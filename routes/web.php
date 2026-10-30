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

// System owner console (above all institutions).
Route::middleware('auth')->prefix('owner')->name('owner.')->group(function () {
    Route::get('/', [App\Http\Controllers\OwnerController::class, 'dashboard'])->name('dashboard');
    Route::get('/institutions', [App\Http\Controllers\OwnerController::class, 'institutions'])->name('institutions');
    Route::get('/users', [App\Http\Controllers\OwnerController::class, 'users'])->name('users');
    Route::post('/institutions/{institution}/toggle', [App\Http\Controllers\OwnerController::class, 'toggleInstitution'])->name('institutions.toggle');
    Route::post('/users/{user}/toggle', [App\Http\Controllers\OwnerController::class, 'toggleUser'])->name('users.toggle');
    Route::get('/invoices/{invoice}/pdf', [App\Http\Controllers\OwnerInvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('/invoices/{invoice}/status', [App\Http\Controllers\OwnerInvoiceController::class, 'status'])->name('invoices.status');
    Route::resource('invoices', App\Http\Controllers\OwnerInvoiceController::class);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/report', [App\Http\Controllers\DashboardController::class, 'report'])->name('dashboard.report');
    Route::get('/dashboard/report/row', [App\Http\Controllers\DashboardController::class, 'reportRow'])->name('dashboard.report.row');
    Route::post('/dashboard/payslip/{lecturer}/email', [App\Http\Controllers\DashboardController::class, 'emailPayslip'])->name('dashboard.payslip.email');
    Route::post('/email-configurations/{email_configuration}/test', [App\Http\Controllers\EmailConfigurationController::class, 'test'])->name('email-configurations.test');
    Route::post('/email-configurations/{email_configuration}/default', [App\Http\Controllers\EmailConfigurationController::class, 'makeDefault'])->name('email-configurations.default');
    Route::resource('email-configurations', App\Http\Controllers\EmailConfigurationController::class)->except('show');
    Route::post('/email-templates/{email_template}/default', [App\Http\Controllers\EmailTemplateController::class, 'makeDefault'])->name('email-templates.default');
    Route::resource('email-templates', App\Http\Controllers\EmailTemplateController::class)->except('show');
    Route::get('/dashboard/payslip/{lecturer}', [App\Http\Controllers\DashboardController::class, 'payslip'])->name('dashboard.payslip');
    Route::get('/payment-schedules/amounts', [App\Http\Controllers\PaymentScheduleController::class, 'amounts'])->name('payment-schedules.amounts');
    Route::get('/payment-schedules/{payment_schedule}/pdf', [App\Http\Controllers\PaymentScheduleController::class, 'pdf'])->name('payment-schedules.pdf');
    Route::get('/payment-schedules/{payment_schedule}/excel', [App\Http\Controllers\PaymentScheduleController::class, 'excel'])->name('payment-schedules.excel');
    Route::resource('payment-schedules', App\Http\Controllers\PaymentScheduleController::class);
    Route::post('/semesters/{semester}/current', [App\Http\Controllers\SemesterController::class, 'makeCurrent'])->name('semesters.current');
    Route::resource('semesters', App\Http\Controllers\SemesterController::class)->except('show');
    Route::get('/attendance-upload', [App\Http\Controllers\AttendanceUploadController::class, 'create'])->name('attendance-upload.create');
    Route::post('/attendance-upload/read', [App\Http\Controllers\AttendanceUploadController::class, 'read'])->name('attendance-upload.read');
    Route::post('/attendance-upload', [App\Http\Controllers\AttendanceUploadController::class, 'store'])->name('attendance-upload.store');
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

    Route::prefix('bulk')->name('bulk.')->group(function () {
        Route::get('lecturers/template', [App\Http\Controllers\BulkImportController::class, 'lecturerTemplate'])->name('lecturers.template');
        Route::post('lecturers/import', [App\Http\Controllers\BulkImportController::class, 'importLecturers'])->name('lecturers.import');
        Route::get('units/template', [App\Http\Controllers\BulkImportController::class, 'unitTemplate'])->name('units.template');
        Route::post('units/import', [App\Http\Controllers\BulkImportController::class, 'importUnits'])->name('units.import');
    });
});

require __DIR__.'/auth.php';

Route::get('lecture-administereds/download-template', [LectureAdministeredController::class, 'downloadTemplate'])->name('lecture-administereds.download-template');
Route::post('lecture-administereds/import', [LectureAdministeredController::class, 'import'])->name('lecture-administereds.import');
Route::resource('classses', App\Http\Controllers\ClasssController::class);
Route::post('lecture-administereds/bulk-delete', [LectureAdministeredController::class, 'bulkDestroy'])->name('lecture-administereds.bulk-destroy')->middleware('auth');
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
