@extends('layouts.app')

@section('title', 'Instagram Scraper - Admin')

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-0">Instagram Scraper</h2>
            <p class="text-muted mb-0">Scrape dan kelola data Instagram posts real-time (Tanpa Database)</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Scraper Form -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card-custom">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="fab fa-instagram text-danger me-2"></i>
                        Scrape Instagram Profiles
                    </h5>

                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Cara Kerja:</strong> Pilih akun yang ingin di-scrape, set jumlah post, lalu klik
                        <strong>"Mulai Scraping"</strong>. Data akan tersimpan di session (tidak masuk database).
                    </div>

                    <form action="{{ route('admin.scraper.scrape') }}" method="POST" id="scraper-form">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-users me-1"></i>Pilih Akun Instagram ({{ count($accounts) }} total)
                            </label>

                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                    <i class="fas fa-check-double"></i> Pilih Semua
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                                    <i class="fas fa-times"></i> Hapus Semua
                                </button>
                            </div>

                            <div class="row g-2">
                                @foreach($accounts as $acc)
                                    <div class="col-md-6">
                                        <div class="account-checkbox p-2 rounded">
                                            <div class="form-check">
                                                <input class="form-check-input account-check" type="checkbox" name="accounts[]"
                                                    value="{{ $acc['username'] }}" id="acc_{{ $acc['username'] }}" checked>
                                                <label class="form-check-label w-100" for="acc_{{ $acc['username'] }}">
                                                    <strong>{{ $acc['username'] }}</strong>
                                                    <small class="text-muted d-block">{{ $acc['unit'] }}</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @error('accounts')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-list-ol me-1"></i>Jumlah Post per Akun
                                </label>
                                <input type="number" name="limit" class="form-control @error('limit') is-invalid @enderror"
                                    value="{{ old('limit', 12) }}" min="1" max="50" required>
                                <small class="text-muted">Maksimal 50 posts per akun</small>
                                @error('limit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-custom" id="scrape-btn">
                            <i class="fas fa-download me-1"></i>Mulai Scraping
                        </button>
                    </form>

                    <!-- Progress Indicator -->
                    <div id="progress-container" class="mt-4" style="display:none;">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-spinner fa-spin me-2"></i>Scraping Progress
                        </h6>

                        <div class="progress mb-2" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                role="progressbar" style="width: 100%;">
                                <span>Processing...</span>
                            </div>
                        </div>

                        <p class="text-muted small mb-0">Mohon tunggu, sedang mengambil data dari Instagram...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-custom mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-chart-bar text-success me-2"></i>
                        Statistik
                    </h5>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Total Posts</span>
                        <h4 class="fw-bold mb-0">{{ number_format($stats['total']) }}</h4>
                    </div>
                    @if($stats['total'] > 0)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total Likes</span>
                            <h5 class="fw-bold mb-0 text-danger">{{ number_format($stats['likes']) }}</h5>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total Comments</span>
                            <h5 class="fw-bold mb-0 text-info">{{ number_format($stats['comments']) }}</h5>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total Views</span>
                            <h5 class="fw-bold mb-0 text-warning">{{ number_format($stats['views']) }}</h5>
                        </div>
                        <hr>
                    @endif

                    <div class="d-flex gap-2">
                        @if($stats['total'] > 0)
                            <a href="{{ route('admin.scraper.export') }}" class="btn btn-success btn-sm flex-fill">
                                <i class="fas fa-file-excel me-1"></i>Export CSV
                            </a>
                            <form action="{{ route('admin.scraper.clear') }}" method="POST" class="flex-fill">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm w-100"
                                    onclick="return confirm('Yakin ingin menghapus semua data scraping dari session?')">
                                    <i class="fas fa-trash me-1"></i>Clear
                                </button>
                            </form>
                        @else
                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                <i class="fas fa-file-excel me-1"></i>Export CSV
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Informasi
                    </h5>
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Scraping dari profil publik saja
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Data tersimpan di session (tidak di DB)
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Mendapatkan likes, comments, views
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock text-warning me-2"></i>
                            Proses ~0.5 detik per akun
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                            Hanya untuk akun PUBLIC
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    @if(count($scrapedData) > 0)
        <div class="card-custom">
            <div class="card-body">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-table text-primary me-2"></i>
                    Hasil Scraping ({{ count($scrapedData) }} posts)
                </h5>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Akun</th>
                                <th>Unit</th>
                                <th>Caption</th>
                                <th>Tipe</th>
                                <th>Likes</th>
                                <th>Comments</th>
                                <th>Views</th>
                                <th>Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scrapedData as $data)
                                <tr>
                                    <td>{{ $data['tanggal'] }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $data['username'] }}</div>
                                        <small class="text-muted">{{ $data['kategori'] }}</small>
                                    </td>
                                    <td>{{ $data['unit'] }}</td>
                                    <td>
                                        <div style="max-width: 300px;">
                                            {{ Str::limit($data['caption'], 80) }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $data['tipe'] === 'Reels/Video' ? 'danger' : 'primary' }}">
                                            {{ $data['tipe'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-heart text-danger me-1"></i>
                                        {{ number_format($data['likes']) }}
                                    </td>
                                    <td>
                                        <i class="fas fa-comment text-primary me-1"></i>
                                        {{ number_format($data['comments']) }}
                                    </td>
                                    <td>
                                        <i class="fas fa-eye text-info me-1"></i>
                                        {{ number_format($data['views']) }}
                                    </td>
                                    <td>
                                        <a href="{{ $data['link'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card-custom">
            <div class="card-body text-center py-5">
                <i class="fab fa-instagram fa-4x mb-3 text-muted opacity-50"></i>
                <h5 class="text-muted">Belum ada data scraping</h5>
                <p class="text-muted">Pilih akun dan klik "Mulai Scraping" untuk mengambil data Instagram</p>
            </div>
        </div>
    @endif

    <style>
        .account-checkbox {
            transition: background 0.2s;
        }

        .account-checkbox:hover {
            background: rgba(0, 82, 163, 0.05);
        }

        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: white;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 82, 163, 0.3);
        }
    </style>

    @push('scripts')
        <script>
            function selectAll() {
                document.querySelectorAll('.account-check').forEach(cb => cb.checked = true);
            }

            function deselectAll() {
                document.querySelectorAll('.account-check').forEach(cb => cb.checked = false);
            }

            document.getElementById('scraper-form').addEventListener('submit', function (e) {
                const btn = document.getElementById('scrape-btn');
                const progress = document.getElementById('progress-container');

                // Check if at least one account is selected
                const checked = document.querySelectorAll('.account-check:checked').length;
                if (checked === 0) {
                    e.preventDefault();
                    alert('Pilih minimal 1 akun untuk di-scrape!');
                    return false;
                }

                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Scraping...';
                progress.style.display = 'block';
            });
        </script>
    @endpush
@endsection