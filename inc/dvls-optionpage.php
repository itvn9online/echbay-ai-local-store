<?php
defined('ABSPATH') or die('No script kiddies please!');
global $dvls_settings;
?>
<div class="wrap">
    <h1><?php _e('Find a local store settings', 'echbay-ai-local-store') ?></h1>
    <p><?php _e('Copy shortcode [devvn_local_stores] to view', 'echbay-ai-local-store'); ?></p>
    <p>
        <input type="text" readonly value="[devvn_local_stores]" />
    </p>

    <form method="post" action="options.php" novalidate="novalidate">
        <?php
        settings_fields($this->_optionGroup);
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="latlng_default"><?php _e('LatLng Default', 'echbay-ai-local-store') ?></label></th>
                    <td>
                        <input type="text" id="dvls_opt_lat" placeholder="Lat default" name="<?php echo $this->_optionName ?>[lat_default]" value="<?php echo esc_attr($dvls_settings['lat_default']); ?>" />
                        <input type="text" id="dvls_opt_lng" placeholder="Lng default" name="<?php echo $this->_optionName ?>[lng_default]" value="<?php echo esc_attr($dvls_settings['lng_default']); ?>" />
                        <p class="description"><?php _e('Click on the map or drag the marker to set the default center.', 'echbay-ai-local-store'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td>
                        <div style="display:flex;gap:6px;margin-bottom:8px;">
                            <input type="text" id="dvls_opt_address" placeholder="<?php _e('Search address...', 'echbay-ai-local-store'); ?>" style="flex:1;" />
                            <button type="button" id="dvls_opt_search" class="button"><?php _e('Search', 'echbay-ai-local-store'); ?></button>
                        </div>
                        <div id="dvls_opt_map" style="width:100%;height:380px;"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="latlng_default"><?php _e('Marker icon', 'echbay-ai-local-store') ?></label></th>
                    <td>
                        <?php
                        wp_enqueue_media();
                        $imgid = intval($dvls_settings['marker_icon']);
                        ?>
                        <div class="svl-upload-image <?php if ($imgid): ?>has-image<?php endif; ?>">
                            <div class="view-has-value">
                                <input type="hidden" class="clone_delete" name="<?php echo $this->_optionName ?>[marker_icon]" id="maps_marker_icon" value="<?php echo esc_attr($imgid); ?>" />
                                <img src="<?php echo wp_get_attachment_image_url($imgid, 'full') ?>" class="image_view pins_img" />
                                <a href="#" class="svl-delete-image">x</a>
                            </div>
                            <div class="hidden-has-value"><input type="button" class="ireel-upload button" value="<?php _e('Select images', 'devvn') ?>" /></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="maps_zoom"><?php _e('Maps Zoom', 'echbay-ai-local-store') ?></label></th>
                    <td>
                        <input type="number" min="3" name="<?php echo $this->_optionName ?>[maps_zoom]" id="maps_zoom" value="<?php echo intval($dvls_settings['maps_zoom']); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="radius"><?php _e('Radius <=', 'echbay-ai-local-store') ?></label></th>
                    <td>
                        <input type="number" min="1" name="<?php echo $this->_optionName ?>[radius]" id="radius" value="<?php echo intval($dvls_settings['radius']); ?>" /> km
                        <small class="dvls_description"><?php _e('For find a store near you', 'echbay-ai-local-store'); ?></small>
                    </td>
                </tr>
                <?php do_settings_fields('dvls-options-group', 'default'); ?>
            </tbody>
        </table>
        <h2><?php _e('First load settings', 'echbay-ai-local-store') ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="number_post"><?php _e('Number store to first load', 'echbay-ai-local-store') ?></label></th>
                    <td>
                        <input type="number" min="-1" name="<?php echo $this->_optionName ?>[number_post]" id="number_post" value="<?php echo intval($dvls_settings['number_post']); ?>" />
                        <small class="dvls_description"><?php _e('Set -1 to load all stores. Default 20', 'echbay-ai-local-store'); ?></small>
                    </td>
                </tr>
            </tbody>
        </table>
        <h2><?php _e('Labels', 'echbay-ai-local-store') ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="disallow_labels"><?php _e('Disallow label', 'echbay-ai-local-store') ?></label></th>
                    <td>
                        <input type="text" name="<?php echo $this->_optionName ?>[disallow_labels]" id="disallow_labels" value="<?php echo esc_attr($dvls_settings['disallow_labels']); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="get_directions"><?php _e('Get Directions', 'echbay-ai-local-store') ?></label></th>
                    <td>
                        <input type="text" name="<?php echo $this->_optionName ?>[get_directions]" id="get_directions" value="<?php echo esc_attr($dvls_settings['get_directions']); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="text_open"><?php _e('Open') ?></label></th>
                    <td>
                        <input type="text" name="<?php echo $this->_optionName ?>[text_open]" id="text_open" value="<?php echo esc_attr($dvls_settings['text_open']); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="text_phone"><?php _e('Phone') ?></label></th>
                    <td>
                        <input type="text" name="<?php echo $this->_optionName ?>[text_phone]" id="text_phone" value="<?php echo esc_attr($dvls_settings['text_phone']); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="text_hotline"><?php _e('Hotline') ?></label></th>
                    <td>
                        <input type="text" name="<?php echo $this->_optionName ?>[text_hotline]" id="text_hotline" value="<?php echo esc_attr($dvls_settings['text_hotline']); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="text_email"><?php _e('Email') ?></label></th>
                    <td>
                        <input type="text" name="<?php echo $this->_optionName ?>[text_email]" id="text_email" value="<?php echo esc_attr($dvls_settings['text_email']); ?>" />
                    </td>
                </tr>
            </tbody>
        </table>
        <?php do_settings_sections('dvls-options-group', 'default'); ?>
        <?php submit_button(); ?>
    </form>
</div>
<link rel='stylesheet' id='dvls-leaflet-css-css' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css?ver=1.9.4' media='all' />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js?ver=1.9.4" id="dvls-leaflet-js-js"></script>
<script>
    (function() {
        var latInput = document.getElementById('dvls_opt_lat');
        var lngInput = document.getElementById('dvls_opt_lng');
        var lat = parseFloat(latInput.value) || 21.0208;
        var lng = parseFloat(lngInput.value) || 105.8095;
        var zoom = <?php echo intval($dvls_settings['maps_zoom']); ?>;

        var map = L.map('dvls_opt_map').setView([lat, lng], zoom);
        // L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: '&copy; Google'
        }).addTo(map);

        var marker = L.marker([lat, lng], {
            draggable: true
        }).addTo(map);

        function applyLatLng(latlng) {
            latInput.value = latlng.lat.toFixed(6);
            lngInput.value = latlng.lng.toFixed(6);
        }

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            applyLatLng(e.latlng);
        });

        marker.on('dragend', function() {
            applyLatLng(marker.getLatLng());
        });

        // Sync map when user manually edits the inputs
        [latInput, lngInput].forEach(function(input) {
            input.addEventListener('change', function() {
                var newLat = parseFloat(latInput.value);
                var newLng = parseFloat(lngInput.value);
                if (!isNaN(newLat) && !isNaN(newLng)) {
                    var ll = L.latLng(newLat, newLng);
                    marker.setLatLng(ll);
                    map.setView(ll);
                }
            });
        });

        // Address search via Nominatim
        function dvls_opt_search(query) {
            query = query.trim();
            if (!query) return;
            var btn = document.getElementById('dvls_opt_search');
            btn.disabled = true;
            btn.textContent = '...';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'https://nominatim.openstreetmap.org/search?format=json&limit=1&accept-language=vi,en&q=' + encodeURIComponent(query));
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.onload = function() {
                btn.disabled = false;
                btn.textContent = '<?php _e('Search', 'echbay-ai-local-store'); ?>';
                if (xhr.status === 200) {
                    var results = JSON.parse(xhr.responseText);
                    if (results && results.length > 0) {
                        var newLat = parseFloat(results[0].lat);
                        var newLng = parseFloat(results[0].lon);
                        var ll = L.latLng(newLat, newLng);
                        marker.setLatLng(ll);
                        map.setView(ll, zoom);
                        applyLatLng(ll);
                    }
                }
            };
            xhr.onerror = function() {
                btn.disabled = false;
                btn.textContent = '<?php _e('Search', 'echbay-ai-local-store'); ?>';
            };
            xhr.send();
        }

        document.getElementById('dvls_opt_search').addEventListener('click', function() {
            dvls_opt_search(document.getElementById('dvls_opt_address').value);
        });
        document.getElementById('dvls_opt_address').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                dvls_opt_search(this.value);
            }
        });
    })();
</script>