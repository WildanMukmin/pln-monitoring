@extends('layouts.app')

@section('title', 'Instagram Scraper - Admin')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-0">Instagram Scraper</h2>
        <p class="text-muted mb-0">Scrape dan kelola data Instagram posts</p>
    </div>
</div>

<!-- Scraper Form -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-body">
                <h5 class="fw-bold mb-3">
                    <i class="fab fa-instagram text-danger me-2"></i>
                    Scrape Instagram Profile
                </h5>

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Cara Kerja:</strong> Scraper ini mengambil data publik dari Instagram. Pastikan akun yang ingin di-scrape bersifat <strong>PUBLIC</strong>. Sistem akan mencoba beberapa metode scraping untuk hasil terbaik.
                </div>

                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Tips:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Gunakan metode "Auto" untuk hasil terbaik</li>
                        <li>Jika gagal, coba lagi dalam beberapa menit</li>
                        <li>Akun private tidak dapat di-scrape</li>
                        <li>Rate limit: Max 10 requests per 5 menit</li>
                    </ul>
                </div>

                <form action="{{ route('admin.scraper.scrape') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Username Instagram <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" 
                                       value="{{ old('username') }}" required placeholder="plnuidlampung">
                            </div>
                            @error('username')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Nama Unit</label>
                            <input type="text" name="unit_name" class="form-control" 
                                   value="{{ old('unit_name', 'Manual') }}" placeholder="UID Lampung">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Kategori</label>
                            <select name="kategori" class="form-select">
                                <option value="Korporat">Korporat</option>
                                <option value="Regional">Regional</option>
                                <option value="Unit">Unit</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Jumlah Post</label>
                            <input type="number" name="limit" class="form-control" 
                                   value="{{ old('limit', 20) }}" min="1" max="50">
                            <small class="text-muted">Maksimal 50 posts</small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Metode Scraping</label>
                            <select name="method" class="form-select">
                                <option value="auto">Auto (Coba Semua)</option>
                                <option value="direct">Direct Instagram</option>
                                <option value="picuki">Via Picuki</option>
                                <option value="imginn">Via Imginn</option>
                            </select>
                            <small class="text-muted">Auto akan mencoba semua metode</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-download me-1"></i>Mulai Scraping
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-custom">
            <div class="card-body">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-chart-bar text-success me-2"></i>
                    Statistik
                </h5>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Total Posts</span>
                    <h4 class="fw-bold mb-0">{{ $posts->total() }}</h4>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.scraper.export') }}" class="btn btn-success btn-sm flex-fill">
                        <i class="fas fa-file-excel me-1"></i>Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Posts Table -->
<div class="card-custom">
    <div class="card-body">
        <h5 class="fw-bold mb-4">
            <i class="fas fa-table text-primary me-2"></i>
            Data Instagram Posts
        </h5>

        @if($posts->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fab fa-instagram fa-4x mb-3 opacity-50"></i>
                <h5>Belum ada data</h5>
                <p>Mulai scraping untuk mengumpulkan data Instagram posts</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul</th>
                            <th>Akun</th>
                            <th>Tipe</th>
                            <th>Unit</th>
                            <th>Likes</th>
                            <th>Comments</th>
                            <th>Views</th>
                            <th>Engagement</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($posts as $post)
                        <tr>
                            <td>{{ $post->tanggal->format('d M Y') }}</td>
                            <td>
                                <div class="fw-semibold">{{ Str::limit($post->judul_pemberitaan, 40) }}</div>
                                <a href="{{ $post->link_pemberitaan }}" target="_blank" class="small text-muted">
                                    <i class="fas fa-external-link-alt me-1"></i>Lihat Post
                                </a>
                            </td>
                            <td>{{ $post->akun }}</td>
                            <td>
                                <span class="badge bg-{{ $post->tipe_konten === 'Reels' ? 'danger' : 'primary' }}">
                                    {{ $post->tipe_konten }}
                                </span>
                            </td>
                            <td>{{ $post->pic_unit }}</td>
                            <td><i class="fas fa-heart text-danger me-1"></i>{{ number_format($post->likes) }}</td>
                            <td><i class="fas fa-comment text-primary me-1"></i>{{ number_format($post->comments) }}</td>
                            <td><i class="fas fa-eye text-info me-1"></i>{{ number_format($post->views) }}</td>
                            <td>
                                <span class="badge bg-success">{{ $post->engagement_rate }}%</span>
                            </td>
                            <td>
                                <form action="{{ route('admin.scraper.destroy', $post) }}" method="POST" 
                                      onsubmit="return confirm('Yakin ingin menghapus data ini?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection