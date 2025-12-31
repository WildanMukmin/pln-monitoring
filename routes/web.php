<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\ScraperController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Admin routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');
        
        // Submissions management
        Route::get('/submissions', [SubmissionController::class, 'adminIndex'])->name('submissions.index');
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
        Route::post('/submissions/{submission}/approve', [SubmissionController::class, 'approve'])->name('submissions.approve');
        Route::post('/submissions/{submission}/reject', [SubmissionController::class, 'reject'])->name('submissions.reject');
        Route::post('/submissions/{submission}/upload-results', [SubmissionController::class, 'uploadResults'])->name('submissions.upload-results');
        
        // Instagram Scraper
        Route::get('/scraper', [ScraperController::class, 'index'])->name('scraper.index');
        Route::post('/scraper/scrape', [ScraperController::class, 'scrape'])->name('scraper.scrape');
        Route::get('/scraper/progress', [ScraperController::class, 'progress'])->name('scraper.progress');
        Route::get('/scraper/export', [ScraperController::class, 'export'])->name('scraper.export');
        Route::post('/scraper/clear', [ScraperController::class, 'clear'])->name('scraper.clear');
        Route::delete('/scraper/{post}', [ScraperController::class, 'destroy'])->name('scraper.destroy');
        
        // Test route (only in local environment)
        if (app()->environment('local')) {
            Route::get('/scraper/test', [ScraperController::class, 'test'])->name('scraper.test');
        }
    });
    
    // User routes
    Route::middleware('user')->prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'userDashboard'])->name('dashboard');
        
        // Submissions
        Route::get('/submissions', [SubmissionController::class, 'userIndex'])->name('submissions.index');
        Route::get('/submissions/create', [SubmissionController::class, 'create'])->name('submissions.create');
        Route::post('/submissions', [SubmissionController::class, 'store'])->name('submissions.store');
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
        Route::get('/submissions/{submission}/edit', [SubmissionController::class, 'edit'])->name('submissions.edit');
        Route::put('/submissions/{submission}', [SubmissionController::class, 'update'])->name('submissions.update');
        Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');
    });
});

// Root redirect
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'user.dashboard');
    }
    return redirect()->route('login');
});