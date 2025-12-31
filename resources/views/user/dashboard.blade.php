@extends('layouts.app')

@section('title', 'Dashboard User - PLN Monitoring')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<style>
    #calendar {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .fc-theme-standard .fc-scrollgrid {
        border-radius: 8px;
        overflow: hidden;
    }

    .fc .fc-button-primary {
        background: linear-gradient(135deg, #0052a3 0%, #0a3a52 100%);
        border: none;
    }

    .fc .fc-button-primary:hover {
        background: linear-gradient(135deg, #003a75 0%, #082e3f 100%);
    }

    .fc-event {
        border-radius: 4px;
        padding: 2px 4px;
    }
</style>
@endpush

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-0">Dashboard User</h2>
        <p class="text-muted">Selamat datang, {{ auth()->user()->username }}!</p>
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
                    <div class="text-muted small mb-1">Menunggu</div>
                    <h3 class="fw-bold mb-0">{{ $stats['pending_submissions'] }}</h3>
                </div>
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small mb-1">Disetujui</div>
                    <h3 class="fw-bold mb-0">{{ $stats['approved_submissions'] }}</h3>
                </div>
                <div class="stat-icon primary">
                    <i class="fas fa-check"></i>
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
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-body">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-bolt text-warning me-2"></i>Quick Actions
                </h5>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('user.submissions.create') }}" class="btn btn-primary-custom">
                        <i class="fas fa-plus me-2"></i>Buat Pengajuan Baru
                    </a>
                    <a href="{{ route('user.submissions.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>Lihat Semua Pengajuan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Section -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-body">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                    Kalender Kegiatan Yang Disetujui
                </h5>
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Submissions -->
<div class="row g-4">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-history text-info me-2"></i>
                        Pengajuan Terbaru Saya
                    </h5>
                    <a href="{{ route('user.submissions.index') }}" class="btn btn-sm btn-outline-primary">
                        Lihat Semua
                    </a>
                </div>

                @if($recent_submissions->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <p>Belum ada pengajuan</p>
                        <a href="{{ route('user.submissions.create') }}" class="btn btn-primary-custom">
                            <i class="fas fa-plus me-2"></i>Buat Pengajuan Pertama
                        </a>
                    </div>
                @else
                    <div class="list-group">
                        @foreach($recent_submissions as $submission)
                        <a href="{{ route('user.submissions.show', $submission) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">{{ $submission->judul_kegiatan }}</h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-calendar me-1"></i>{{ $submission->tanggal_kegiatan->format('d M Y') }}
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $submission->lokasi ?? '-' }}
                                    </p>
                                    <small class="text-muted">Diajukan {{ $submission->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="text-end ms-3">
                                    <span class="badge badge-custom status-{{ $submission->status }}">
                                        {{ $submission->status_label }}
                                    </span>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'id',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        buttonText: {
            today: 'Hari Ini',
            month: 'Bulan',
            week: 'Minggu'
        },
        events: @json($calendar_events),
        eventClick: function(info) {
            var props = info.event.extendedProps;
            var statusBadge = info.event.extendedProps.status === 'completed' 
                ? '<span class="badge bg-success">Selesai</span>' 
                : '<span class="badge bg-primary">Disetujui</span>';
            
            var modalHtml = `
                <div class="modal fade" id="eventModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${info.event.title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Status:</strong> ${statusBadge}</p>
                                <p><strong>Tanggal:</strong> ${info.event.start.toLocaleDateString('id-ID')}</p>
                                <p><strong>Unit:</strong> ${props.unit || '-'}</p>
                                <p><strong>Lokasi:</strong> ${props.lokasi || '-'}</p>
                                ${props.deskripsi ? '<p><strong>Deskripsi:</strong><br>' + props.deskripsi + '</p>' : ''}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove old modal if exists
            var oldModal = document.getElementById('eventModal');
            if(oldModal) oldModal.remove();
            
            // Add and show new modal
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            var modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();
        }
    });
    calendar.render();
});
</script>
@endpush
