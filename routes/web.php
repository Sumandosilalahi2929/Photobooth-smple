<?php

use App\Http\Controllers\PhotoboothController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Di sini kita mendaftarkan semua rute web untuk aplikasi.
|
*/


Route::get('/', [PhotoboothController::class, 'index'])->name('photobooth.index');


Route::post('/photobooth/store', [PhotoboothController::class, 'store'])->name('photobooth.store');
