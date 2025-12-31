@extends('layouts.app')

@section('title', 'Edit Pengajuan')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card-custom">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">
                        <i class="fas fa-edit text-warning me-2"></i>
                        Edit Pengajuan Dokumentasi
                    </h4>

                    <form action="{{ route('user.submissions.update', $submission) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Judul Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="judul_kegiatan"
                                class="form-control @error('judul_kegiatan') is-invalid @enderror"
                                value="{{ old('judul_kegiatan', $submission->judul_kegiatan) }}" required>
                            @error('judul_kegiatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Deskripsi Kegiatan</label>
                            <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror"
                                rows="4"
                                placeholder="Jelaskan detail kegiatan...">{{ old('deskripsi', $submission->deskripsi) }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Tanggal Kegiatan <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="tanggal_kegiatan"
                                    class="form-control @error('tanggal_kegiatan') is-invalid @enderror"
                                    value="{{ old('tanggal_kegiatan', $submission->tanggal_kegiatan->format('Y-m-d')) }}"
                                    required>
                                @error('tanggal_kegiatan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Lokasi</label>
                                <input type="text" name="lokasi" class="form-control @error('lokasi') is-invalid @enderror"
                                    value="{{ old('lokasi', $submission->lokasi) }}"
                                    placeholder="Contoh: Kantor PLN UID Lampung">
                                @error('lokasi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Unit</label>
                            <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror"
                                value="{{ old('unit', $submission->unit) }}">
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Anda hanya dapat mengedit pengajuan dengan status <strong>Pending</strong>. Setelah disetujui
                            atau ditolak, pengajuan tidak dapat diubah.
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('user.submissions.show', $submission) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save me-1"></i>Update Pengajuan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection