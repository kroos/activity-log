<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'adminAccess'])->group(function () {

	Route::controller(\App\Http\Controllers\System\ActivityLogController::class)->group(function () {

		Route::get('/activity-logs/getActivityLogs', 'getActivityLogs')->name('getActivityLogs');

		Route::prefix('/activity-logs')->name('activity-logs.')->group(function () {
			Route::get('/', 'index')->name('index');
			Route::get('/{log}', 'show')->name('show');
			Route::delete('{log}', 'destroy')->name('destroy');
		});

	});
});

