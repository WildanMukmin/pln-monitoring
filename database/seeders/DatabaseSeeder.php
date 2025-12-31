<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Submission;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'unit' => 'ADMIN',
            'email' => 'admin@pln.co.id'
        ]);

        // Create Demo User
        User::create([
            'username' => 'user',
            'password' => Hash::make('user123'),
            'role' => 'user',
            'unit' => 'UID Lampung',
            'email' => 'user@pln.co.id'
        ]);

        // Create sample submissions
        $user = User::where('role', 'user')->first();
        
        Submission::create([
            'user_id' => $user->id,
            'judul_kegiatan' => 'Dokumentasi Rapat Koordinasi Triwulan',
            'deskripsi' => 'Dokumentasi rapat koordinasi triwulan dengan seluruh unit kerja PLN UID Lampung',
            'tanggal_kegiatan' => now()->addDays(5),
            'lokasi' => 'Kantor PLN UID Lampung',
            'unit' => 'UID Lampung',
            'status' => 'pending'
        ]);

        Submission::create([
            'user_id' => $user->id,
            'judul_kegiatan' => 'Dokumentasi Sosialisasi Program CSR',
            'deskripsi' => 'Sosialisasi program tanggung jawab sosial kepada masyarakat',
            'tanggal_kegiatan' => now()->addDays(10),
            'lokasi' => 'Aula Kantor PLN',
            'unit' => 'UID Lampung',
            'status' => 'approved',
            'approved_at' => now()
        ]);

        Submission::create([
            'user_id' => $user->id,
            'judul_kegiatan' => 'Dokumentasi Kunjungan Industri',
            'deskripsi' => 'Kunjungan industri mahasiswa ke fasilitas pembangkit listrik',
            'tanggal_kegiatan' => now()->subDays(5),
            'lokasi' => 'PLTU Tarahan',
            'unit' => 'UID Lampung',
            'status' => 'completed',
            'approved_at' => now()->subDays(4),
            'completed_at' => now()->subDays(2),
            'hasil_link_foto' => json_encode(['https://drive.google.com/foto1', 'https://drive.google.com/foto2']),
            'hasil_link_video' => json_encode(['https://youtube.com/watch?v=example']),
            'hasil_link_drive' => json_encode(['https://drive.google.com/folder/example'])
        ]);
    }
}