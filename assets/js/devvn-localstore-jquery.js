(function ($) {
	$(document).ready(function () {
		var dvls_city = $.parseJSON(devvn_localstore_array.local_address);
		var citySelect = $("#dvls_city");
		var districtSelect = $("#dvls_district");
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
					districtSelect.append(
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
			citySelect.append(
				'<option value="' +
					value.id +
					'" ' +
					thisChecked +
					">" +
					value.name +
					"</option>",
			);
		});
		$(citySelect).on("change", function () {
			var thisval = $(this).val();
			districtSelect.html(
				'<option value="null" selected>' +
					devvn_localstore_array.select_text +
					"</option>",
			);
			$(dvls_city).each(function (index, value) {
				if (thisval == value.id) {
					$(value.district).each(function (index, value) {
						districtSelect.append(
							'<option value="' + value.id + '">' + value.name + "</option>",
						);
					});
					return false;
				}
			});
		});

		var map,
			markers = [],
			dvls_loading = false;
		var mapDiv = $("#dvls_maps");

		function dvls_initMap() {
			var lat = parseFloat(mapDiv.data("lat"));
			var lng = parseFloat(mapDiv.data("lng"));
			map = L.map("dvls_maps").setView(
				[lat, lng],
				parseInt(devvn_localstore_array.maps_zoom),
			);
			// L.tileLayer("https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}", {
			L.tileLayer("https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}", {
				maxZoom: 20,
				attribution: "&copy; Google",
			}).addTo(map);
			dvls_lastest_store();
		}
		dvls_initMap();

		$(".dvls_near_you").on("click", function () {
			var thisDisallow = $(this).data("disallow");
			if (thisDisallow == 1) return false;
			devvn_findstore_nearyou();
			return false;
		});
		function dvls_notsupport_geocoder() {
			$(".dvls_near_you").remove();
		}
		function dvls_disallow_geocoder() {
			$(".dvls_near_you")
				.attr("data-disallow", "1")
				.html(devvn_localstore_array.labels.disallow_labels);
		}
		function devvn_findstore_nearyou() {
			if (navigator.geolocation) {
				dvls_before_load();
				navigator.geolocation.getCurrentPosition(
					successFunction,
					errorFunction,
				);
			} else {
				dvls_notsupport_geocoder();
				dvls_lastest_store();
			}
		}
		function errorFunction() {
			dvls_disallow_geocoder();
			dvls_lastest_store();
		}
		function successFunction(position) {
			var lat = position.coords.latitude;
			var lng = position.coords.longitude;
			var data = [];
			data["lat"] = lat;
			data["lng"] = lng;
			data["near"] = true;
			dvls_firstload_store(data);
		}

		function dvls_lastest_store() {
			var data = [];
			data["near"] = false;
			dvls_firstload_store(data);
		}

		function dvls_before_load() {
			dvls_loading = true;
			$(".dvls_maps_body").addClass("devvn_loading");
		}

		function dvls_ajax_load_success(response) {
			if (response.success) {
				var maps_data = response.data;
				var boundsArr = [];
				clearLocations();
				$(".dvls_result_wrap").html("");
				for (var i = 0; i < maps_data.length; i++) {
					var lat = maps_data[i].maps_lat ? maps_data[i].maps_lat : "";
					var lng = maps_data[i].maps_lng ? maps_data[i].maps_lng : "";
					var dataMarker = {};
					dataMarker["stt"] = i;
					dataMarker["id"] = maps_data[i].id ? parseInt(maps_data[i].id) : "";
					dataMarker["title"] = maps_data[i].title ? maps_data[i].title : "";
					dataMarker["name"] = maps_data[i].name ? maps_data[i].name : "";
					dataMarker["thumb"] = maps_data[i].thumb ? maps_data[i].thumb : "";
					dataMarker["address"] = maps_data[i].address
						? maps_data[i].address
						: "";
					dataMarker["city"] = maps_data[i].city
						? parseInt(maps_data[i].city)
						: "";
					dataMarker["district"] = maps_data[i].district
						? parseInt(maps_data[i].district)
						: "";
					dataMarker["phone1"] = maps_data[i].phone1 ? maps_data[i].phone1 : "";
					dataMarker["phone2"] = maps_data[i].phone2 ? maps_data[i].phone2 : "";
					dataMarker["hotline1"] = maps_data[i].hotline1
						? maps_data[i].hotline1
						: "";
					dataMarker["hotline2"] = maps_data[i].hotline2
						? maps_data[i].hotline2
						: "";
					dataMarker["email"] = maps_data[i].email ? maps_data[i].email : "";
					dataMarker["open"] = maps_data[i].open ? maps_data[i].open : "";
					var marker_defaultURL = devvn_localstore_array.marker_default[0]
						? devvn_localstore_array.marker_default[0]
						: "";
					var marker_defaultH = devvn_localstore_array.marker_default[2]
						? parseInt(devvn_localstore_array.marker_default[2])
						: 35;
					dataMarker["marker"] =
						maps_data[i].marker && maps_data[i].marker[0]
							? maps_data[i].marker[0]
							: marker_defaultURL;
					dataMarker["h_marker"] =
						maps_data[i].marker && maps_data[i].marker[2]
							? parseInt(maps_data[i].marker[2])
							: marker_defaultH;
					dataMarker["maps_lat"] = lat;
					dataMarker["maps_lng"] = lng;
					createMarker(dataMarker);
					if (lat && lng) boundsArr.push([lat, lng]);
					var has_thumb = "";
					if (dataMarker["thumb"]) has_thumb = "has_thumb";

					var $html =
						'<div data-id="' +
						i +
						'" data-lat="' +
						lat +
						'" data-lng="' +
						lng +
						'" class="dvls_result_item ' +
						has_thumb +
						'">';
					if (dataMarker["thumb"]) {
						$html +=
							'<div class="dvls_result_thumb"><img src="' +
							dataMarker["thumb"] +
							'" alt=""></div>';
					}
					$html += '<div class="dvls_result_infor">';
					if (dataMarker["name"]) {
						$html += "<h3>" + dataMarker["name"] + "</h3>";
					} else {
						$html += "<h3>" + dataMarker["title"] + "</h3>";
					}
					$html += "<p>" + dataMarker["address"] + "</p>";
					$html += "<p>";
					if (dataMarker["hotline1"]) {
						$html +=
							'<a href="tel:' +
							dataMarker["hotline1"] +
							'">' +
							dataMarker["hotline1"] +
							"</a>";
					}
					if (dataMarker["hotline1"] && dataMarker["hotline2"]) {
						$html += " - ";
					}
					if (dataMarker["hotline2"]) {
						$html +=
							'<a href="tel:' +
							dataMarker["hotline2"] +
							'">' +
							dataMarker["hotline2"] +
							"</a>";
					}
					$html += "</p>";
					$html +=
						'<a href="https://www.google.com/maps/dir/?api=1&destination=' +
						lat +
						"," +
						lng +
						'" target="_blank" rel="nofollow">' +
						devvn_localstore_array.labels.get_directions +
						"</a>";
					$html += "</div>";
					$html += "</div>";

					$(".dvls_result_wrap").append($html);
				}
				if (maps_data.length) {
					if (boundsArr.length > 1) {
						map.fitBounds(L.latLngBounds(boundsArr), { padding: [30, 30] });
					} else if (boundsArr.length === 1) {
						map.setView(
							boundsArr[0],
							parseInt(devvn_localstore_array.maps_zoom),
						);
					}
					$(".dvls_result_wrap")
						.off("click")
						.on("click", ".dvls_result_item", function () {
							var markerNum = $(this).data("id");
							var latsvl = $(this).data("lat");
							var lngsvl = $(this).data("lng");
							markers[markerNum].fire("click");
							map.setZoom(parseInt(devvn_localstore_array.maps_zoom));
							map.setView([latsvl, lngsvl]);
							$(".dvls_result_wrap .dvls_result_item").removeClass("active");
							$(this).addClass("active");
						});

					$(".dvls_result_status strong").html(maps_data.length);
					$(".dvls_result_status").addClass("show");
				}
			} else {
				clearAllData();
			}
			$(".dvls_maps_body").removeClass("devvn_loading");
		}

		function dvls_firstload_store(data) {
			var near = data["near"] ? true : false;
			var lat = data["lat"] ? data["lat"] : "";
			var lng = data["lng"] ? data["lng"] : "";
			var nonce = $("#dvls_nonce").val();
			var action = "dvls_loadlastest_store";
			$.ajax({
				type: "post",
				dataType: "json",
				url: devvn_localstore_array.ajaxurl,
				data: {
					action: action,
					lat: lat,
					lng: lng,
					near: near,
					nonce: nonce,
				},
				context: this,
				beforeSend: function () {
					dvls_before_load();
				},
				success: function (response) {
					dvls_loading = false;
					dvls_ajax_load_success(response);
				},
				error: function () {
					dvls_loading = false;
					$(".dvls_maps_body").removeClass("devvn_loading");
				},
			});
		}

		function clearAllData() {
			$(".dvls_result_wrap").html("");
			clearLocations();
			$(".dvls_result_status strong").html("0");
			$(".dvls_result_status").addClass("show");
			map.setView([
				parseFloat(devvn_localstore_array.lat_default),
				parseFloat(devvn_localstore_array.lng_default),
			]);
		}

		function dvls_loadresult() {
			var nonce = $("#dvls_nonce").val();
			var cityid = $("#dvls_city").val();
			var districtid = $("#dvls_district").val();
			if (!dvls_loading) {
				$.ajax({
					type: "post",
					dataType: "json",
					url: devvn_localstore_array.ajaxurl,
					data: {
						action: "dvls_load_localstores",
						cityid: cityid,
						districtid: districtid,
						nonce: nonce,
					},
					context: this,
					beforeSend: function () {
						dvls_before_load();
					},
					success: function (response) {
						dvls_loading = false;
						dvls_ajax_load_success(response);
					},
					error: function () {
						$(".dvls_maps_body").removeClass("devvn_loading");
						dvls_loading = false;
					},
				});
			}
		}

		function createMarker(dataMarker) {
			var i = dataMarker.stt;
			var h_marker = dataMarker.h_marker ? dataMarker.h_marker : 35;
			var html = "";
			html += '<div class="item infobox" data-id="' + i + '">';
			if (dataMarker.thumb) {
				html +=
					'<div class="item_infobox_thumb"><img src="' +
					dataMarker.thumb +
					'" alt=""></div>';
			}
			html += '<div class="item_infobox_infor">';
			if (dataMarker.name) {
				html += "<h3>" + dataMarker.name + "</h3>";
			} else {
				html += "<h3>" + dataMarker.title + "</h3>";
			}
			html += "<p>" + dataMarker.address + "</p>";
			if (dataMarker.phone1 || dataMarker.phone2) {
				html += "<p>" + devvn_localstore_array.labels.text_phone + ": ";
				if (dataMarker.phone1) {
					html +=
						'<a href="tel:' +
						dataMarker.phone1 +
						'">' +
						dataMarker.phone1 +
						"</a>";
				}
				if (dataMarker.phone1 && dataMarker.phone2) {
					html += " - ";
				}
				if (dataMarker.phone2) {
					html +=
						'<a href="tel:' +
						dataMarker.phone2 +
						'">' +
						dataMarker.phone2 +
						"</a>";
				}
				html += "</p>";
			}
			if (dataMarker.hotline1 || dataMarker.hotline2) {
				html += "<p>" + devvn_localstore_array.labels.text_hotline + ": ";
				if (dataMarker.hotline1) {
					html +=
						'<a href="tel:' +
						dataMarker.hotline1 +
						'">' +
						dataMarker.hotline1 +
						"</a>";
				}
				if (dataMarker.hotline1 && dataMarker.hotline2) {
					html += " - ";
				}
				if (dataMarker.hotline2) {
					html +=
						'<a href="tel:' +
						dataMarker.hotline2 +
						'">' +
						dataMarker.hotline2 +
						"</a>";
				}
				html += "</p>";
			}
			html += "<p>";
			if (dataMarker.email) {
				html +=
					devvn_localstore_array.labels.text_email +
					': <a href="mailto:' +
					dataMarker.email +
					'">' +
					dataMarker.email +
					"</a>";
			}
			html += "</p>";
			if (dataMarker.open) {
				html +=
					"<p>" +
					devvn_localstore_array.labels.text_open +
					": " +
					dataMarker.open +
					"</p>";
			}
			html +=
				'<a href="https://www.google.com/maps/dir/?api=1&destination=' +
				dataMarker.maps_lat +
				"," +
				dataMarker.maps_lng +
				'" target="_blank" rel="nofollow">' +
				devvn_localstore_array.labels.get_directions +
				"</a>";
			html += "</div>";
			html += "</div>";

			var marker;
			if (dataMarker.marker) {
				var iconH = h_marker;
				var iconW = Math.round(iconH * 0.7);
				var icon = L.icon({
					iconUrl: dataMarker.marker,
					iconSize: [iconW, iconH],
					iconAnchor: [Math.round(iconW / 2), iconH],
					popupAnchor: [0, -iconH],
				});
				marker = L.marker([dataMarker.maps_lat, dataMarker.maps_lng], {
					icon: icon,
				});
			} else {
				marker = L.marker([dataMarker.maps_lat, dataMarker.maps_lng]);
			}
			marker.addTo(map);
			marker.bindPopup(html, {
				maxWidth: 300,
				className: "infobox-wrapper",
			});
			marker.on("click", function () {
				this.openPopup();
				$(".dvls_result_item.active").removeClass("active");
				$(".dvls_result_item[data-id=" + i + "]").addClass("active");
			});
			marker.on("popupclose", function () {
				$(".dvls_result_item[data-id=" + i + "]").removeClass("active");
			});
			markers.push(marker);
		}

		function clearLocations() {
			for (var i = 0; i < markers.length; i++) {
				map.removeLayer(markers[i]);
			}
			markers.length = 0;
		}

		$(".dvls-submit").on("click", function () {
			dvls_loadresult();
			return false;
		});
	});
})(jQuery);
