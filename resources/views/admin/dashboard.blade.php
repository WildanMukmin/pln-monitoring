@extends('layouts.app')

@section('title', 'Dashboard Admin - PLN Monitoring')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-0">Dashboard Admin</h2>
        <p class="text-muted">Overview statistik dan pengajuan terbaru</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small mb-1">Total Pengajuan</div>
                    <h3 class="fw-bold mb-0">{{ $stats['total_submissions'] }}</h3>
                </div>
                <div class="stat-icon primary">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small mb-1">Menunggu Review</div>
                    <h3 class="fw-bold mb-0">{{ $stats['pending_submissions'] }}</h3>
                </div>
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small mb-1">Selesai</div>
                    <h3 class="fw-bold mb-0">{{ $stats['completed_submissions'] }}</h3>
                </div>
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small mb-1">Ditolak</div>
                    <h3 class="fw-bold mb-0">{{ $stats['rejected_submissions'] }}</h3>
                </div>
                <div class="stat-icon danger">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card-custom">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-users text-primary me-2"></i>Total Users
                    </h5>
                    <span class="badge bg-primary">{{ $stats['total_users'] }}</span>
                </div>
                <p class="text-muted small mb-0">User terdaftar dalam sistem</p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card-custom">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fab fa-instagram text-danger me-2"></i>Instagram Posts
                    </h5>
                    <span class="badge bg-danger">{{ $stats['total_posts'] }}</span>
                </div>
                <p class="text-muted small mb-0">Data post Instagram yang tersimpan</p>
            </div>
        </div>
    </div>
</div>

<!-- Pending Submissions Table -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-exclamation-circle text-warning me-2"></i>
                        Pengajuan Pending (Butuh Review)
                    </h5>
                    <a href="{{ route('admin.submissions.index') }}" class="btn btn-sm btn-primary-custom">
                        <i class="fas fa-eye me-1"></i>Lihat Semua
                    </a>
                </div>

                @if($pending_submissions->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <p>Tidak ada pengajuan yang menunggu review</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kegiatan</th>
                                    <th>User</th>
                                    <th>Unit</th>
                                    <th>Tanggal</th>
                                    <th>Diajukan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pending_submissions as $submission)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $submission->judul_kegiatan }}</div>
                                        @if($submission->deskripsi)
                                            <small class="text-muted">{{ Str::limit($submission->deskripsi, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <i class="fas fa-user-circle me-1 text-muted"></i>
                                        {{ $submission->user->username }}
                                    </td>
                                    <td>{{ $submission->unit }}</td>
                                    <td>{{ $submission->tanggal_kegiatan->format('d M Y') }}</td>
                                    <td>{{ $submission->created_at->diffForHumans() }}</td>
                                    <td>
                                        <a href="{{ route('admin.submissions.show', $submission) }}" class="btn btn-sm btn-primary-custom">
                                            <i class="fas fa-edit me-1"></i>Review
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Recent Submissions -->
<div class="row g-4">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-body">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-history text-info me-2"></i>
                    Pengajuan Terbaru
                </h5>

                @if($recent_submissions->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <p>Belum ada pengajuan</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kegiatan</th>
                                    <th>User</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_submissions as $submission)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $submission->judul_kegiatan }}</div>
                                    </td>
                                    <td>{{ $submission->user->username }}</td>
                                    <td>{{ $submission->tanggal_kegiatan->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge badge-custom status-{{ $submission->status }}">
                                            {{ $submission->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.submissions.show', $submission) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Detail
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
