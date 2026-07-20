<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\rules;
use function Livewire\Volt\state;

state(['password' => '']);

rules(['password' => ['required', 'string', 'current_password']]);

$deleteUser = function (Logout $logout) {
    $this->validate();

    tap(Auth::user(), $logout(...))->delete();

    $this->redirect('/', navigate: true);
};

?>

<section class="space-y-6" x-data="{ open: false }">
    <header>
        <h2 class="text-lg font-bold text-white">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-slate-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button
        type="button"
        x-on:click="open = true"
        class="py-2.5 px-5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-rose-600/25 focus:outline-none focus:ring-2 focus:ring-rose-500"
    >
        {{ __('Delete Account') }}
    </button>

    <!-- Dark Modal -->
    <div
        x-show="open"
        class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50 flex items-center justify-center"
        style="display: none;"
        x-transition:enter="ease-out duration-300"
        x-transition:leave="ease-in duration-200"
    >
        <!-- Background Overlay -->
        <div class="fixed inset-0 transform transition-all" x-on:click="open = false">
            <div class="absolute inset-0 bg-[#060d1a]/80 backdrop-blur-sm"></div>
        </div>

        <!-- Modal Content Card -->
        <div
            x-show="open"
            class="mb-6 bg-slate-900 border border-white/10 rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:w-full sm:max-w-lg p-6 relative z-10"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <form wire:submit="deleteUser" class="space-y-4">
                <h2 class="text-lg font-bold text-white">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="text-sm text-slate-400 leading-relaxed">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div>
                    <label for="delete_password" class="block text-sm font-medium text-slate-300 mb-1.5">{{ __('Password') }}</label>
                    <input
                        wire:model="password"
                        id="delete_password"
                        name="password"
                        type="password"
                        class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all duration-200"
                        placeholder="{{ __('Password') }}"
                        required
                    />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        x-on:click="open = false"
                        class="py-2.5 px-5 bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white text-sm font-semibold rounded-xl transition-all duration-200"
                    >
                        {{ __('Cancel') }}
                    </button>

                    <button
                        type="submit"
                        class="py-2.5 px-5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-rose-600/25 focus:outline-none focus:ring-2 focus:ring-rose-500 flex items-center justify-center gap-2"
                    >
                        <span wire:loading.remove wire:target="deleteUser">{{ __('Delete Account') }}</span>
                        <span wire:loading wire:target="deleteUser" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Deleting...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

