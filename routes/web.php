<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\UsersController;
use App\Http\Controllers\FarmersController;
use App\Http\Controllers\FarmingTypeController;
use App\Http\Controllers\PublicPostController;
use App\Http\Controllers\AssistanceController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::match(['get', 'post'], '/finder', [PublicPostController::class, 'finder'])->name('finder');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    } else {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::resource('/users', UsersController::class)->only([
        'index', 'create', 'store', 'update', 'destroy'
    ])->parameters([ 'users'=>'id' ]);

    Route::resource('/farmers', FarmersController::class)->only([
        'index', 'create', 'store', 'update', 'destroy'
    ])->parameters([ 'farmers'=>'id' ]);
    
    Route::resource('/types', FarmingTypeController::class)->only([
        'index', 'create', 'store', 'update', 'destroy'
    ])->parameters([ 'types'=>'id' ]);

    Route::resource('/assistance', AssistanceController::class)->only([
        'index', 'create', 'store', 'update', 'destroy'
    ])->parameters([ 'assistance'=>'id' ]);

    Route::put('/types/archive_type/{id}', [FarmingTypeController::class, 'archive_type'])->name('types.archive_type');
    Route::put('/users/archive_user/{id}', [UsersController::class, 'archive_user'])->name('users.archive_user');
    Route::put('/farmers/archive_farmer/{id}', [FarmersController::class, 'archive_farmer'])->name('farmers.archive_farmer');
    Route::post('/farmers/upload/{id}', [FarmersController::class, 'upload'])->name('farmers.upload');
    Route::put('/assistance/archive_assistance/{id}', [AssistanceController::class, 'archive_assistance'])->name('assistance.archive_assistance');
    Route::post('/assistance/save_assistance', [AssistanceController::class, 'save_assistance'])->name('assistance.save_assistance');
    Route::get('farmers/view/{id}', [FarmersController::class, 'view'])->name('farmers.view');
    Route::get('/search', [FarmersController::class, 'search'])->name('farmers.search');

    Route::prefix('reports')->name('reports.')->group( function() {
        Route::match(['get', 'post'], '/assistance', [AssistanceController::class, 'reports'])->name('assistance');
        Route::match(['get', 'post'], '/activities', [ReportController::class, 'reports'])->name('activities');
        Route::match(['get', 'post'], '/registered', [ReportController::class, 'registered'])->name('registered');
        Route::match(['get', 'post'], '/farming', [ReportController::class, 'farming'])->name('farming');
        Route::match(['get', 'post'], '/livelihood', [ReportController::class, 'livelihood'])->name('livelihood');
    });
});

Artisan::call('storage:link');