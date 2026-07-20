<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;

use function Livewire\Volt\form;
use function Livewire\Volt\layout;

layout('layouts.guest');

form(LoginForm::class);

$login = function () {
    $this->validate();
    $this->form->authenticate();
    Session::regenerate();

    \App\Models\UserActivity::create([
        'user_id' => auth()->id(),
        'activity_type' => 'login',
        'description' => 'User logged in.',
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ]);

    // Redirect admin to admin dashboard
    if (auth()->user()->role === 'admin') {
        $this->redirect(route('admin.dashboard'), navigate: true);
    } else {
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
};

?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Welcome Back</h1>
        <p class="text-slate-400 text-sm mt-1">Sign in to your account to continue</p>
    </div>

    <form wire:submit="login" class="space-y-5">
        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">Email Address</label>
            <input wire:model="form.email"
                id="email"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="username"
                placeholder="you@example.com"
                class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500
                       focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent
                       transition-all duration-200">
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
            <input wire:model="form.password"
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500
                       focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent
                       transition-all duration-200">
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember + Forgot -->
        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center gap-2 cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox"
                    class="w-4 h-4 rounded border-white/20 bg-white/5 text-sky-500 focus:ring-sky-500">
                <span class="text-sm text-slate-400">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-sky-400 hover:text-sky-300 transition-colors" href="{{ route('password.request') }}" wire:navigate>
                    Forgot password?
                </a>
            @endif
        </div>

        <!-- Submit -->
        <button type="submit"
            class="w-full py-3 px-6 bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500
                   text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-sky-500/25
                   focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-transparent
                   flex items-center justify-center gap-2">
            <span wire:loading.remove>Sign In</span>
            <span wire:loading class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Signing in...
            </span>
        </button>
    </form>

    <!-- Register Link -->
    <p class="text-center text-sm text-slate-400 mt-6">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="text-sky-400 hover:text-sky-300 font-medium transition-colors">
            Create account
        </a>
    </p>
</div>
