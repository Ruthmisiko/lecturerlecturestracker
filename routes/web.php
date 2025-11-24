<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LectureAdministeredController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('lecturers', App\Http\Controllers\LecturerController::class);

});

require __DIR__.'/auth.php';

Route::get('lecture-administereds/download-template', [LectureAdministeredController::class, 'downloadTemplate'])->name('lecture-administereds.download-template');
Route::post('lecture-administereds/import', [LectureAdministeredController::class, 'import'])->name('lecture-administereds.import');
Route::resource('classses', App\Http\Controllers\ClasssController::class);
Route::resource('lecture-administereds', App\Http\Controllers\LectureAdministeredController::class);
Route::resource('roles', App\Http\Controllers\RoleController::class);
Route::resource('users', App\Http\Controllers\UserController::class);
Route::resource('units', App\Http\Controllers\UnitController::class);
Route::get('lecture-administereds/export/pdf', [LectureAdministeredController::class, 'exportPdf'])
    ->name('lecture-administereds.export.pdf');

Route::get('lecture-administereds/export/excel', [LectureAdministeredController::class, 'exportExcel'])
    ->name('lecture-administereds.export.excel');
