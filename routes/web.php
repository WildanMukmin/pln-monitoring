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
        Route::prefix('submissions')->name('submissions.')->group(function () {
            Route::get('/', [SubmissionController::class, 'adminIndex'])->name('index');
            Route::get('/{submission}', [SubmissionController::class, 'show'])->name('show');
            Route::post('/{submission}/approve', [SubmissionController::class, 'approve'])->name('approve');
            Route::post('/{submission}/reject', [SubmissionController::class, 'reject'])->name('reject');
            Route::post('/{submission}/upload-results', [SubmissionController::class, 'uploadResults'])->name('upload-results');
        });
        
        // Instagram Scraper
        Route::prefix('scraper')->name('scraper.')->group(function () {
            Route::get('/', [ScraperController::class, 'index'])->name('index');
            Route::post('/scrape', [ScraperController::class, 'scrape'])->name('scrape');
            Route::get('/export', [ScraperController::class, 'export'])->name('export');
            Route::post('/clear', [ScraperController::class, 'clear'])->name('clear');
            
            // Test route (only in local environment)
            if (app()->environment('local')) {
                Route::get('/test', [ScraperController::class, 'test'])->name('test');
            }
        });
    });
    
    // User routes
    Route::middleware('user')->prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'userDashboard'])->name('dashboard');
        
        // Submissions
        Route::prefix('submissions')->name('submissions.')->group(function () {
            Route::get('/', [SubmissionController::class, 'userIndex'])->name('index');
            Route::get('/create', [SubmissionController::class, 'create'])->name('create');
            Route::post('/', [SubmissionController::class, 'store'])->name('store');
            Route::get('/{submission}', [SubmissionController::class, 'show'])->name('show');
            Route::get('/{submission}/edit', [SubmissionController::class, 'edit'])->name('edit');
            Route::put('/{submission}', [SubmissionController::class, 'update'])->name('update');
            Route::delete('/{submission}', [SubmissionController::class, 'destroy'])->name('destroy');
        });
    });
});

// Root redirect
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'user.dashboard');
    }
    return redirect()->route('login');
})->name('home');