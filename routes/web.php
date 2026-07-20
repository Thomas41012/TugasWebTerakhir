<?php

use App\Http\Controllers\CountryController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get(
    '/dashboard',
    [DashboardController::class, 'index']
)->middleware(['auth', 'verified'])->name('dashboard');

Route::get(
    '/countries/{country}',
    [CountryController::class, 'detail']
)->middleware(['auth'])->whereNumber('country')->name('countries.detail');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::view('/users', 'admin.users')->name('users');
    Route::delete('/users/{user}', function(\App\Models\User $user) {
        if ($user->role === 'admin') {
            return back()->with('error', 'Akun admin tidak dapat dihapus!');
        }
        $user->delete();
        return back()->with('success', 'User berhasil dihapus secara permanen.');
    })->name('users.destroy');
    Route::view('/ports', 'admin.ports')->name('ports');
    Route::view('/articles', 'admin.articles')->name('articles');
});

require __DIR__.'/auth.php';

// Temporary route to force create Admin user
Route::get('/setup-admin-999', function() {
    \App\Models\User::updateOrCreate(
        ['email' => 'admin233@gmail.com'],
        [
            'name' => 'Super Admin',
            'password' => \Illuminate\Support\Facades\Hash::make('admin233'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]
    );
    return 'Akun Admin berhasil dibuat! Silakan kembali ke halaman login.';
});
