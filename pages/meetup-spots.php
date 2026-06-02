<?php
$title      = 'Safe Meetup Spots';
$extra_head = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<style>
  .meetup-layout {
    display: grid;
    grid-template-columns: 340px 1fr;
    height: calc(100vh - 64px);
    overflow: hidden;
  }
  .meetup-sidebar {
    background: var(--white);
    border-right: 1px solid var(--gray-200);
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  .meetup-sidebar-header { padding: 1.25rem; border-bottom: 1px solid var(--gray-200); flex-shrink: 0; }
  .meetup-sidebar-header h1 { font-size: 1.1rem; font-weight: 700; margin: 0 0 0.25rem; }
  .meetup-sidebar-header p { font-size: 0.82rem; color: var(--gray-400); margin: 0; line-height: 1.4; }
  .meetup-legend { display: flex; gap: 0.75rem; margin-top: 0.75rem; flex-wrap: wrap; }
  .meetup-legend-item { display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: var(--gray-600); }
  .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
  .meetup-filter { padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--gray-200); flex-shrink: 0; }
  .meetup-filter input,
  .meetup-filter select {
    width: 100%; padding: 0.45rem 0.75rem;
    border: 1.5px solid var(--gray-200); border-radius: var(--radius);
    font-size: 0.875rem; background: var(--gray-100); color: var(--gray-800); font-family: inherit;
  }
  .meetup-filter input:focus,
  .meetup-filter select:focus { outline: none; border-color: var(--primary); }
  .meetup-filter select { margin-top: 0.5rem; }
  .spot-list { overflow-y: auto; flex: 1; }
  .spot-item { padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--gray-200); cursor: pointer; transition: background var(--transition); }
  .spot-item:hover, .spot-item.active { background: var(--gray-100); }
  .spot-item-top { display: flex; align-items: flex-start; gap: 0.6rem; }
  .spot-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #fff; flex-shrink: 0; margin-top: 1px; }
  .spot-icon.mall    { background: #f97316; }
  .spot-icon.police  { background: #3b82f6; }
  .spot-icon.library { background: #8b5cf6; }
  .spot-icon.post    { background: #10b981; }
  .spot-name { font-size: 0.875rem; font-weight: 600; color: var(--gray-800); line-height: 1.3; }
  .spot-meta { font-size: 0.775rem; color: var(--gray-400); margin-top: 0.2rem; }
  .spot-city { display: inline-block; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--primary); margin-top: 0.25rem; }
  #map { height: 100%; z-index: 1; }
  .map-wrapper { position: relative; }
  .map-info-banner {
    position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
    background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg);
    padding: 0.5rem 1rem; font-size: 0.8rem; color: var(--gray-600);
    z-index: 1000; white-space: nowrap; box-shadow: var(--shadow-md); pointer-events: none;
  }
  .map-info-banner i { color: var(--primary); margin-right: 0.35rem; }
  @media (max-width: 768px) {
    .meetup-layout { grid-template-columns: 1fr; grid-template-rows: 280px 1fr; height: auto; }
    .meetup-sidebar { order: 2; border-right: none; border-top: 1px solid var(--gray-200); height: 420px; }
    #map { height: 280px; }
    .map-wrapper { order: 1; }
  }
</style>
<body class="page-wrap">

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="meetup-layout">

    <aside class="meetup-sidebar">
      <div class="meetup-sidebar-header">
        <h1><i class="fa-solid fa-shield-halved" style="color:var(--primary)"></i> Safe Meetup Spots</h1>
        <p>All locations are verified public venues — shopping centres, police stations, post offices, and libraries — chosen for visibility and safety.</p>
        <div class="meetup-legend">
          <div class="meetup-legend-item"><div class="legend-dot" style="background:#f97316"></div> Shopping Centre</div>
          <div class="meetup-legend-item"><div class="legend-dot" style="background:#3b82f6"></div> Police Station</div>
          <div class="meetup-legend-item"><div class="legend-dot" style="background:#8b5cf6"></div> Library</div>
          <div class="meetup-legend-item"><div class="legend-dot" style="background:#10b981"></div> Post Office</div>
        </div>
      </div>
      <div class="meetup-filter">
        <input type="text" id="spot-search" placeholder="Search spots…">
        <select id="city-filter">
          <option value="">All Cities</option>
          <option value="Johannesburg">Johannesburg</option>
          <option value="Cape Town">Cape Town</option>
          <option value="Durban">Durban</option>
          <option value="Pretoria">Pretoria</option>
          <option value="Port Elizabeth">Gqeberha / Port Elizabeth</option>
          <option value="Bloemfontein">Bloemfontein</option>
        </select>
      </div>
      <div class="spot-list" id="spot-list"></div>
    </aside>

    <div class="map-wrapper">
      <div class="map-info-banner"><i class="fa-solid fa-circle-info"></i> Click a spot on the map or in the list for details</div>
      <div id="map"></div>
    </div>

  </div>

  <script src="/swift-swap/js/main.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const SPOTS = [
      // Johannesburg
      { id: 1,  name: 'Sandton City Mall',              city: 'Johannesburg',   type: 'mall',    lat: -26.1076, lng: 28.0567, address: '83 Rivonia Rd, Sandton',               hours: 'Mon–Sun 09:00–21:00' },
      { id: 2,  name: 'Rosebank Mall',                  city: 'Johannesburg',   type: 'mall',    lat: -26.1468, lng: 28.0418, address: 'Bath Ave & Cradock Ave, Rosebank',      hours: 'Mon–Sun 09:00–21:00' },
      { id: 3,  name: 'Eastgate Shopping Centre',       city: 'Johannesburg',   type: 'mall',    lat: -26.1735, lng: 28.1042, address: '43 Bradford Rd, Bedfordview',           hours: 'Mon–Sat 09:00–19:00, Sun 09:00–17:00' },
      { id: 4,  name: 'Cresta Shopping Centre',         city: 'Johannesburg',   type: 'mall',    lat: -26.1351, lng: 27.9649, address: 'Beyers Naudé Dr, Cresta',               hours: 'Mon–Sat 09:00–19:00, Sun 09:00–17:00' },
      { id: 5,  name: 'Sandton Police Station',         city: 'Johannesburg',   type: 'police',  lat: -26.1037, lng: 28.0522, address: '281 West St, Sandton',                  hours: '24 hours' },
      { id: 6,  name: 'Johannesburg Central Library',   city: 'Johannesburg',   type: 'library', lat: -26.2034, lng: 28.0468, address: 'Market St, Johannesburg CBD',           hours: 'Mon–Fri 08:00–17:30, Sat 09:00–13:00' },
      { id: 7,  name: 'Johannesburg GPO (Post Office)', city: 'Johannesburg',   type: 'post',    lat: -26.2053, lng: 28.0412, address: 'Rissik St, Johannesburg CBD',           hours: 'Mon–Fri 08:00–16:30, Sat 08:00–12:00' },
      // Cape Town
      { id: 8,  name: 'V&A Waterfront',                 city: 'Cape Town',      type: 'mall',    lat: -33.9043, lng: 18.4201, address: 'Dock Rd, V&A Waterfront',               hours: 'Mon–Sun 09:00–21:00' },
      { id: 9,  name: 'Canal Walk Shopping Centre',     city: 'Cape Town',      type: 'mall',    lat: -33.8888, lng: 18.5143, address: 'Century Blvd, Century City',            hours: 'Mon–Sun 09:00–21:00' },
      { id: 10, name: 'Cavendish Square',               city: 'Cape Town',      type: 'mall',    lat: -33.9985, lng: 18.4680, address: 'Dreyer St, Claremont',                  hours: 'Mon–Sat 09:00–19:00, Sun 10:00–17:00' },
      { id: 11, name: 'Cape Town Central Police',       city: 'Cape Town',      type: 'police',  lat: -33.9258, lng: 18.4232, address: 'Buitenkant St, Cape Town CBD',          hours: '24 hours' },
      { id: 12, name: 'Cape Town City Library',         city: 'Cape Town',      type: 'library', lat: -33.9254, lng: 18.4178, address: 'Darling St, Cape Town CBD',             hours: 'Mon–Fri 08:30–17:00, Sat 09:00–13:00' },
      // Durban
      { id: 13, name: 'Gateway Theatre of Shopping',    city: 'Durban',         type: 'mall',    lat: -29.7285, lng: 31.0736, address: '1 Palm Blvd, Umhlanga Ridge',           hours: 'Mon–Sun 09:00–21:00' },
      { id: 14, name: 'The Pavilion Shopping Centre',   city: 'Durban',         type: 'mall',    lat: -29.8594, lng: 30.9433, address: 'Jack Martens Dr, Westville',            hours: 'Mon–Sat 09:00–19:00, Sun 09:00–17:00' },
      { id: 15, name: 'Durban Central Police Station',  city: 'Durban',         type: 'police',  lat: -29.8553, lng: 31.0228, address: '191 Stanger St, Durban CBD',            hours: '24 hours' },
      { id: 16, name: 'Durban City Library',            city: 'Durban',         type: 'library', lat: -29.8568, lng: 31.0217, address: 'Anton Lembede St, Durban CBD',          hours: 'Mon–Fri 08:00–17:00, Sat 08:30–12:30' },
      // Pretoria
      { id: 17, name: 'Menlyn Park Shopping Centre',    city: 'Pretoria',       type: 'mall',    lat: -25.7822, lng: 28.2773, address: 'Atterbury Rd & Lois Ave, Menlyn',       hours: 'Mon–Sun 09:00–21:00' },
      { id: 18, name: 'Brooklyn Mall',                  city: 'Pretoria',       type: 'mall',    lat: -25.7727, lng: 28.2280, address: 'Veale St, Brooklyn, Pretoria',          hours: 'Mon–Sat 09:00–19:00, Sun 10:00–17:00' },
      { id: 19, name: 'Pretoria Central Police',        city: 'Pretoria',       type: 'police',  lat: -25.7463, lng: 28.1876, address: 'Pretorius St, Pretoria CBD',            hours: '24 hours' },
      { id: 20, name: 'Pretoria City Library',          city: 'Pretoria',       type: 'library', lat: -25.7467, lng: 28.1877, address: 'Kerk St, Pretoria CBD',                 hours: 'Mon–Fri 08:00–17:30, Sat 08:00–12:00' },
      // Port Elizabeth / Gqeberha
      { id: 21, name: 'Greenacres Shopping Centre',     city: 'Port Elizabeth', type: 'mall',    lat: -33.9651, lng: 25.5765, address: 'Cape Rd, Greenacres, Gqeberha',         hours: 'Mon–Sat 09:00–19:00, Sun 09:00–15:00' },
      { id: 22, name: 'The Bridge Shopping Centre',     city: 'Port Elizabeth', type: 'mall',    lat: -33.9584, lng: 25.5706, address: 'Govan Mbeki Ave, Gqeberha',             hours: 'Mon–Sat 09:00–18:00' },
      { id: 23, name: 'Gqeberha Central Police',        city: 'Port Elizabeth', type: 'police',  lat: -33.9592, lng: 25.6114, address: 'Russell Rd, Central, Gqeberha',         hours: '24 hours' },
      // Bloemfontein
      { id: 24, name: 'Mimosa Mall',                    city: 'Bloemfontein',   type: 'mall',    lat: -29.1227, lng: 26.2060, address: 'Kellner St, Westdene, Bloemfontein',    hours: 'Mon–Sat 09:00–18:00, Sun 09:00–15:00' },
      { id: 25, name: 'Bloemfontein Central Police',    city: 'Bloemfontein',   type: 'police',  lat: -29.1202, lng: 26.2074, address: 'Charlotte Maxeke St, Bloemfontein CBD', hours: '24 hours' },
    ];

    const TYPE_COLORS = { mall: '#f97316', police: '#3b82f6', library: '#8b5cf6', post: '#10b981' };
    const TYPE_ICONS  = { mall: 'fa-bag-shopping', police: 'fa-shield', library: 'fa-book', post: 'fa-envelope' };
    const TYPE_LABELS = { mall: 'Shopping Centre', police: 'Police Station', library: 'Library', post: 'Post Office' };

    const map = L.map('map', { zoomControl: true }).setView([-28.5, 25.5], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    }).addTo(map);

    function makeIcon(type) {
      const color = TYPE_COLORS[type];
      const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 42" width="32" height="42">
        <path fill="${color}" stroke="#fff" stroke-width="1.5" d="M16 2C9.37 2 4 7.37 4 14c0 9.5 12 26 12 26S28 23.5 28 14C28 7.37 22.63 2 16 2z"/>
        <circle fill="#fff" cx="16" cy="14" r="6"/>
      </svg>`;
      return L.divIcon({ html: svg, iconSize: [32, 42], iconAnchor: [16, 42], popupAnchor: [0, -40], className: '' });
    }

    const markers = {};
    SPOTS.forEach(spot => {
      const m = L.marker([spot.lat, spot.lng], { icon: makeIcon(spot.type) }).addTo(map);
      m.bindPopup(`
        <div style="min-width:180px">
          <strong style="font-size:0.9rem">${spot.name}</strong><br>
          <span style="font-size:0.78rem;color:#666">${TYPE_LABELS[spot.type]} · ${spot.city}</span><br>
          <hr style="margin:6px 0">
          <span style="font-size:0.78rem"><i class="fa-solid fa-location-dot"></i> ${spot.address}</span><br>
          <span style="font-size:0.78rem"><i class="fa-solid fa-clock"></i> ${spot.hours}</span>
        </div>
      `);
      m.on('click', () => highlightSpot(spot.id));
      markers[spot.id] = m;
    });

    function renderList(filtered) {
      const list = document.getElementById('spot-list');
      if (!filtered.length) {
        list.innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--gray-400);font-size:0.875rem">No spots found</div>';
        return;
      }
      list.innerHTML = filtered.map(s => `
        <div class="spot-item" id="item-${s.id}" data-id="${s.id}">
          <div class="spot-item-top">
            <div class="spot-icon ${s.type}"><i class="fa-solid ${TYPE_ICONS[s.type]}"></i></div>
            <div>
              <div class="spot-name">${s.name}</div>
              <div class="spot-meta">${s.address}</div>
              <div class="spot-meta">${s.hours}</div>
              <span class="spot-city">${s.city}</span>
            </div>
          </div>
        </div>`).join('');

      list.querySelectorAll('.spot-item').forEach(el => {
        el.addEventListener('click', () => {
          const id = +el.dataset.id;
          highlightSpot(id);
          const s = SPOTS.find(x => x.id === id);
          map.setView([s.lat, s.lng], 15, { animate: true });
          markers[id].openPopup();
        });
      });
    }

    function highlightSpot(id) {
      document.querySelectorAll('.spot-item').forEach(el => el.classList.remove('active'));
      const el = document.getElementById(`item-${id}`);
      if (el) { el.classList.add('active'); el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
    }

    function applyFilters() {
      const q    = document.getElementById('spot-search').value.toLowerCase();
      const city = document.getElementById('city-filter').value;
      renderList(SPOTS.filter(s =>
        (!city || s.city === city) &&
        (!q || s.name.toLowerCase().includes(q) || s.city.toLowerCase().includes(q) || s.address.toLowerCase().includes(q))
      ));
    }

    document.getElementById('spot-search').addEventListener('input', applyFilters);
    document.getElementById('city-filter').addEventListener('change', applyFilters);

    renderList(SPOTS);
  </script>
</body>
</html>
