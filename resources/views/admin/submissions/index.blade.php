@extends('layouts.app')

@section('title', 'Semua Pengajuan - Admin')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Semua Pengajuan</h2>
                <p class="text-muted mb-0">Kelola semua pengajuan dokumentasi dari user</p>
            </div>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card-custom mb-4">
    <div class="card-body">
        <ul class="nav nav-pills mb-0">
            <li class="nav-item">
                <a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('admin.submissions.index') }}">
                    Semua ({{ $submissions->total() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status') == 'pending' ? 'active' : '' }}" href="{{ route('admin.submissions.index', ['status' => 'pending']) }}">
                    Pending
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status') == 'approved' ? 'active' : '' }}" href="{{ route('admin.submissions.index', ['status' => 'approved']) }}">
                    Disetujui
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status') == 'completed' ? 'active' : '' }}" href="{{ route('admin.submissions.index', ['status' => 'completed']) }}">
                    Selesai
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status') == 'rejected' ? 'active' : '' }}" href="{{ route('admin.submissions.index', ['status' => 'rejected']) }}">
                    Ditolak
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Submissions Table -->
<div class="card-custom">
    <div class="card-body">
        @if($submissions->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-4x mb-3 opacity-50"></i>
                <h5>Tidak ada pengajuan</h5>
                <p>Belum ada pengajuan yang masuk</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Kegiatan</th>
                            <th>User</th>
                            <th>Unit</th>
                            <th>Tanggal Kegiatan</th>
                            <th>Status</th>
                            <th>Diajukan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $submission)
                        <tr>
                            <td class="text-muted">#{{ $submission->id }}</td>
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
                            <td>
                                <span class="badge badge-custom status-{{ $submission->status }}">
                                    {{ $submission->status_label }}
                                </span>
                            </td>
                            <td>{{ $submission->created_at->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('admin.submissions.show', $submission) }}" class="btn btn-sm btn-primary-custom">
                                    <i class="fas fa-eye me-1"></i>Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $submissions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection