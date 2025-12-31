<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\InstagramPost;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show Admin Dashboard
     */
    public function adminDashboard()
    {
        $stats = [
            'total_submissions' => Submission::count(),
            'pending_submissions' => Submission::pending()->count(),
            'approved_submissions' => Submission::approved()->count(),
            'completed_submissions' => Submission::completed()->count(),
            'rejected_submissions' => Submission::rejected()->count(),
            'total_users' => User::where('role', 'user')->count(),
            'total_posts' => InstagramPost::count(),
        ];

        $recent_submissions = Submission::with('user')
            ->latest()
            ->limit(5)
            ->get();

        $pending_submissions = Submission::pending()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_submissions', 'pending_submissions'));
    }

    /**
     * Show User Dashboard
     */
    public function userDashboard()
    {
        $user = Auth::user();
        
        $stats = [
            'total_submissions' => $user->submissions()->count(),
            'pending_submissions' => $user->submissions()->pending()->count(),
            'approved_submissions' => $user->submissions()->approved()->count(),
            'completed_submissions' => $user->submissions()->completed()->count(),
        ];

        // Get approved and completed submissions for calendar
        $calendar_events = Submission::where(function($query) {
                $query->where('status', 'approved')
                      ->orWhere('status', 'completed');
            })
            ->with('user')
            ->get()
            ->map(function($submission) {
                return [
                    'id' => $submission->id,
                    'title' => $submission->judul_kegiatan,
                    'start' => $submission->tanggal_kegiatan->format('Y-m-d'),
                    'backgroundColor' => $submission->status === 'completed' ? '#10b981' : '#3b82f6',
                    'borderColor' => $submission->status === 'completed' ? '#059669' : '#2563eb',
                    'extendedProps' => [
                        'unit' => $submission->unit,
                        'lokasi' => $submission->lokasi,
                        'status' => $submission->status,
                        'deskripsi' => $submission->deskripsi,
                    ]
                ];
            });

        $recent_submissions = $user->submissions()
            ->latest()
            ->limit(5)
            ->get();

        return view('user.dashboard', compact('stats', 'calendar_events', 'recent_submissions'));
    }
}