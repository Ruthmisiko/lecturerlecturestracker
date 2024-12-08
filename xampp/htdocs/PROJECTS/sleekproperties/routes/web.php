<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Display all properties
    Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
    
    // Show form to create a new property
    Route::get('properties/create', [PropertyController::class, 'create'])->name('properties.create');
    
    // Store new property (POST)
    Route::post('properties', [PropertyController::class, 'store'])->name('properties.store');
    
    // Show form to edit an existing property
    Route::get('properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    
    // Update existing property (PUT/PATCH)
    Route::put('properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
    
    // Delete a property (DELETE)
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');
// });

// require __DIR__.'/auth.php';
