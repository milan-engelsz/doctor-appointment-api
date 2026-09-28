<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Doctor\AvailabilityController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\FreeSlotController;
use App\Http\Controllers\PatientController;

Route::prefix('v1')->group(function () {

    Route::apiResources([
        'doctors' => DoctorController::class,
        'patients' => PatientController::class,
    ]);

    Route::apiResource('appointments', AppointmentController::class)
        ->only(['index', 'store']);

    Route::group([
        'as' => 'doctors.',
        'prefix' => 'doctors/{doctor}',
    ], function () {
        Route::apiResource('availabilities', AvailabilityController::class)
            ->only(['index', 'store']);
    });

    Route::apiResource('free-slots', FreeSlotController::class)
        ->only(['index']);

    Route::group([
        'as' => 'appointments.',
        'prefix' => 'appointments/{appointment}',
    ], function () {
        Route::post('cancel', [AppointmentController::class, 'cancel'])->name('cancel');
        Route::post('confirm', [AppointmentController::class, 'confirm'])->name('confirm');
        Route::post('complete', [AppointmentController::class, 'complete'])->name('complete');

    });

});
