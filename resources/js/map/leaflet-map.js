/**
 * NusaWisata - Leaflet Map Integration
 * Uses OpenStreetMap tile provider
 */
window.NusaMap = {
  /**
   * Initialize a Leaflet map
   * @param {string} elementId - DOM element ID for the map container
   * @param {number} lat - Latitude
   * @param {number} lng - Longitude
   * @param {number} zoom - Zoom level (default: 13)
   * @returns {object} Leaflet map instance
   */
  init: function (elementId, lat, lng, zoom) {
    if (typeof L === 'undefined') {
      console.error('Leaflet library not loaded');
      return null;
    }

    zoom = zoom || 13;
    var map = L.map(elementId).setView([lat, lng], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    }).addTo(map);

    return map;
  },

  /**
   * Add a marker to the map
   * @param {object} map - Leaflet map instance
   * @param {number} lat
   * @param {number} lng
   * @param {string} title - Popup title
   * @param {string|object} extra - Popup description or object with {description, province, category, rating, url}
   * @returns {object} Leaflet marker instance
   */
  addMarker: function (map, lat, lng, title, extra) {
    if (!map) {
      return null;
    }

    var popupContent = '<div class="nusa-map-popup">';
    popupContent += '<h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: #0f172a;">' + title + '</h4>';

    if (typeof extra === 'object' && extra !== null) {
      if (extra.province) {
        popupContent += '<div style="font-size: 12px; color: #64748b; margin-bottom: 4px;"><i class="fa-solid fa-location-dot" style="color: #ef4444; margin-right: 4px;"></i>' + extra.province + '</div>';
      }
      popupContent += '<div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 6px; padding-top: 6px; border-top: 1px solid #e2e8f0;">';
      if (extra.category) {
        popupContent += '<span style="display: inline-block; background: #e0e7ff; color: #4338ca; font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 9999px;">' + extra.category + '</span>';
      }
      if (extra.rating) {
        popupContent += '<span style="font-size: 12px; font-weight: 700; color: #f59e0b;">★ ' + parseFloat(extra.rating).toFixed(1) + '</span>';
      }
      popupContent += '</div>';

      if (extra.url) {
        popupContent += '<a href="' + extra.url + '" target="_blank" style="display: block; text-align: center; margin-top: 8px; padding: 5px 10px; background: #1a56db; color: #fff; font-size: 11.5px; font-weight: 600; border-radius: 6px; text-decoration: none;">Lihat Detail &rarr;</a>';
      }
    } else if (extra) {
      popupContent += '<p style="margin: 4px 0 0 0; font-size: 12px; color: #475569;">' + extra + '</p>';
    }

    popupContent += '</div>';

    var marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup(popupContent);

    return marker;
  },

  /**
   * Add multiple markers from an array
   * @param {object} map - Leaflet map instance
   * @param {Array} destinations - Array of {lat, lng, name, description, province, category, rating, url}
   */
  addMarkers: function (map, destinations) {
    if (!map || !destinations) {
      return;
    }

    var bounds = [];
    destinations.forEach(function (dest) {
      if (dest.lat && dest.lng) {
        NusaMap.addMarker(map, dest.lat, dest.lng, dest.name, {
          description: dest.description || '',
          province: dest.province || '',
          category: dest.category || '',
          rating: dest.rating || dest.google_rating || null,
          url: dest.url || null
        });
        bounds.push([dest.lat, dest.lng]);
      }
    });

    if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [30, 30] });
    }
  },
};
