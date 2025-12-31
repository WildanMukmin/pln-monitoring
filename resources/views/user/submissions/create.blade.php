@extends('layouts.app')

@section('title', 'Buat Pengajuan - PLN Monitoring')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card-custom">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-4">
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    Buat Pengajuan Dokumentasi
                </h4>

                <form action="{{ route('user.submissions.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" name="judul_kegiatan" class="form-control @error('judul_kegiatan') is-invalid @enderror" 
                               value="{{ old('judul_kegiatan') }}" required>
                        @error('judul_kegiatan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi Kegiatan</label>
                        <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" 
                                  rows="4" placeholder="Jelaskan detail kegiatan...">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tanggal Kegiatan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_kegiatan" class="form-control @error('tanggal_kegiatan') is-invalid @enderror" 
                                   value="{{ old('tanggal_kegiatan') }}" required>
                            @error('tanggal_kegiatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control @error('lokasi') is-invalid @enderror" 
                                   value="{{ old('lokasi') }}" placeholder="Contoh: Kantor PLN UID Lampung">
                            @error('lokasi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Unit</label>
                        <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" 
                               value="{{ old('unit', auth()->user()->unit) }}">
                        @error('unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Pengajuan akan dikirim ke admin untuk direview. Anda akan mendapat notifikasi setelah pengajuan disetujui atau ditolak.
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('user.submissions.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-paper-plane me-1"></i>Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
