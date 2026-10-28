<?php

use App\Http\Controllers\CsvAskController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/ask', [CsvAskController::class, 'store'])->name('ask.store');
