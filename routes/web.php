<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Tracking
Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
Route::get('/tracking/search', [TrackingController::class, 'searchCategories'])->name('tracking.search');
Route::post('/tracking/categories', [TrackingController::class, 'storeCategory'])->name('tracking.store-category');
Route::put('/tracking/categories/{category}', [TrackingController::class, 'updateCategory'])->name('tracking.update-category');
Route::delete('/tracking/categories/{category}', [TrackingController::class, 'destroyCategory'])->name('tracking.destroy-category');
Route::post('/tracking/categories/{category}/sync', [TrackingController::class, 'syncCategory'])->name('tracking.sync-category');
Route::post('/tracking/channels', [TrackingController::class, 'storeChannel'])->name('tracking.store-channel');
Route::delete('/tracking/channels/{channel}', [TrackingController::class, 'destroyChannel'])->name('tracking.destroy-channel');
Route::delete('/tracking/channels/by-login/{login}', [TrackingController::class, 'destroyChannelByLogin'])->name('tracking.destroy-channel-by-login');

// Alerts
Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
Route::post('/alerts', [AlertController::class, 'store'])->name('alerts.store');
Route::put('/alerts/{alert}', [AlertController::class, 'update'])->name('alerts.update');
Route::delete('/alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');

// Blacklist
Route::get('/blacklist', [BlacklistController::class, 'index'])->name('blacklist.index');
Route::post('/blacklist', [BlacklistController::class, 'store'])->name('blacklist.store');
Route::delete('/blacklist/{blacklist}', [BlacklistController::class, 'destroy'])->name('blacklist.destroy');

// History
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
Route::delete('/history', [HistoryController::class, 'clear'])->name('history.clear');

// Settings
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/test-twitch', [SettingsController::class, 'testTwitch'])->name('settings.test-twitch');
Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
Route::post('/settings/test-discord', [SettingsController::class, 'testDiscord'])->name('settings.test-discord');
Route::post('/settings/test-telegram', [SettingsController::class, 'testTelegram'])->name('settings.test-telegram');
Route::post('/settings/test-webhook', [SettingsController::class, 'testWebhook'])->name('settings.test-webhook');
Route::post('/settings/disable-auth', [SettingsController::class, 'disableAuth'])->name('settings.disable-auth');
Route::get('/settings/check-update', [SettingsController::class, 'checkUpdate'])->name('settings.check-update');
Route::get('/settings/export', [SettingsController::class, 'export'])->name('settings.export');
Route::post('/settings/import', [SettingsController::class, 'import'])->name('settings.import');

// Sync
Route::post('/sync', [SyncController::class, 'trigger'])->name('sync.trigger');
Route::get('/sync/status', [SyncController::class, 'status'])->name('sync.status');
