<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
    /**
     * Display all submissions (Admin)
     */
    public function adminIndex()
    {
        $submissions = Submission::with('user')
            ->latest()
            ->paginate(20);

        return view('admin.submissions.index', compact('submissions'));
    }

    /**
     * Display user's own submissions
     */
    public function userIndex()
    {
        $submissions = Auth::user()
            ->submissions()
            ->latest()
            ->paginate(10);

        return view('user.submissions.index', compact('submissions'));
    }

    /**
     * Show create submission form (User)
     */
    public function create()
    {
        return view('user.submissions.create');
    }

    /**
     * Store new submission (User)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul_kegiatan' => 'required|max:255',
            'deskripsi' => 'nullable',
            'tanggal_kegiatan' => 'required|date',
            'lokasi' => 'nullable|max:255',
            'unit' => 'nullable|max:100',
        ]);

        $submission = Auth::user()->submissions()->create([
            'judul_kegiatan' => $validated['judul_kegiatan'],
            'deskripsi' => $validated['deskripsi'],
            'tanggal_kegiatan' => $validated['tanggal_kegiatan'],
            'lokasi' => $validated['lokasi'],
            'unit' => $validated['unit'] ?? Auth::user()->unit,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('user.submissions.index')
            ->with('success', 'Pengajuan berhasil dibuat! Menunggu persetujuan admin.');
    }

    /**
     * Show submission detail
     */
    public function show(Submission $submission)
    {
        // Check authorization
        if (Auth::user()->isUser() && $submission->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $view = Auth::user()->isAdmin() ? 'admin.submissions.show' : 'user.submissions.show';
        return view($view, compact('submission'));
    }

    /**
     * Show edit form (User can edit only pending submissions)
     */
    public function edit(Submission $submission)
    {
        if ($submission->user_id !== Auth::id() || $submission->status !== 'pending') {
            abort(403, 'Unauthorized or submission already processed');
        }

        return view('user.submissions.edit', compact('submission'));
    }

    /**
     * Update submission (User)
     */
    public function update(Request $request, Submission $submission)
    {
        if ($submission->user_id !== Auth::id() || $submission->status !== 'pending') {
            abort(403, 'Unauthorized or submission already processed');
        }

        $validated = $request->validate([
            'judul_kegiatan' => 'required|max:255',
            'deskripsi' => 'nullable',
            'tanggal_kegiatan' => 'required|date',
            'lokasi' => 'nullable|max:255',
            'unit' => 'nullable|max:100',
        ]);

        $submission->update($validated);

        return redirect()
            ->route('user.submissions.show', $submission)
            ->with('success', 'Pengajuan berhasil diupdate!');
    }

    /**
     * Delete submission (User can delete only pending submissions)
     */
    public function destroy(Submission $submission)
    {
        if ($submission->user_id !== Auth::id() || $submission->status !== 'pending') {
            abort(403, 'Unauthorized or submission already processed');
        }

        $submission->delete();

        return redirect()
            ->route('user.submissions.index')
            ->with('success', 'Pengajuan berhasil dihapus!');
    }

    /**
     * Approve submission (Admin)
     */
    public function approve(Request $request, Submission $submission)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $submission->update([
            'status' => 'approved',
            'approved_at' => now(),
            'catatan_admin' => $request->catatan_admin,
        ]);

        return back()->with('success', 'Pengajuan berhasil disetujui!');
    }

    /**
     * Reject submission (Admin)
     */
    public function reject(Request $request, Submission $submission)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'catatan_admin' => 'required',
        ]);

        $submission->update([
            'status' => 'rejected',
            'catatan_admin' => $validated['catatan_admin'],
        ]);

        return back()->with('success', 'Pengajuan berhasil ditolak!');
    }

    /**
     * Upload results (Admin)
     */
    public function uploadResults(Request $request, Submission $submission)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'hasil_link_foto' => 'nullable|array',
            'hasil_link_foto.*' => 'url',
            'hasil_link_video' => 'nullable|array',
            'hasil_link_video.*' => 'url',
            'hasil_link_drive' => 'nullable|array',
            'hasil_link_drive.*' => 'url',
        ]);

        $submission->update([
            'hasil_link_foto' => $validated['hasil_link_foto'] ?? [],
            'hasil_link_video' => $validated['hasil_link_video'] ?? [],
            'hasil_link_drive' => $validated['hasil_link_drive'] ?? [],
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Hasil dokumentasi berhasil diupload!');
    }
}