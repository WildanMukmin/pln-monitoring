<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal',
        'bulan',
        'tahun',
        'judul_pemberitaan',
        'link_pemberitaan',
        'platform',
        'tipe_konten',
        'pic_unit',
        'akun',
        'kategori',
        'likes',
        'comments',
        'views',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'likes' => 'integer',
        'comments' => 'integer',
        'views' => 'integer',
    ];

    /**
     * Get engagement rate
     */
    public function getEngagementRateAttribute(): float
    {
        $total = $this->likes + $this->comments;
        return $this->views > 0 ? round(($total / $this->views) * 100, 2) : 0;
    }
}