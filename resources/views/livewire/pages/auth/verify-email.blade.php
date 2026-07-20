<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use function Livewire\Volt\layout;

layout('layouts.guest');

$sendVerification = function () {
    if (Auth::user()->hasVerifiedEmail()) {
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

        return;
    }

    Auth::user()->sendEmailVerificationNotification();

    Session::flash('status', 'verification-link-sent');
};

$logout = function (Logout $logout) {
    $logout();

    $this->redirect('/', navigate: true);
};

?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Verify Email</h1>
        <p class="text-slate-300 text-sm mt-2 leading-relaxed">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 font-medium text-sm text-emerald-400 bg-emerald-500/10 p-4 rounded-xl border border-emerald-500/20">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <button wire:click="sendVerification" type="button"
            class="w-full sm:w-auto py-2.5 px-5 bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500
                   text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-sky-500/25
                   focus:outline-none focus:ring-2 focus:ring-sky-500 flex items-center justify-center gap-2">
            <span wire:loading.remove wire:target="sendVerification">Resend Verification Email</span>
            <span wire:loading wire:target="sendVerification" class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Sending...
            </span>
        </button>

        <button wire:click="logout" type="button" class="text-sm text-slate-400 hover:text-white underline transition-colors focus:outline-none rounded-md">
            Log Out
        </button>
    </div>
</div>

