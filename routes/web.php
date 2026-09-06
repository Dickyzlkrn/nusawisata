<?php

use App\Http\Controllers\Admin\ClusteringController as AdminClusteringController;
use App\Http\Controllers\Admin\CollaborativeFilteringController as AdminCollaborativeFilteringController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DatasetController as AdminDatasetController;
use App\Http\Controllers\Admin\DestinationController as AdminDestinationController;
use App\Http\Controllers\Admin\DocumentationController as AdminDocumentationController;
use App\Http\Controllers\Admin\MlRunController as AdminMlRunController;
use App\Http\Controllers\Admin\ProvinceController as AdminProvinceController;
use App\Http\Controllers\Admin\RatingController as AdminRatingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RecommendationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

// System & Algorithm Documentation
Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation');

// Destinations
Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations.index');
Route::get('/destinations/{destination}', [DestinationController::class, 'show'])->name('destinations.show');

// Provinces
Route::get('/provinces', [ProvinceController::class, 'index'])->name('provinces.index');
Route::get('/provinces/{province}', [ProvinceController::class, 'show'])->name('provinces.show');

// Recommendations
Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations.index');
Route::post('/recommendations/generate', [RecommendationController::class, 'generate'])->name('recommendations.generate');
Route::get('/recommendations/status/{run}', [RecommendationController::class, 'status'])->name('recommendations.status');
Route::get('/recommendations/{run}/result', [RecommendationController::class, 'result'])->name('recommendations.result');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/ratings', [RatingController::class, 'store'])->name('ratings.store');
    Route::delete('/ratings/{rating}', [RatingController::class, 'destroy'])->name('ratings.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Restricted by 'admin' middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Destinations CRUD
    Route::resource('destinations', AdminDestinationController::class);

    // Provinces CRUD
    Route::resource('provinces', AdminProvinceController::class);

    // Users Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Ratings Management
    Route::get('/ratings', [AdminRatingController::class, 'index'])->name('ratings.index');
    Route::delete('/ratings/{rating}', [AdminRatingController::class, 'destroy'])->name('ratings.destroy');

    // Dataset & Scraping Tools
    Route::get('/dataset', [AdminDatasetController::class, 'index'])->name('dataset.index');
    Route::post('/dataset/upload', [AdminDatasetController::class, 'upload'])->name('dataset.upload');
    Route::post('/dataset/import', [AdminDatasetController::class, 'import'])->name('dataset.import');
    Route::post('/dataset/import-default', [AdminDatasetController::class, 'importDefault'])->name('dataset.import_default');
    Route::post('/dataset/crawl', [AdminDatasetController::class, 'crawl'])->name('dataset.crawl');
    Route::post('/dataset/{version}/activate', [AdminDatasetController::class, 'activate'])->name('dataset.activate');
    Route::post('/dataset/{version}/rollback', [AdminDatasetController::class, 'rollback'])->name('dataset.rollback');
    Route::post('/dataset/{version}/reprocess', [AdminDatasetController::class, 'reprocess'])->name('dataset.reprocess');
    Route::post('/dataset/{version}/rebuild-ml', [AdminDatasetController::class, 'rebuildMl'])->name('dataset.rebuild_ml');
    Route::delete('/dataset/{version}', [AdminDatasetController::class, 'destroy'])->name('dataset.destroy');

    // Automated ML Run & Model Registry
    Route::get('/ml-runs', [AdminMlRunController::class, 'index'])->name('ml_runs.index');
    Route::get('/ml-runs/dataset-summary/{version}', [AdminMlRunController::class, 'datasetSummary'])->name('ml_runs.dataset_summary');
    Route::post('/ml-runs/run', [AdminMlRunController::class, 'run'])->name('ml_runs.run');
    Route::get('/ml-runs/{run}', [AdminMlRunController::class, 'show'])->name('ml_runs.show');
    Route::post('/ml-runs/{run}/activate', [AdminMlRunController::class, 'activate'])->name('ml_runs.activate');
    Route::delete('/ml-runs/{run}', [AdminMlRunController::class, 'destroy'])->name('ml_runs.destroy');

    // Clustering Research Tools
    Route::get('/clustering', [AdminClusteringController::class, 'index'])->name('clustering.index');
    Route::post('/clustering/run', [AdminClusteringController::class, 'run'])->name('clustering.run');

    // Collaborative Filtering Research Tools
    Route::get('/collaborative-filtering', [AdminCollaborativeFilteringController::class, 'index'])->name('collaborative_filtering.index');
    Route::post('/collaborative-filtering/simulate', [AdminCollaborativeFilteringController::class, 'simulate'])->name('collaborative_filtering.simulate');
    Route::post('/collaborative-filtering/evaluate', [AdminCollaborativeFilteringController::class, 'evaluate'])->name('collaborative_filtering.evaluate');

    // System Documentation & Technical Architecture
    Route::get('/documentation', [AdminDocumentationController::class, 'index'])->name('documentation.index');
});
