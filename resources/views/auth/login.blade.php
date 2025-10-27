<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>LOGIN {{ $setting->nama_perusahaan ?? 'Perusahaan' }}</title>

    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}"/>

    <link href="{{ asset('tabler/dist/css/tabler.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/dist/css/tabler-flags.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/dist/css/tabler-payments.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/dist/css/tabler-vendors.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('tabler/dist/css/demo.min.css') }}" rel="stylesheet"/>

    <style>
      @import url('https://rsms.me/inter/inter.css');
      :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }

      body {
        font-feature-settings: "cv03", "cv04", "cv11";
        background-color: #f5f7fb;
        opacity: 0;
        transition: opacity 0.8s ease;
      }
      body.loaded {
        opacity: 1;
      }

      .nama-pt {
        color: #1f3236 !important;
        font-weight: 900 !important;
      }

      /* === Splash Screen Awal === */
      #splashScreen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #ffffff;
        z-index: 10000;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        transition: opacity 0.8s ease;
      }

      #splashScreen img {
        width: 160px;
        height: auto;
        opacity: 1;
        transition: opacity 0.6s ease-in-out;
      }

      #splashScreen p {
        position: absolute;
        bottom: 40px;
        left: 0;
        width: 100%;
        text-align: center;
        color: #949494ff;
        font-weight: 600;
        font-size: 15px;
        opacity: 0.8;
      }

      /* === Overlay Loading Saat Klik Login === */
      #loadingOverlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.92);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        flex-direction: column;
      }

      #loadingOverlay img {
        width: 130px;
        height: auto;
      }

      #loadingOverlay p {
        position: absolute;
        bottom: 40px;
        left: 0;
        width: 100%;
        text-align: center;
        font-weight: bold;
        color: #a0a0a0ff;
        font-size: 16px;
      }

      /* === Pastikan form tetap center vertikal === */
      .page.page-center {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
      }
    </style>
  </head>

  <body class="d-flex flex-column">
    <!-- Splash screen -->
    <div id="splashScreen">
      <img src="{{ asset('animsolar.gif') }}" alt="Splash Animation">
      <p>Memuat sistem...</p>
    </div>

    <script src="{{ asset('tabler/dist/js/demo-theme.min.js') }}"></script>

    <!-- Halaman Login -->
    <div class="page page-center" id="loginPage" style="display:none;">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          <a href="#" class="navbar-brand">
            <img src="{{ asset($setting->path_logo ?? 'logo.png') }}" height="130" alt="Logo">
          </a>
        </div>

        <div class="card card-md shadow-sm">
          <div class="card-body">
            <h2 class="nama-pt text-center mb-4">LOGIN</h2>

            {{-- Session status --}}
            @if (session('status'))
              <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            {{-- Validation Errors --}}
            @if ($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form method="POST" action="{{ route('login') }}" autocomplete="off">
              @csrf
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username"
                       value="{{ old('username') }}" required autofocus autocomplete="username">
              </div>

              <div class="mb-2">
                <label class="form-label">Password</label>
                <div class="input-group input-group-flat">
                  <input type="password" id="password" name="password" class="form-control"
                         placeholder="Masukkan password" required autocomplete="current-password">
                  <span class="input-group-text">
                    <a href="#" class="link-secondary" title="Tampilkan password" id="toggle-password">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                           fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M21 12c-2.4 4 -5.4 6 -9 6
                                c-3.6 0 -6.6 -2 -9 -6
                                c2.4 -4 5.4 -6 9 -6
                                c3.6 0 6.6 2 9 6" />
                      </svg>
                    </a>
                  </span>
                </div>
              </div>

              <div class="form-footer">
                <button type="submit" id="loginBtn" class="btn btn-primary w-100">
                  Login
                </button>
              </div>
            </form>

            @if (Route::has('password.request'))
              <div class="text-center mt-3">
                <a href="{{ route('password.request') }}" class="text-muted">Lupa password?</a>
              </div>
            @endif
          </div>

          <div class="card-footer text-center py-3" style="background-color:#ecf2f8;">
            <img src="{{ asset('piclogo.png') }}" alt="Developer Logo" height="40">
            <p class="text-muted small mb-0 mt-2">
              &copy; PartnerInCode Project {{ date('Y') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Overlay Loading -->
    <div id="loadingOverlay">
      <img src="{{ asset('animsolar.gif') }}" alt="Loading...">
      <p>Memproses login...</p>
    </div>

    <script src="{{ asset('tabler/dist/js/tabler.min.js') }}" defer></script>
    <script>
      // === Splash animation fade out ===
      window.addEventListener('load', () => {
        const splash = document.getElementById('splashScreen');
        const loginPage = document.getElementById('loginPage');
        document.body.classList.add('loaded');

        // tunggu 1.8 detik lalu sembunyikan splash
        setTimeout(() => {
          splash.style.opacity = '0';
          setTimeout(() => {
            splash.style.display = 'none';
            loginPage.style.display = 'flex';
          }, 800);
        }, 3000);
      });

      // === Toggle show/hide password ===
      document.getElementById('toggle-password').addEventListener('click', function(e) {
        e.preventDefault();
        const pw = document.getElementById('password');
        pw.type = pw.type === 'password' ? 'text' : 'password';
      });

      // === Overlay loading saat login ===
      const loginBtn = document.getElementById('loginBtn');
      const loadingOverlay = document.getElementById('loadingOverlay');

      if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
          e.preventDefault();
          loadingOverlay.style.display = 'flex';
          this.disabled = true;
          this.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Memproses...
          `;
          setTimeout(() => this.form.submit(), 300);
        });
      }
    </script>
  </body>
</html>
