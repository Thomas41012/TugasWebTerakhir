<x-layouts.app>
    <div class="min-h-screen bg-slate-950 text-white">
        <div class="mx-auto max-w-4xl px-4 py-8 lg:px-8">
            <section class="mb-8">
                <a href="{{ route('dashboard') }}" class="mb-3 inline-flex text-sm text-emerald-400 transition hover:text-emerald-300" wire:navigate>
                    ← Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl text-white">
                    {{ __('Profile Settings') }}
                </h1>
                <p class="mt-2 text-slate-400">
                    Manage your account settings, update your password, or delete your account.
                </p>
            </section>

            <div class="space-y-6">
                <div class="p-6 bg-white/5 border border-white/10 rounded-2xl backdrop-blur-xl shadow-lg">
                    <div class="max-w-xl">
                        <livewire:profile.update-profile-information-form />
                    </div>
                </div>

                <div class="p-6 bg-white/5 border border-white/10 rounded-2xl backdrop-blur-xl shadow-lg">
                    <div class="max-w-xl">
                        <livewire:profile.update-password-form />
                    </div>
                </div>

                <div class="p-6 bg-white/5 border border-white/10 rounded-2xl backdrop-blur-xl shadow-lg border-rose-500/10">
                    <div class="max-w-xl">
                        <livewire:profile.delete-user-form />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

