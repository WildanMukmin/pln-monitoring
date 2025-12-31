<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'judul_kegiatan',
        'deskripsi',
        'tanggal_kegiatan',
        'lokasi',
        'unit',
        'status',
        'catatan_admin',
        'hasil_link_foto',
        'hasil_link_video',
        'hasil_link_drive',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'tanggal_kegiatan' => 'date',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'hasil_link_foto' => 'array',
        'hasil_link_video' => 'array',
        'hasil_link_drive' => 'array',
    ];

    /**
     * Get the user that created this submission
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for pending submissions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved submissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for completed submissions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for rejected submissions
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'completed' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label in Indonesian
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            default => 'Unknown',
        };
    }

    /**
     * Check if submission has results uploaded
     */
    public function hasResults(): bool
    {
        return !empty($this->hasil_link_foto) || 
               !empty($this->hasil_link_video) || 
               !empty($this->hasil_link_drive);
    }
}