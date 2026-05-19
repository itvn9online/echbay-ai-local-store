<?php
/**
 * GitHub Updater for EchBay Local Store
 *
 * Hooks into WordPress plugin update system to deliver updates
 * directly from GitHub without the WordPress.org repository.
 *
 * Version check URL : https://raw.githubusercontent.com/itvn9online/echbay-ai-local-store/refs/heads/main/VERSION
 * Download ZIP URL  : https://github.com/itvn9online/echbay-ai-local-store/archive/refs/heads/main.zip
 */
defined('ABSPATH') or die('No script kiddies please!');

if (!class_exists('DVLS_GitHub_Updater')) {

    class DVLS_GitHub_Updater
    {
        /** Plugin slug: folder/main-file.php */
        private $plugin_slug;

        /** Just the folder name (e.g. echbay-ai-local-store) */
        private $plugin_folder;

        /** Currently installed version string */
        private $current_version;

        private $github_user = 'itvn9online';
        private $github_repo = 'echbay-ai-local-store';
        private $version_url;
        private $zip_url;

        /** Transient key for caching the remote version check (12 h) */
        const CACHE_KEY = 'dvls_github_remote_version';

        public function __construct($plugin_slug, $current_version)
        {
            $this->plugin_slug     = $plugin_slug;
            $this->plugin_folder   = dirname($plugin_slug); // echbay-ai-local-store
            $this->current_version = $current_version;

            $this->version_url = 'https://raw.githubusercontent.com/'
                . $this->github_user . '/' . $this->github_repo
                . '/refs/heads/main/VERSION';

            $this->zip_url = 'https://github.com/'
                . $this->github_user . '/' . $this->github_repo
                . '/archive/refs/heads/main.zip';

            // Inject update info into WordPress transient
            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));

            // Provide plugin info for the "View version x.x.x details" modal
            add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);

            // Rename the extracted GitHub folder (echbay-ai-local-store-main)
            // to the correct plugin folder name (echbay-ai-local-store)
            add_filter('upgrader_source_selection', array($this, 'rename_github_source'), 10, 4);

            // Allow admin to clear cache via ?dvls_check_update=1 on plugins page
            add_action('admin_init', array($this, 'maybe_clear_cache'));
        }

        // ---------------------------------------------------------------
        // Remote version check
        // ---------------------------------------------------------------

        /**
         * Fetch the VERSION file from GitHub. Result is cached for 12 hours.
         * Returns a version string such as "1.2.0" or false on failure.
         */
        private function get_remote_version()
        {
            $cached = get_transient(self::CACHE_KEY);
            if ($cached !== false) {
                return $cached;
            }

            $response = wp_remote_get($this->version_url, array(
                'timeout'    => 10,
                'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            ));

            if (is_wp_error($response)) {
                return false;
            }

            if (wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }

            $version = trim(wp_remote_retrieve_body($response));

            // Accept only semver-like strings to avoid storing error HTML
            if (!preg_match('/^\d+\.\d+(\.\d+)*$/', $version)) {
                return false;
            }

            set_transient(self::CACHE_KEY, $version, 12 * HOUR_IN_SECONDS);

            return $version;
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

            if (!isset($args->slug) || $args->slug !== $this->plugin_folder) {
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
         * (echbay-ai-local-store-main) to the real plugin folder name
         * (echbay-ai-local-store) so the update lands in the right place.
         *
         * @param string      $source        Extracted source path (may end with -main/)
         * @param string      $remote_source Temp directory containing the extraction
         * @param WP_Upgrader $upgrader      Upgrader instance
         * @param array       $hook_extra    Contains 'plugin' key with plugin slug
         * @return string Corrected source path
         */
        public function rename_github_source($source, $remote_source, $upgrader, $hook_extra = array())
        {
            global $wp_filesystem;

            // Only act when updating this plugin
            if (
                !isset($hook_extra['plugin']) ||
                $hook_extra['plugin'] !== $this->plugin_slug
            ) {
                return $source;
            }

            // GitHub extracts to echbay-ai-local-store-main/
            $expected_github_name = $this->plugin_folder . '-main';
            $source_basename      = basename(untrailingslashit($source));

            if ($source_basename !== $expected_github_name) {
                // Already has the right name, nothing to do
                return $source;
            }

            $correct_source = trailingslashit($remote_source) . $this->plugin_folder . '/';

            if (!$wp_filesystem) {
                return $source;
            }

            if ($wp_filesystem->move($source, $correct_source)) {
                return $correct_source;
            }

            // If rename fails, return original and let WordPress handle the error
            return $source;
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
