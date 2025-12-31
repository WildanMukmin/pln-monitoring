@extends('layouts.app')

@section('title', 'Pengajuan Saya - PLN Monitoring')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Pengajuan Saya</h2>
                <p class="text-muted mb-0">Daftar semua pengajuan dokumentasi Anda</p>
            </div>
            <a href="{{ route('user.submissions.create') }}" class="btn btn-primary-custom">
                <i class="fas fa-plus me-2"></i>Buat Pengajuan Baru
            </a>
        </div>
    </div>
</div>

<div class="card-custom">
    <div class="card-body">
        @if($submissions->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-4x mb-3 opacity-50"></i>
                <h5>Belum ada pengajuan</h5>
                <p>Buat pengajuan dokumentasi pertama Anda</p>
                <a href="{{ route('user.submissions.create') }}" class="btn btn-primary-custom">
                    <i class="fas fa-plus me-2"></i>Buat Pengajuan
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kegiatan</th>
                            <th>Tanggal</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th>Diajukan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $submission)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $submission->judul_kegiatan }}</div>
                                @if($submission->deskripsi)
                                    <small class="text-muted">{{ Str::limit($submission->deskripsi, 60) }}</small>
                                @endif
                            </td>
                            <td>{{ $submission->tanggal_kegiatan->format('d M Y') }}</td>
                            <td>{{ $submission->lokasi ?? '-' }}</td>
                            <td>
                                <span class="badge badge-custom status-{{ $submission->status }}">
                                    {{ $submission->status_label }}
                                </span>
                            </td>
                            <td>{{ $submission->created_at->diffForHumans() }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('user.submissions.show', $submission) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($submission->status === 'pending')
                                        <a href="{{ route('user.submissions.edit', $submission) }}" class="btn btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('user.submissions.destroy', $submission) }}" method="POST" 
                                              onsubmit="return confirm('Yakin ingin menghapus pengajuan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
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
