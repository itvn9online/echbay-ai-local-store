// Extract coordinates from Google Maps URL
function dvls_extract_gmaps_coords(text) {
	text = text.replace(/\s+/g, "").trim();
	var parts = text.split("/@");
	var coords = null;
	if (parts.length < 2) {
		coords = parts[0].split(",");
		if (coords.length < 2) return null;
	}
	if (coords === null) coords = parts[1].split(",");
	var lat = parseFloat(coords[0]);
	var lng = parseFloat(coords[1]);
	if (isNaN(lat) || isNaN(lng)) return null;
	if (lat < -90 || lat > 90) return null;
	if (lng < -180 || lng > 180) return null;
	return {
		lat: lat,
		lng: lng,
	};
}

(function ($) {
	$(document).ready(function () {
		var dvls_city = $.parseJSON(dvls_admin.local_address);
		var citySelect = $(".dvls_city");
		var districtSelect = $(".dvls_district");
		var oldValueCity = citySelect.data("value");
		var oldValueDistrict = districtSelect.data("value");
		$(dvls_city).each(function (index, value) {
			var thisChecked = "";
			if (oldValueCity == value.id) {
				thisChecked = 'selected="selected"';
				$(value.district).each(function (index, value) {
					var thisChecked = "";
					if (oldValueDistrict == value.id) {
						thisChecked = 'selected="selected"';
					}
					$(".dvls_district").append(
						'<option value="' +
							value.id +
							'" ' +
							thisChecked +
							">" +
							value.name +
							"</option>",
					);
				});
			}
			$(".dvls_city").append(
				'<option value="' +
					value.id +
					'" ' +
					thisChecked +
					">" +
					value.name +
					"</option>",
			);
		});
		$(".dvls_city").on("change", function () {
			var thisval = $(this).val();
			$(".dvls_district").html('<option value="null">Select district</option>');
			$(dvls_city).each(function (index, value) {
				if (thisval == value.id) {
					$(value.district).each(function (index, value) {
						$(".dvls_district").append(
							'<option value="' + value.id + '">' + value.name + "</option>",
						);
					});
					return false;
				}
			});
		});

		// Image upload
		$("body").on("click", ".ireel-upload", function (e) {
			e.preventDefault();
			var thisUpload = $(this).parents(".svl-upload-image");
			meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
				title: "Upload Image",
				button: { text: "Upload Image" },
				library: { type: "image" },
				multiple: false,
			});
			meta_image_frame.on("select", function () {
				var media_attachment = meta_image_frame
					.state()
					.get("selection")
					.first()
					.toJSON();
				if (media_attachment.id) {
					var attachment_image =
						media_attachment.sizes && media_attachment.sizes.thumbnail
							? media_attachment.sizes.thumbnail.url
							: media_attachment.url;
					thisUpload.addClass("has-image");
					thisUpload.find('input[type="hidden"]').val(media_attachment.id);
					thisUpload.find("img.image_view").attr("src", media_attachment.url);
				}
			});
			meta_image_frame.open();
		});

		$("body").on("click", ".svl-delete-image", function () {
			var parentDiv = $(this).parents(".svl-upload-image");
			parentDiv.removeClass("has-image");
			parentDiv.find('input[type="hidden"]').val("");
			return false;
		});

		var mapDiv = $("#dvls_maps");
		if (!mapDiv.length) return;

		var lat = parseFloat(mapDiv.data("lat"));
		var lng = parseFloat(mapDiv.data("lng"));

		var map = L.map("dvls_maps").setView(
			[lat, lng],
			parseInt(dvls_admin.maps_zoom),
		);
		// L.tileLayer("https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}", {
		L.tileLayer("https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}", {
			maxZoom: 20,
			attribution: "&copy; Google",
		}).addTo(map);

		var marker = L.marker([lat, lng], { draggable: true }).addTo(map);

		function updateLatLng(latlng) {
			$("#dvls_maps_lat").val(latlng.lat.toFixed(6));
			$("#dvls_maps_lng").val(latlng.lng.toFixed(6));
			$("#dvls_maps_address").val("");
		}

		map.on("click", function (e) {
			marker.setLatLng(e.latlng);
			updateLatLng(e.latlng);
		});

		marker.on("dragend", function () {
			updateLatLng(marker.getLatLng());
		});

		// Address search via Nominatim (OpenStreetMap geocoder - no API key needed)
		var addressInput = $("#dvls_maps_address");
		addressInput.after(
			'<button type="button" id="dvls_search_address" class="button" style="margin-left:5px">Search</button>',
		);

		function dvls_nominatim_search(query) {
			if (!query) return;
			$.ajax({
				url: "https://nominatim.openstreetmap.org/search",
				data: { q: query, format: "json", limit: 1 },
				dataType: "json",
				headers: { "Accept-Language": "vi,en" },
				success: function (results) {
					if (results && results.length > 0) {
						var result = results[0];
						var newLat = parseFloat(result.lat);
						var newLng = parseFloat(result.lon);
						var latlng = L.latLng(newLat, newLng);
						marker.setLatLng(latlng);
						map.setView(latlng, parseInt(dvls_admin.maps_zoom));
						$("#dvls_maps_lat").val(newLat.toFixed(6));
						$("#dvls_maps_lng").val(newLng.toFixed(6));
					}
				},
			});
		}

		addressInput.on("paste", function (e) {
			var pasted = (
				e.originalEvent.clipboardData || window.clipboardData
			).getData("text");
			if (!pasted) return;
			var coords = dvls_extract_gmaps_coords(pasted);
			if (!coords) return;
			e.preventDefault();
			var latlng = L.latLng(coords.lat, coords.lng);
			marker.setLatLng(latlng);
			map.setView(latlng, parseInt(dvls_admin.maps_zoom));
			$("#dvls_maps_lat").val(coords.lat.toFixed(6));
			$("#dvls_maps_lng").val(coords.lng.toFixed(6));
			$(this).val("");
		});

		addressInput.on("keydown", function (e) {
			if (e.keyCode === 13) {
				e.preventDefault();
				dvls_nominatim_search($(this).val());
			}
		});

		$("#dvls_search_address").on("click", function () {
			dvls_nominatim_search(addressInput.val());
		});
	});
})(jQuery);
