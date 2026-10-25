<x-app-layout title="Profil">
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight text-[var(--ink)]">
            Profil
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Ringkasan booking: total + aktif, link ke Pesanan saya --}}
            <div class="border-b border-[var(--line)] px-4 py-8 sm:px-0">
                @include('profile.partials.booking-summary-card')
            </div>

            <div class="border-b border-[var(--line)] px-4 py-8 sm:px-0">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="border-b border-[var(--line)] px-4 py-8 sm:px-0">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="border-b border-[var(--line)] px-4 py-8 sm:px-0">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
