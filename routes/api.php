<?php

use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\InspectionController;
use Illuminate\Support\Facades\Route;

Route::get('inspections', [InspectionController::class, 'index']);
Route::post('inspections/submit', [InspectionController::class, 'submit']);
Route::get('inspections/{transactionId}', [InspectionController::class, 'show']);
Route::put('inspections/{transactionId}', [InspectionController::class, 'update']);
Route::delete('inspections/{transactionId}', [InspectionController::class, 'destroy']);
Route::post('files/upload', [FileController::class, 'upload']);
