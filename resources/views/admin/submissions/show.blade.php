@extends('layouts.app')

@section('title', 'Detail Pengajuan - Admin')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <a href="{{ route('admin.submissions.index') }}" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
        <h2 class="fw-bold mb-0">Detail Pengajuan</h2>
        <p class="text-muted mb-0">Review dan kelola pengajuan dokumentasi</p>
    </div>
</div>

<div class="row g-4">
    <!-- Main Info -->
    <div class="col-lg-8">
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
                            <strong>Tanggal Kegiatan:</strong> {{ $submission->tanggal_kegiatan->format('d F Y') }}
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                            <strong>Lokasi:</strong> {{ $submission->lokasi ?? '-' }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2">
                            <i class="fas fa-building text-info me-2"></i>
                            <strong>Unit:</strong> {{ $submission->unit }}
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-clock text-warning me-2"></i>
                            <strong>Diajukan:</strong> {{ $submission->created_at->format('d M Y H:i') }}
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
                <div class="alert alert-info">
                    <h6 class="fw-bold mb-2"><i class="fas fa-sticky-note me-2"></i>Catatan Admin:</h6>
                    <p class="mb-0">{{ $submission->catatan_admin }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Action Buttons for Pending -->
        @if($submission->status === 'pending')
        <div class="card-custom mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Review Pengajuan</h5>
                
                <form action="{{ route('admin.submissions.approve', $submission) }}" method="POST" class="mb-3">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea name="catatan_admin" class="form-control" rows="3" placeholder="Tambahkan catatan untuk user..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Setujui Pengajuan
                    </button>
                </form>

                <hr>

                <form action="{{ route('admin.submissions.reject', $submission) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="catatan_admin" class="form-control @error('catatan_admin') is-invalid @enderror" rows="3" required placeholder="Jelaskan alasan penolakan..."></textarea>
                        @error('catatan_admin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Tolak Pengajuan
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- Upload Results for Approved -->
        @if($submission->status === 'approved')
        <div class="card-custom mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Upload Hasil Dokumentasi</h5>
                
                <form action="{{ route('admin.submissions.upload-results', $submission) }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Link Foto</label>
                        <div id="foto-links">
                            <div class="input-group mb-2">
                                <input type="url" name="hasil_link_foto[]" class="form-control" placeholder="https://drive.google.com/...">
                                <button type="button" class="btn btn-outline-secondary" onclick="addFotoLink()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Link Video</label>
                        <div id="video-links">
                            <div class="input-group mb-2">
                                <input type="url" name="hasil_link_video[]" class="form-control" placeholder="https://youtube.com/...">
                                <button type="button" class="btn btn-outline-secondary" onclick="addVideoLink()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Link Google Drive</label>
                        <div id="drive-links">
                            <div class="input-group mb-2">
                                <input type="url" name="hasil_link_drive[]" class="form-control" placeholder="https://drive.google.com/...">
                                <button type="button" class="btn btn-outline-secondary" onclick="addDriveLink()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-upload me-1"></i>Upload & Tandai Selesai
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- Display Results for Completed -->
        @if($submission->status === 'completed' && $submission->hasResults())
        <div class="card-custom mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Hasil Dokumentasi</h5>

                @if($submission->hasil_link_foto)
                <div class="mb-3">
                    <h6 class="fw-semibold">Link Foto:</h6>
                    <ul class="list-unstyled">
                        @foreach($submission->hasil_link_foto as $link)
                        <li class="mb-2">
                            <a href="{{ $link }}" target="_blank" class="text-decoration-none">
                                <i class="fas fa-image text-primary me-2"></i>{{ $link }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if($submission->hasil_link_video)
                <div class="mb-3">
                    <h6 class="fw-semibold">Link Video:</h6>
                    <ul class="list-unstyled">
                        @foreach($submission->hasil_link_video as $link)
                        <li class="mb-2">
                            <a href="{{ $link }}" target="_blank" class="text-decoration-none">
                                <i class="fas fa-video text-danger me-2"></i>{{ $link }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if($submission->hasil_link_drive)
                <div class="mb-3">
                    <h6 class="fw-semibold">Link Google Drive:</h6>
                    <ul class="list-unstyled">
                        @foreach($submission->hasil_link_drive as $link)
                        <li class="mb-2">
                            <a href="{{ $link }}" target="_blank" class="text-decoration-none">
                                <i class="fab fa-google-drive text-success me-2"></i>{{ $link }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
        <div class="card-custom mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Informasi User</h5>
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="fas fa-user fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">{{ $submission->user->username }}</h6>
                        <small class="text-muted">{{ $submission->user->email ?? '-' }}</small>
                    </div>
                </div>
                <p class="mb-0">
                    <strong>Unit:</strong> {{ $submission->user->unit }}
                </p>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Timeline</h5>
                <ul class="timeline">
                    <li class="timeline-item">
                        <i class="fas fa-paper-plane text-primary"></i>
                        <div>
                            <strong>Diajukan</strong>
                            <br><small class="text-muted">{{ $submission->created_at->format('d M Y H:i') }}</small>
                        </div>
                    </li>
                    @if($submission->approved_at)
                    <li class="timeline-item">
                        <i class="fas fa-check text-success"></i>
                        <div>
                            <strong>Disetujui</strong>
                            <br><small class="text-muted">{{ $submission->approved_at->format('d M Y H:i') }}</small>
                        </div>
                    </li>
                    @endif
                    @if($submission->completed_at)
                    <li class="timeline-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <div>
                            <strong>Selesai</strong>
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

@push('scripts')
<script>
function addFotoLink() {
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="url" name="hasil_link_foto[]" class="form-control" placeholder="https://drive.google.com/...">
        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    document.getElementById('foto-links').appendChild(div);
}

function addVideoLink() {
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="url" name="hasil_link_video[]" class="form-control" placeholder="https://youtube.com/...">
        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    document.getElementById('video-links').appendChild(div);
}

function addDriveLink() {
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="url" name="hasil_link_drive[]" class="form-control" placeholder="https://drive.google.com/...">
        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    document.getElementById('drive-links').appendChild(div);
}
</script>
@endpush
@endsection