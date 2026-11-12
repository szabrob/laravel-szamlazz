<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\SzamlazzConsoleController;

Route::redirect('/', '/szamlazz');

Route::prefix('szamlazz')->name('szamlazz.')->controller(SzamlazzConsoleController::class)->group(function (): void {
    Route::post('credentials', 'credentials')->name('credentials');
    Route::get('pdf/{file}', 'pdf')->name('pdf');
    Route::get('{operation?}', 'show')->name('console');
    Route::post('{operation}', 'run')->name('run');
});
