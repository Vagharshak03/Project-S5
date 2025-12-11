// attach initMap to global window object
window.initMap = function() {
    const map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 40.1772, lng: 44.5035 },
        zoom: 8,
        mapTypeId: 'satellite'
    });

    const infoWindow = new google.maps.InfoWindow();

    const loadBtn = document.getElementById('loadBtn');
    loadBtn.addEventListener('click', () => loadPolygons(map, infoWindow));

    // initial load
    loadPolygons(map, infoWindow);
};

// move your loadPolygons function to accept map and infoWindow as parameters
async function loadPolygons(map, infoWindow) {
    const lat = parseFloat(document.getElementById('lat').value);
    const lng = parseFloat(document.getElementById('lng').value);
    const radius = parseInt(document.getElementById('radius').value, 10) || 50000;

    map.setCenter({ lat, lng });
    map.setZoom(9);

    setStatus('Fetching polygons near the point...');

    const url = `/api/forest-cover?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}&radius=${encodeURIComponent(radius)}`;

    try {
        const res = await fetch(url);
        if (!res.ok) {
            const err = await res.json().catch(()=>({error:'unknown'}));
            setStatus('Server error: ' + (err.error || res.statusText));
            console.error('Server error', err);
            return;
        }

        const geojson = await res.json();

        if (!geojson || !geojson.features) {
            setStatus('No features returned.');
            console.warn('Invalid GeoJSON', geojson);
            return;
        }

        // Clear previous data layer
        map.data.forEach(feature => map.data.remove(feature));
        map.data.addGeoJson(geojson);

        map.data.setStyle(feature => {
            const props = feature.getProperty('attributes') || feature.getProperty('properties') || feature.getProperty('Attributes') || null;
            let fillColor = '#FF6B6B';
            let strokeColor = '#A12';
            const keysToCheck = ['LC', 'LABEL', 'class', 'ClassName', 'cover', 'value', 'VALUE', 'label'];
            let known = null;

            for (const k of keysToCheck) {
                let v = feature.getProperty(k) ?? (props && props[k]) ?? null;
                if (v !== null && v !== undefined) { known = v; break; }
            }

            if (known !== null) {
                const s = String(known).toLowerCase();
                if (s.includes('tree') || s.includes('forest') || s.includes('wood') || s.includes('tree_cover')) {
                    fillColor = '#33A02C';
                    strokeColor = '#166A13';
                }
            }

            return { fillColor, strokeColor, strokeWeight: 1, fillOpacity: 0.45 };
        });

        map.data.addListener('click', event => {
            const props = event.feature.getProperty('properties') || event.feature.getProperty('attributes') || {};
            let html = '<div style="max-width:300px"><strong>Feature properties</strong><br><table>';
            if (props && typeof props === 'object') {
                for (const k in props) html += `<tr><td style="font-weight:600">${k}</td><td>${props[k]}</td></tr>`;
            } else {
                const top = event.feature.toJSON();
                html += `<tr><td colspan="2">${JSON.stringify(top.properties || top.attributes || {}, null, 2)}</td></tr>`;
            }
            html += '</table></div>';

            infoWindow.setContent(html);
            const anchor = event.latLng || getFeatureCenter(event.feature, map);
            infoWindow.setPosition(anchor);
            infoWindow.open(map);
        });

        setStatus(`Loaded ${geojson.features.length} features.`);
    } catch (err) {
        console.error('Failed loading polygons', err);
        setStatus('Failed to fetch polygons: ' + err.message);
    }
}

function setStatus(text) {
    document.getElementById('status').innerText = text;
}

function getFeatureCenter(feature, map) {
    const geo = feature.getGeometry();
    if (geo && typeof geo.getArray === 'function') {
        try {
            const paths = geo.getArray ? geo.getArray() : null;
            if (paths && paths.length) {
                const ring = paths[0].getArray ? paths[0].getArray() : paths[0];
                let sx = 0, sy = 0, n = 0;
                for (let i=0;i<ring.length;i++){ sx += ring[i].lat(); sy += ring[i].lng(); n++; }
                return { lat: sx / n, lng: sy / n };
            }
        } catch(e){}
    }
    return map.getCenter();
}
