@extends('layouts.app')

@section('title', 'Detail Pengajuan')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <a href="{{ route('user.submissions.index') }}" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
        <h2 class="fw-bold mb-0">Detail Pengajuan</h2>
        <p class="text-muted mb-0">Informasi lengkap pengajuan Anda</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Main Info Card -->
        <div class="card-custom mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <h4 class="fw-bold mb-0">{{ $submission->judul_kegiatan }}</h4>
                    <span class="badge badge-custom status-{{ $submission->status }} fs-6">
                        {{ $submission->status_label }}
                    </span>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <i class="fas fa-calendar text-primary me-2"></i>
                            <strong>Tanggal Kegiatan:</strong><br>
                            <span class="ms-4">{{ $submission->tanggal_kegiatan->format('d F Y') }}</span>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                            <strong>Lokasi:</strong><br>
                            <span class="ms-4">{{ $submission->lokasi ?? '-' }}</span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2">
                            <i class="fas fa-building text-info me-2"></i>
                            <strong>Unit:</strong><br>
                            <span class="ms-4">{{ $submission->unit }}</span>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-clock text-warning me-2"></i>
                            <strong>Diajukan:</strong><br>
                            <span class="ms-4">{{ $submission->created_at->format('d M Y H:i') }}</span>
                        </p>
                    </div>
                </div>

                @if($submission->deskripsi)
                <div class="mb-3">
                    <h6 class="fw-bold mb-2">Deskripsi Kegiatan:</h6>
                    <p class="text-muted">{{ $submission->deskripsi }}</p>
                </div>
                @endif

                @if($submission->catatan_admin)
                <div class="alert alert-{{ $submission->status === 'rejected' ? 'danger' : 'info' }}">
                    <h6 class="fw-bold mb-2">
                        <i class="fas fa-sticky-note me-2"></i>
                        {{ $submission->status === 'rejected' ? 'Alasan Penolakan' : 'Catatan Admin' }}:
                    </h6>
                    <p class="mb-0">{{ $submission->catatan_admin }}</p>
                </div>
                @endif

                <!-- Action Buttons for Pending -->
                @if($submission->status === 'pending')
                <div class="mt-4">
                    <a href="{{ route('user.submissions.edit', $submission) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i>Edit Pengajuan
                    </a>
                    <form action="{{ route('user.submissions.destroy', $submission) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Yakin ingin menghapus pengajuan ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Hapus Pengajuan
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <!-- Display Results for Completed -->
        @if($submission->status === 'completed' && $submission->hasResults())
        <div class="card-custom mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-camera text-success me-2"></i>
                    Hasil Dokumentasi
                </h5>

                @if($submission->hasil_link_foto)
                <div class="mb-4">
                    <h6 class="fw-semibold mb-3">
                        <i class="fas fa-image text-primary me-2"></i>Link Foto
                    </h6>
                    <div class="list-group">
                        @foreach($submission->hasil_link_foto as $index => $link)
                        <a href="{{ $link }}" target="_blank" class="list-group-item list-group-item-action">
                            <i class="fas fa-external-link-alt me-2 text-primary"></i>Foto {{ $index + 1 }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($submission->hasil_link_video)
                <div class="mb-4">
                    <h6 class="fw-semibold mb-3">
                        <i class="fas fa-video text-danger me-2"></i>Link Video
                    </h6>
                    <div class="list-group">
                        @foreach($submission->hasil_link_video as $index => $link)
                        <a href="{{ $link }}" target="_blank" class="list-group-item list-group-item-action">
                            <i class="fas fa-external-link-alt me-2 text-danger"></i>Video {{ $index + 1 }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($submission->hasil_link_drive)
                <div class="mb-4">
                    <h6 class="fw-semibold mb-3">
                        <i class="fab fa-google-drive text-success me-2"></i>Link Google Drive
                    </h6>
                    <div class="list-group">
                        @foreach($submission->hasil_link_drive as $index => $link)
                        <a href="{{ $link }}" target="_blank" class="list-group-item list-group-item-action">
                            <i class="fas fa-external-link-alt me-2 text-success"></i>Drive {{ $index + 1 }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Status Info -->
        <div class="card-custom mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Status Pengajuan</h5>
                
                @if($submission->status === 'pending')
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-clock me-2"></i>
                    <strong>Menunggu Review</strong><br>
                    <small>Pengajuan Anda sedang menunggu persetujuan dari admin</small>
                </div>
                @elseif($submission->status === 'approved')
                <div class="alert alert-info mb-0">
                    <i class="fas fa-check me-2"></i>
                    <strong>Disetujui</strong><br>
                    <small>Pengajuan telah disetujui. Menunggu upload hasil dokumentasi</small>
                </div>
                @elseif($submission->status === 'completed')
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Selesai</strong><br>
                    <small>Dokumentasi telah selesai dan hasil sudah diupload</small>
                </div>
                @elseif($submission->status === 'rejected')
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>Ditolak</strong><br>
                    <small>Pengajuan ditolak. Lihat alasan penolakan di atas</small>
                </div>
                @endif
            </div>
        </div>

        <!-- Timeline -->
        <div class="card-custom">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Timeline</h5>
                <ul class="timeline">
                    <li class="timeline-item">
                        <i class="fas fa-paper-plane text-primary"></i>
                        <div>
                            <strong>Pengajuan Dibuat</strong>
                            <br><small class="text-muted">{{ $submission->created_at->format('d M Y H:i') }}</small>
                        </div>
                    </li>
                    @if($submission->approved_at)
                    <li class="timeline-item">
                        <i class="fas fa-check text-success"></i>
                        <div>
                            <strong>Disetujui Admin</strong>
                            <br><small class="text-muted">{{ $submission->approved_at->format('d M Y H:i') }}</small>
                        </div>
                    </li>
                    @endif
                    @if($submission->completed_at)
                    <li class="timeline-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <div>
                            <strong>Dokumentasi Selesai</strong>
                            <br><small class="text-muted">{{ $submission->completed_at->format('d M Y H:i') }}</small>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    list-style: none;
    padding-left: 0;
    position: relative;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e2e8f0;
}
.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 20px;
}
.timeline-item i {
    position: absolute;
    left: 0;
    top: 0;
    width: 24px;
    height: 24px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    z-index: 1;
}
</style>
@endpush
@endsection