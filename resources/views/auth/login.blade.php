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
      }
      .nama-pt {
        color: #1f3236 !important;
        font-weight: 900 !important;
      }

      /* === Overlay Loading === */
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
        margin-top: 15px;
        font-weight: bold;
        color: #333;
        font-size: 16px;
      }
    </style>
  </head>
  <body class="d-flex flex-column">
    <script src="{{ asset('tabler/dist/js/demo-theme.min.js') }}"></script>
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          <a href="#" class="navbar-brand">
            <img src="{{ asset($setting->path_logo ?? 'logo.png') }}" height="130" alt="Logo">
          </a>
        </div>
        <div class="card card-md">
          <div class="card-body">
            <h2 class="nama-pt text-center mb-4">
              LOGIN
            </h2>

            {{-- Session status --}}
            @if (session('status'))
              <div class="alert alert-success">
                {{ session('status') }}
              </div>
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
                <input type="text" name="username" class="form-control" placeholder="Enter your username"
                       value="{{ old('username') }}" required autofocus autocomplete="username">
              </div>
              <div class="mb-2">
                <label class="form-label">Password</label>
                <div class="input-group input-group-flat">
                  <input type="password" id="password" name="password" class="form-control"
                         placeholder="Your password" required autocomplete="current-password">
                  <span class="input-group-text">
                    <a href="#" class="link-secondary" title="Show password" id="toggle-password">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                           fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6
                                c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
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
                <a href="{{ route('password.request') }}" class="text-muted">Forgot your password?</a>
              </div>
            @endif
          </div>

          {{-- Branding menyatu di dalam card --}}
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
      // toggle show/hide password
      document.getElementById('toggle-password').addEventListener('click', function(e) {
        e.preventDefault();
        const pw = document.getElementById('password');
        pw.type = pw.type === 'password' ? 'text' : 'password';
      });

      // tampilkan overlay loading saat login diklik
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

          setTimeout(() => {
            this.form.submit();
          }, 300);
        });
      }
    </script>
  </body>
</html>
