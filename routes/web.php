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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/report', [App\Http\Controllers\DashboardController::class, 'report'])->name('dashboard.report');
    Route::get('/dashboard/payslip/{lecturer}', [App\Http\Controllers\DashboardController::class, 'payslip'])->name('dashboard.payslip');
    Route::get('/payment-schedules/amounts', [App\Http\Controllers\PaymentScheduleController::class, 'amounts'])->name('payment-schedules.amounts');
    Route::get('/payment-schedules/{payment_schedule}/pdf', [App\Http\Controllers\PaymentScheduleController::class, 'pdf'])->name('payment-schedules.pdf');
    Route::get('/payment-schedules/{payment_schedule}/excel', [App\Http\Controllers\PaymentScheduleController::class, 'excel'])->name('payment-schedules.excel');
    Route::resource('payment-schedules', App\Http\Controllers\PaymentScheduleController::class);
    Route::post('/semesters/{semester}/current', [App\Http\Controllers\SemesterController::class, 'makeCurrent'])->name('semesters.current');
    Route::resource('semesters', App\Http\Controllers\SemesterController::class)->except('show');
    Route::post('/institution/switch', [App\Http\Controllers\InstitutionController::class, 'switch'])->name('institution.switch');
    Route::get('/institutions/{institution}/settings', [App\Http\Controllers\InstitutionController::class, 'settings'])->name('institutions.settings');
    Route::put('/institutions/{institution}/settings', [App\Http\Controllers\InstitutionController::class, 'updateSettings'])->name('institutions.settings.update');
    Route::get('/institutions/{institution}/asset/{type}', [App\Http\Controllers\InstitutionController::class, 'asset'])
        ->whereIn('type', ['logo', 'letterhead'])->name('institutions.asset');
    Route::resource('institutions', App\Http\Controllers\InstitutionController::class)->except('show');
});

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
Route::resource('departments', App\Http\Controllers\DepartmentController::class);
Route::get('lecture-administereds/export/pdf', [LectureAdministeredController::class, 'exportPdf'])
    ->name('lecture-administereds.export.pdf');

Route::get('lecture-administereds/export/excel', [LectureAdministeredController::class, 'exportExcel'])
    ->name('lecture-administereds.export.excel');

Route::post('/department/switch', [App\Http\Controllers\DepartmentController::class, 'switchDepartment'])
    ->name('department.switch')
    ->middleware('auth');

Route::get('lecturers/{id}/export/pdf', [App\Http\Controllers\LecturerController::class, 'exportPdf'])
    ->name('lecturers.export.pdf');
Route::get('lecturers/{id}/export/excel', [App\Http\Controllers\LecturerController::class, 'exportExcel'])
    ->name('lecturers.export.excel');
