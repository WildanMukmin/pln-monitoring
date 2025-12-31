<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PLN Monitoring')</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Header */
        .app-header {
            background: linear-gradient(135deg, #001a2e 0%, #0a3a52 40%, #0052a3 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0,82,163,0.15);
        }

        .app-logo {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .app-logo i {
            color: #fbbf24;
        }

        /* Navbar */
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 0.75rem 0;
        }

        .navbar-custom .nav-link {
            color: #334155;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link.active {
            background: linear-gradient(135deg, #0052a3 0%, #0a3a52 100%);
            color: white;
        }

        /* Content */
        .content-wrapper {
            padding: 2rem 0;
            min-height: calc(100vh - 300px);
        }

        /* Cards */
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card-custom:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        /* Stats Card */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .stat-card.primary { border-color: #0052a3; }
        .stat-card.warning { border-color: #fbbf24; }
        .stat-card.success { border-color: #10b981; }
        .stat-card.danger { border-color: #ef4444; }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.primary { background: rgba(0,82,163,0.1); color: #0052a3; }
        .stat-icon.warning { background: rgba(251,191,36,0.1); color: #fbbf24; }
        .stat-icon.success { background: rgba(16,185,129,0.1); color: #10b981; }
        .stat-icon.danger { background: rgba(239,68,68,0.1); color: #ef4444; }

        /* Buttons */
        .btn-custom {
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #0052a3 0%, #0a3a52 100%);
            border: none;
            color: white;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #003a75 0%, #082e3f 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,82,163,0.3);
        }

        /* Footer */
        .app-footer {
            background: linear-gradient(135deg, #0a2342 0%, #001a2e 100%);
            color: white;
            padding: 2rem 0;
            margin-top: 4rem;
            border-top: 3px solid #fbbf24;
        }

        /* Alerts */
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
        }

        /* Table */
        .table-custom {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .table-custom thead {
            background: linear-gradient(135deg, #0052a3 0%, #0a3a52 100%);
            color: white;
        }

        /* Badge */
        .badge-custom {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 600;
        }

        /* Status Badges */
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Header -->
    <div class="app-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="app-logo">
                    <i class="fas fa-bolt"></i>
                    <span>PLN MONITORING</span>
                </div>
                <div class="text-end">
                    <div class="small opacity-75">{{ auth()->user()->unit }}</div>
                    <div class="fw-bold">{{ auth()->user()->username }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    @if(auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-home me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.submissions.*') ? 'active' : '' }}" href="{{ route('admin.submissions.index') }}">
                                <i class="fas fa-clipboard-list me-1"></i> Pengajuan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.scraper.*') ? 'active' : '' }}" href="{{ route('admin.scraper.index') }}">
                                <i class="fab fa-instagram me-1"></i> Instagram Scraper
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}" href="{{ route('user.dashboard') }}">
                                <i class="fas fa-home me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('user.submissions.*') ? 'active' : '' }}" href="{{ route('user.submissions.index') }}">
                                <i class="fas fa-clipboard-list me-1"></i> Pengajuan Saya
                            </a>
                        </li>
                    @endif
                </ul>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- Footer -->
    <footer class="app-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3">PLN UID LAMPUNG</h5>
                    <p class="mb-0 small opacity-75">Digital Monitoring System v2.0</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5 class="fw-bold mb-3">Support</h5>
                    <p class="mb-0 small opacity-75">Contact Admin untuk bantuan teknis</p>
                </div>
            </div>
            <hr class="my-4 opacity-25">
            <div class="text-center small opacity-75">
                <p class="mb-0">&copy; 2025 PT PLN (Persero) UID Lampung. All rights reserved.</p>
                <p class="mb-0 mt-1">Sistem ini dilindungi dan digunakan secara internal.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>
