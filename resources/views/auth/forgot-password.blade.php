<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>RESET PASSWORD {{ $setting->nama_perusahaan ?? 'Perusahaan' }}</title>

    <link rel="icon" type="image/png" href="{{ asset($setting->path_logo ?? 'logo.png') }}"/>

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
    </style>
  </head>
  <body class="d-flex flex-column">
    <script src="{{ asset('tabler/dist/js/demo-theme.min.js') }}"></script>
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          <a href="#" class="navbar-brand">
            <img src="{{ asset($setting->path_logo ?? 'logo.png') }}" height="100" alt="Logo">
          </a>
        </div>
        <div class="card card-md">
          <div class="card-body">
            <h2 class="nama-pt text-center mb-4">
              RESET PASSWORD
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

            <p class="text-muted text-center mb-4">
              Masukkan email Anda. Kami akan mengirimkan link untuk reset password.
            </p>

            <form method="POST" action="{{ route('password.email') }}" autocomplete="off">
              @csrf
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email"
                       name="email"
                       class="form-control"
                       placeholder="Alamat email anda"
                       value="{{ old('email') }}"
                       required autofocus>
              </div>

              <div class="form-footer">
                <button type="submit" id="resetBtn" class="btn btn-primary w-100">
                  Kirim Link Reset Password
                </button>
              </div>
            </form>

            <div class="text-center mt-3">
              <a href="{{ route('login') }}" class="text-muted">Kembali ke login</a>
            </div>
          </div>

          {{-- Branding menyatu di dalam card --}}
          <div class="card-footer text-center py-3" style="background-color:#ecf2f8;">
            <img src="{{ asset('piclogo.png') }}" alt="Developer Logo" height="40">
            <p class="text-muted small mb-0 mt-2">
              &copy; Solar System <b>ERP</b> Project {{ date('Y') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <script src="{{ asset('tabler/dist/js/tabler.min.js') }}" defer></script>
    <script>
      // cegah double submit
      const resetBtn = document.getElementById('resetBtn');
      if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
          this.disabled = true;
          this.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Mengirim...
          `;
          this.form.submit();
        });
      }
    </script>
  </body>
</html>
