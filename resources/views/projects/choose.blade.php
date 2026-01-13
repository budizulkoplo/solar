<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}"/>
    <title>Pilih Project & Solar System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --card-bg: #fff7ed;
            --card-border: #fdba74;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-container {
            width: 100%;
            max-width: 1200px;
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            position: relative;
        }
        .modal-title {
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 32px;
            color: var(--text-primary);
            position: relative;
            padding-bottom: 12px;
        }
        .modal-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .project-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 160px;
            position: relative;
            overflow: hidden;
        }
        .project-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            background: #ffedd5;
        }
        .project-card:hover::before { opacity: 1; }
        .project-icon {
            margin-bottom: 12px;
            color: var(--primary-color);
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 50px;
            width: 50px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.1);
            margin-left: auto;
            margin-right: auto;
        }
        .project-icon img {
            max-width: 100%;
            max-height: 30px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .project-company {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }
        .project-name {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }
        .project-card form {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        .project-card button {
            background: transparent;
            border: none;
            width: 100%;
            height: 100%;
            cursor: pointer;
            outline: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .no-projects {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
        .no-projects i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #d1d5db;
        }
        .logout-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ef4444;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
            z-index: 9999;
        }
        .logout-btn:hover { background: #dc2626; }

        @media (max-width: 600px) {
            .logout-btn {
                top: 10px;
                right: 10px;
                padding: 6px 12px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="modal-container">

        {{-- Tombol Logout --}}
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>

        {{-- Bagian Solar System --}}
        <h2 class="modal-title">Sub Solar System</h2>

        <div class="projects-grid">
            @php
                use Illuminate\Support\Facades\Crypt;
                use Illuminate\Support\Facades\Auth;

                $userId = Auth::id();
                $expires = now()->addMinutes(10)->timestamp;

                // Data dasar
                $payload = $userId . '|' . $expires;

                // Enkripsi atau beri tanda tangan HMAC
                $signature = hash_hmac('sha256', $payload, config('app.key'));

                // Gabung dan encode aman untuk URL
                $token = rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');
            @endphp


            {{-- Project Management (tetap seperti aslinya) --}}
            <div class="project-card">
                <form action="{{ 'https://pm.mentarimultitrada.com/external/' . $token }}" method="GET" target="_blank">
                    <button type="submit">
                        <div class="project-icon"><i class="fas fa-project-diagram"></i></div>
                        <div class="project-company">Solar System</div>
                        <div class="project-name">Project Management</div>
                    </button>
                </form>
            </div>

            <div class="project-card">
                <form action="{{ route('mobile.home') }}" method="GET">
                    <button type="submit">
                        <div class="project-icon">
                            <i class="fas fa-mobile"></i>
                        </div>
                        <div class="project-company">Solar System</div>
                        <div class="project-name">Absensi Mobile</div>
                    </button>
                </form>
            </div>

            {{-- Module lain dari tabel menus --}}
            @forelse($modules as $module)
                <div class="project-card">
                    <form action="{{ route('choose.project.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="module" value="{{ $module->module }}">
                        <button type="submit">
                            <div class="project-icon">
                                <i class="{{ $module->icon ?? 'fas fa-folder' }}"></i>
                            </div>
                            <div class="project-company">Solar System</div>
                            <div class="project-name">{{ ucfirst($module->module) }}</div>
                        </button>
                    </form>
                </div>
            @empty
                <div class="no-projects">
                    <i class="fas fa-folder-open"></i>
                    <p>Tidak ada module Solar System tersedia</p>
                </div>
            @endforelse
        </div>

        {{-- Bagian Pilih Project --}}
        <h2 class="modal-title">Pilih Project</h2>

        <div class="projects-grid">
            @forelse($projects as $project)
                <div class="project-card">
                    <form action="{{ route('choose.project.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $project->id }}">
                        <button type="submit">
                            <div class="project-icon">
                                @if($project->companyUnit->logo)
                                    <img src="{{ asset('storage/' . $project->companyUnit->logo) }}" alt="{{ $project->companyUnit->company_name }}">
                                @else
                                    <i class="fas fa-building"></i>
                                @endif
                            </div>
                            <div class="project-company">{{ $project->companyUnit->company_name }}</div>
                            <div class="project-name">{{ $project->namaproject }}</div>
                            <div class="project-company">{{ $project->retail->namaretail }}</div>
                        </button>
                    </form>
                </div>
            @empty
                <div class="no-projects">
                    <i class="fas fa-folder-open"></i>
                    <p>Tidak ada project yang tersedia</p>
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>
