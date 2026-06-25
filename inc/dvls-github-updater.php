<?php
/**
 * GitHub Updater for EchBay Local Store
 *
 * Hooks into WordPress plugin update system to deliver updates
 * directly from GitHub without the WordPress.org repository.
 *
 * Version check URL : https://raw.githubusercontent.com/itvn9online/echbay-ai-local-store/refs/heads/main/VERSION
 *                     (falls back to version.txt if VERSION is missing)
 * Download ZIP URL  : https://github.com/itvn9online/echbay-ai-local-store/archive/refs/heads/main.zip
 */
defined('ABSPATH') or die('No script kiddies please!');

if (!class_exists('DVLS_GitHub_Updater')) {

    class DVLS_GitHub_Updater
    {
        /** Plugin slug: folder/main-file.php */
        private $plugin_slug;

        /** Installed plugin folder name (may include a stale -main suffix). */
        private $plugin_folder;

        /** Canonical folder name without GitHub's -main suffix. */
        private $canonical_folder;

        /** Currently installed version string */
        private $current_version;

        private $github_user = 'itvn9online';
        private $github_repo = 'echbay-ai-local-store';
        private $zip_url;

        /** Transient key for caching the remote version check (12 h) */
        const CACHE_KEY = 'dvls_github_remote_version';

        public function __construct($plugin_slug, $current_version)
        {
            $this->plugin_slug       = $plugin_slug;
            $this->plugin_folder     = dirname($plugin_slug);
            $this->canonical_folder  = $this->github_repo;
            $this->current_version   = $current_version;

            $this->zip_url = 'https://github.com/'
                . $this->github_user . '/' . $this->github_repo
                . '/archive/refs/heads/main.zip';

            // Inject update info into WordPress transient
            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));

            // Provide plugin info for the "View version x.x.x details" modal
            add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);

            // Rename the extracted GitHub folder (echbay-ai-local-store-main)
            // to the canonical plugin folder name (echbay-ai-local-store)
            add_filter('upgrader_source_selection', array($this, 'rename_github_source'), 10, 4);

            // Move installs that still live in a stale *-main folder after update
            add_action('upgrader_process_complete', array($this, 'maybe_normalize_plugin_folder'), 10, 2);

            // Allow admin to clear cache via ?dvls_check_update=1 on plugins page
            add_action('admin_init', array($this, 'maybe_clear_cache'));
        }

        // ---------------------------------------------------------------
        // Remote version check
        // ---------------------------------------------------------------

        /**
         * Fetch the VERSION (or version.txt) file from GitHub. Cached for 12 hours.
         * Returns a version string such as "1.2.0" or false on failure.
         */
        private function get_remote_version()
        {
            $cached = get_transient(self::CACHE_KEY);
            if ($cached !== false) {
                return $cached;
            }

            $version_files = array('VERSION', 'version.txt');

            foreach ($version_files as $version_file) {
                $version_url = 'https://raw.githubusercontent.com/'
                    . $this->github_user . '/' . $this->github_repo
                    . '/refs/heads/main/' . $version_file;

                $response = wp_remote_get($version_url, array(
                    'timeout'    => 10,
                    'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
                ));

                if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                    continue;
                }

                $version = trim(wp_remote_retrieve_body($response));

                if (!preg_match('/^\d+\.\d+(\.\d+)*$/', $version)) {
                    continue;
                }

                set_transient(self::CACHE_KEY, $version, 12 * HOUR_IN_SECONDS);

                return $version;
            }

            return false;
        }

        /**
         * Whether the given plugin basename refers to this plugin's main file.
         */
        private function is_this_plugin($plugin_basename)
        {
            return basename($plugin_basename) === basename($this->plugin_slug);
        }

        // ---------------------------------------------------------------
        // WordPress update hooks
        // ---------------------------------------------------------------

        /**
         * Inject our plugin into WordPress's update_plugins transient
         * so it appears in Dashboard > Updates.
         */
        public function check_update($transient)
        {
            if (empty($transient->checked)) {
                return $transient;
            }

            $remote_version = $this->get_remote_version();

            if ($remote_version && version_compare($remote_version, $this->current_version, '>')) {
                $transient->response[$this->plugin_slug] = (object) array(
                    'slug'        => $this->plugin_folder,
                    'plugin'      => $this->plugin_slug,
                    'new_version' => $remote_version,
                    'url'         => 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
                    'package'     => $this->zip_url,
                    'tested'      => '',
                    'requires'    => '',
                    'requires_php' => '',
                );
            } else {
                // Remove stale response so WordPress shows "up to date"
                if (isset($transient->response[$this->plugin_slug])) {
                    unset($transient->response[$this->plugin_slug]);
                }
            }

            return $transient;
        }

        /**
         * Provide plugin meta for the "View details" popup in the updates screen.
         */
        public function plugin_info($result, $action, $args)
        {
            if ($action !== 'plugin_information') {
                return $result;
            }

            if (!isset($args->slug) || !in_array($args->slug, array($this->plugin_folder, $this->canonical_folder), true)) {
                return $result;
            }

            $remote_version = $this->get_remote_version();

            return (object) array(
                'name'          => 'EchBay Local Store',
                'slug'          => $this->plugin_folder,
                'version'       => $remote_version ?: $this->current_version,
                'author'        => '<a href="https://webgiare.org" target="_blank">Dao Quoc Dai</a>',
                'homepage'      => 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
                'download_link' => $this->zip_url,
                'sections'      => array(
                    'description' => 'Find a Local Store by EchBay. '
                        . '<br><br>Source: <a href="https://github.com/'
                        . esc_html($this->github_user) . '/' . esc_html($this->github_repo)
                        . '" target="_blank">GitHub</a>',
                ),
            );
        }

        /**
         * After WordPress extracts the ZIP, rename the GitHub-style folder
         * (echbay-ai-local-store-main) to the canonical plugin folder name
         * (echbay-ai-local-store) so the update lands in the right place.
         */
        public function rename_github_source($source, $remote_source, $upgrader, $hook_extra = array())
        {
            global $wp_filesystem;

            if (
                !isset($hook_extra['plugin']) ||
                !$this->is_this_plugin($hook_extra['plugin'])
            ) {
                return $source;
            }

            $expected_github_name = $this->canonical_folder . '-main';
            $source_basename      = basename(untrailingslashit($source));

            if ($source_basename !== $expected_github_name) {
                return $source;
            }

            $correct_source = trailingslashit($remote_source) . $this->canonical_folder . '/';

            if (!$wp_filesystem) {
                return $source;
            }

            if ($wp_filesystem->move($source, $correct_source)) {
                return $correct_source;
            }

            return $source;
        }

        /**
         * After an update, move the plugin out of a stale *-main folder if needed.
         */
        public function maybe_normalize_plugin_folder($upgrader, $hook_extra)
        {
            if (
                empty($hook_extra['action']) ||
                $hook_extra['action'] !== 'update' ||
                empty($hook_extra['type']) ||
                $hook_extra['type'] !== 'plugin'
            ) {
                return;
            }

            $plugins = array();

            if (!empty($hook_extra['plugins']) && is_array($hook_extra['plugins'])) {
                $plugins = $hook_extra['plugins'];
            } elseif (!empty($hook_extra['plugin'])) {
                $plugins = array($hook_extra['plugin']);
            }

            foreach ($plugins as $plugin_basename) {
                if (!$this->is_this_plugin($plugin_basename)) {
                    continue;
                }

                $this->normalize_plugin_folder(dirname($plugin_basename));
            }
        }

        /**
         * Ensure the plugin directory uses the canonical name (no -main suffix).
         */
        private function normalize_plugin_folder($current_folder)
        {
            if ($current_folder === $this->canonical_folder) {
                return;
            }

            global $wp_filesystem;

            if (!$wp_filesystem) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }

            if (!$wp_filesystem) {
                return;
            }

            $old_path = trailingslashit(WP_PLUGIN_DIR) . $current_folder;
            $new_path = trailingslashit(WP_PLUGIN_DIR) . $this->canonical_folder;

            if (!$wp_filesystem->exists($old_path)) {
                return;
            }

            if ($wp_filesystem->exists($new_path)) {
                $wp_filesystem->delete($new_path, true);
            }

            if (!$wp_filesystem->move($old_path, $new_path)) {
                return;
            }

            $old_plugin = $current_folder . '/' . basename($this->plugin_slug);
            $new_plugin = $this->canonical_folder . '/' . basename($this->plugin_slug);

            if (is_plugin_active($old_plugin)) {
                deactivate_plugins($old_plugin, true);
                activate_plugin($new_plugin, '', is_network_admin(), true);
            }
        }

        // ---------------------------------------------------------------
        // Cache management
        // ---------------------------------------------------------------

        /**
         * Clear cached version so the next page load forces a fresh GitHub check.
         * Triggered when an admin visits Plugins page with ?dvls_check_update=1
         */
        public function maybe_clear_cache()
        {
            if (
                isset($_GET['dvls_check_update']) &&
                current_user_can('update_plugins') &&
                isset($_GET['_wpnonce']) &&
                wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'dvls_check_update')
            ) {
                self::delete_version_cache();
                delete_site_transient('update_plugins');
                wp_safe_redirect(admin_url('plugins.php'));
                exit;
            }
        }

        public static function delete_version_cache()
        {
            delete_transient(self::CACHE_KEY);
        }
    }
}
