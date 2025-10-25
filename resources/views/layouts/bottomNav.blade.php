<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Drawer Menu</title>
  <style>
    body {
      margin: 0;
      font-family: sans-serif;
      padding-bottom: 80px; /* space for bottom bar */
    }

    .quickMenuBar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      height: 70px;
      background: #fff;
      box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-around;
      align-items: center;
      z-index: 1001;
      padding: 0 10px;
    }

    .quick-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      font-size: 12px;
      color: #555;
      text-decoration: none;
      background: none;
      border: none;
      cursor: pointer;
    }

    .quick-item.active,
    .quick-item:hover {
      color: #0054a6;
    }

    .quick-item ion-icon {
      font-size: 22px;
      transition: transform 0.3s ease;
    }

    .menu-main {
      background: #0054a6;
      color: white;
      padding: 10px;
      border-radius: 50%;
      transform: translateY(-20%);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
      font-size: 18px;
    }

    .logout {
      color: red !important;
    }

    .logout ion-icon {
      color: red !important;
    }

    .bottomMenuOverlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.3);
      z-index: 1000;
      display: none;
    }

    .bottomMenuOverlay.active {
      display: block;
    }

    .bottomMenuDrawer {
      position: fixed;
      left: 0;
      right: 0;
      bottom: -100%;
      background: #fff;
      transition: bottom 0.3s ease;
      z-index: 1001;
      padding-top: 40px;
      border-top-left-radius: 20px;
      border-top-right-radius: 20px;
      box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.2);
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .bottomMenuDrawer.active {
      bottom: 0;
    }

    .drawerCloseArrow {
      position: absolute;
      top: -25px;
      left: 50%;
      transform: translateX(-50%);
      background: #ffffff;
      border: none;
      border-radius: 30px;
      padding: 6px 12px;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
      cursor: pointer;
      z-index: 1002;
      animation: pulseDown 1.5s infinite;
    }

    .drawerCloseArrow ion-icon {
      font-size: 20px;
      color: #007bff;
    }

    @keyframes pulseDown {
      0%, 100% { transform: translateX(-50%) translateY(0); }
      50% { transform: translateX(-50%) translateY(5px); }
    }

    .drawerContent {
      overflow-y: auto;
      width: 100%;
      padding: 0 20px 30px;
      flex: 1;
      -webkit-overflow-scrolling: touch;
    }

    .bottomMenuGrid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 25px;
      max-width: 500px;
      margin: 0 auto;
    }

    .item {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      color: #333;
      font-size: 13px;
    }

    .item .col {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      text-align: center;
    }

    .item ion-icon {
      font-size: 26px;
      color: #333;
    }

    .item.active ion-icon,
    .item.active .col {
      color: #0054a6;
    }

    .item.logout ion-icon,
    .item.logout .col {
      color: red !important;
    }
    
  </style>
</head>
<body>

<!-- Bottom Quick Menu -->
<div class="quickMenuBar">
  <a href="/mobile/home" class="quick-item {{ request()->is('mobile/home') ? 'active' : '' }}">
    <ion-icon name="home-outline"></ion-icon>
    <span>Home</span>
  </a>
  <a href="/mobile/presensi/create" class="quick-item {{ request()->is('/presensi/create') ? 'active' : '' }}">
    <ion-icon name="finger-print-outline"></ion-icon>
    <span>Absensi</span>
  </a>

  <button class="quick-item menu-main" id="menuToggle">
    <ion-icon id="menuIcon" name="apps-outline"></ion-icon>
  </button>

  <a href="/mobile/kalender" class="quick-item {{ request()->is('/kalender') ? 'active' : '' }}">
    <ion-icon name="calendar-outline"></ion-icon>
    <span>Kalender</span>
  </a>

  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="all: unset; display: contents;">
    @csrf
    <button type="submit" class="quick-item logout">
      <ion-icon name="exit-outline"></ion-icon>
      <span>Logout</span>
    </button>
  </form>
</div>

<!-- Overlay -->
<div class="bottomMenuOverlay" id="menuOverlay"></div>

<!-- Drawer -->
<div class="bottomMenuDrawer" id="menuDrawer">
  <div class="drawerCloseArrow" id="drawerClose">
    <ion-icon class="text-primary" name="chevron-down-outline"></ion-icon>
  </div>
  <div class="drawerContent">
    <div class="bottomMenuGrid">
        @foreach($drawerMenus as $menu)
          <a href="{{ $menu->link }}" class="item {{ request()->is(ltrim($menu->link, '/')) ? 'active' : '' }}">
            <div class="col">
              <ion-icon name="{{ $menu->icon }}"></ion-icon>
              <strong>{{ $menu->namamenu }}</strong>
            </div>
          </a>
        @endforeach

        <a href="{{ url('/choose-project') }}" style="all: unset; display: contents; cursor: pointer;">
            <div class="item">
                <div class="col">
                    <img src="{{ asset('logo.png') }}" alt="Logo" style="width: 32px; height: 32px;">
                    <strong>SOLAR</strong>
                </div>
            </div>
        </a>

      <form id="logout-form" action="{{ route('logout') }}" method="POST" style="all: unset; display: contents;">
        @csrf
        <button type="submit" style="all: unset; cursor: pointer;" class="item logout">
          <div class="col">
            <ion-icon name="exit-outline"></ion-icon>
            <strong>Logout</strong>
          </div>
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Ionicons -->
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

<!-- Script -->
<script>
  const toggle = document.getElementById('menuToggle');
  const drawer = document.getElementById('menuDrawer');
  const overlay = document.getElementById('menuOverlay');
  const closeBtn = document.getElementById('drawerClose');
  const menuIcon = document.getElementById('menuIcon');

  function openDrawer() {
    drawer.classList.add('active');
    overlay.classList.add('active');
    menuIcon.setAttribute('name', 'chevron-down-outline');
  }

  function closeDrawer() {
    drawer.classList.remove('active');
    overlay.classList.remove('active');
    menuIcon.setAttribute('name', 'apps-outline');
  }

  function toggleDrawer() {
    if (drawer.classList.contains('active')) {
      closeDrawer();
    } else {
      openDrawer();
    }
  }

  toggle.addEventListener('click', toggleDrawer);
  overlay.addEventListener('click', closeDrawer);
  closeBtn.addEventListener('click', closeDrawer);

  // Swipe-down gesture
  let startY = 0;
  drawer.addEventListener('touchstart', e => {
    startY = e.touches[0].clientY;
  });
  drawer.addEventListener('touchend', e => {
    const endY = e.changedTouches[0].clientY;
    if (endY - startY > 40) {
      closeDrawer();
    }
  });
</script>

</body>
</html>
