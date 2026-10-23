<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">
            Pengaturan Toko
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ tab: 'brand' }">
        <div class="max-w-7xl mx-auto space-y-6">
            @if(session('success'))
                <x-alert type="success">
                    {{ session('success') }}
                </x-alert>
            @endif

            <div class="card-surface overflow-hidden">
                <div class="flex flex-col md:flex-row">
                    <!-- Sidebar Tabs -->
                    <div class="space-y-2 border-b border-[var(--border)] p-4 md:w-1/4 md:border-b-0 md:border-e">
                        <button type="button" @click="tab = 'brand'" :class="{ 'bg-[var(--coral-300)] text-[var(--navy-900)]': tab === 'brand', 'hover:bg-[var(--bg)]': tab !== 'brand' }" class="w-full rounded-md px-4 py-3 text-left font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">
                            Identitas & Brand
                        </button>
                        <button type="button" @click="tab = 'kontak'" :class="{ 'bg-[var(--coral-300)] text-[var(--navy-900)]': tab === 'kontak', 'hover:bg-[var(--bg)]': tab !== 'kontak' }" class="w-full rounded-md px-4 py-3 text-left font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">
                            Kontak & Sosial Media
                        </button>
                        <button type="button" @click="tab = 'aturan'" :class="{ 'bg-[var(--coral-300)] text-[var(--navy-900)]': tab === 'aturan', 'hover:bg-[var(--bg)]': tab !== 'aturan' }" class="w-full rounded-md px-4 py-3 text-left font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">
                            Aturan Sewa & S&K
                        </button>
                    </div>

                    <!-- Form Content -->
                    <div class="p-6 md:w-3/4">
                    <form method="POST" action="{{ route('admin.pengaturan.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- TAB: BRAND -->
                        <div x-show="tab === 'brand'" x-cloak class="space-y-6">
                            <div>
                                <x-input-label for="brand_name" value="Nama Toko (Brand) *" />
                                <x-text-input id="brand_name" name="brand_name" type="text" class="mt-1 block w-full" :value="old('brand_name', setting('brand.name', 'EduwGo'))" required />
                                <x-input-error :messages="$errors->get('brand_name')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="brand_tagline" value="Slogan (Tagline)" />
                                <x-text-input id="brand_tagline" name="brand_tagline" type="text" class="mt-1 block w-full" :value="old('brand_tagline', setting('brand.tagline'))" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="brand_primary_color" value="Warna Utama (Hex)" />
                                    <x-text-input id="brand_primary_color" name="brand_primary_color" type="text" placeholder="#1e3a8a" class="mt-1 block w-full font-mono" :value="old('brand_primary_color', setting('brand.primary_color'))" />
                                    <x-input-error :messages="$errors->get('brand_primary_color')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="brand_accent_color" value="Warna Aksen (Hex)" />
                                    <x-text-input id="brand_accent_color" name="brand_accent_color" type="text" placeholder="#f97316" class="mt-1 block w-full font-mono" :value="old('brand_accent_color', setting('brand.accent_color'))" />
                                    <x-input-error :messages="$errors->get('brand_accent_color')" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="brand_description" value="Deskripsi Singkat (Profil Perusahaan)" />
                                <textarea id="brand_description" name="brand_description" rows="3" class="mt-1 block w-full rounded-md shadow-sm">{{ old('brand_description', setting('brand.description')) }}</textarea>
                                <x-input-error :messages="$errors->get('brand_description')" class="mt-2" />
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="brand_logo_light" value="Logo Mode Terang (Max 512KB)" />
                                    <input type="file" id="brand_logo_light" name="brand_logo_light" accept=".png,.jpg,.svg,.ico" class="mt-1 block w-full text-sm text-[var(--ink-muted)]">
                                    <x-input-error :messages="$errors->get('brand_logo_light')" class="mt-2" />
                                    @if(setting('brand.logo_light')) <p class="text-xs text-[var(--ink-muted)] mt-1">Logo saat ini telah terpasang.</p> @endif
                                </div>
                                <div>
                                    <x-input-label for="brand_logo_dark" value="Logo Mode Gelap (Max 512KB)" />
                                    <input type="file" id="brand_logo_dark" name="brand_logo_dark" accept=".png,.jpg,.svg,.ico" class="mt-1 block w-full text-sm text-[var(--ink-muted)]">
                                    <x-input-error :messages="$errors->get('brand_logo_dark')" class="mt-2" />
                                    @if(setting('brand.logo_dark')) <p class="text-xs text-[var(--ink-muted)] mt-1">Logo dark saat ini telah terpasang.</p> @endif
                                </div>
                                <div>
                                    <x-input-label for="brand_favicon" value="Favicon (Max 512KB)" />
                                    <input type="file" id="brand_favicon" name="brand_favicon" accept=".png,.jpg,.svg,.ico" class="mt-1 block w-full text-sm text-[var(--ink-muted)]">
                                    <x-input-error :messages="$errors->get('brand_favicon')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- TAB: KONTAK -->
                        <div x-show="tab === 'kontak'" x-cloak class="space-y-6">
                            <div>
                                <x-input-label for="contact_whatsapp" value="WhatsApp Admin (62xxx) *" />
                                <x-text-input id="contact_whatsapp" name="contact_whatsapp" type="text" class="mt-1 block w-full" :value="old('contact_whatsapp', setting('contact.whatsapp', '6281234567890'))" required />
                                <p class="text-xs text-[var(--ink-muted)] mt-1">Wajib diawali angka 62 tanpa spasi/plus.</p>
                                <x-input-error :messages="$errors->get('contact_whatsapp')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="contact_address" value="Alamat Toko" />
                                <textarea id="contact_address" name="contact_address" rows="3" class="mt-1 block w-full rounded-md shadow-sm">{{ old('contact_address', setting('contact.address')) }}</textarea>
                            </div>

                            <div>
                                <x-input-label for="contact_hours" value="Jam Operasional" />
                                <x-text-input id="contact_hours" name="contact_hours" type="text" class="mt-1 block w-full" placeholder="Senin - Jumat: 08:00 - 17:00" :value="old('contact_hours', setting('contact.hours'))" />
                            </div>

                            <hr class="border-gray-200 dark:border-gray-700">

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="social_instagram" value="Instagram (Username)" />
                                    <x-text-input id="social_instagram" name="social_instagram" type="text" class="mt-1 block w-full" :value="old('social_instagram', setting('social.instagram'))" />
                                </div>
                                <div>
                                    <x-input-label for="social_facebook" value="Facebook (Username)" />
                                    <x-text-input id="social_facebook" name="social_facebook" type="text" class="mt-1 block w-full" :value="old('social_facebook', setting('social.facebook'))" />
                                </div>
                                <div>
                                    <x-input-label for="social_twitter" value="Twitter/X (Username)" />
                                    <x-text-input id="social_twitter" name="social_twitter" type="text" class="mt-1 block w-full" :value="old('social_twitter', setting('social.twitter'))" />
                                </div>
                            </div>
                        </div>

                        <!-- TAB: ATURAN -->
                        <div x-show="tab === 'aturan'" x-cloak class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="booking_max_days" value="Maksimal Sewa (Hari) *" />
                                    <x-text-input id="booking_max_days" name="booking_max_days" type="number" min="1" max="14" class="mt-1 block w-full" :value="old('booking_max_days', setting('booking.max_days', 5))" required />
                                    <x-input-error :messages="$errors->get('booking_max_days')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="booking_invoice_minutes" value="Batas Bayar (Menit) *" />
                                    <x-text-input id="booking_invoice_minutes" name="booking_invoice_minutes" type="number" min="15" max="1440" class="mt-1 block w-full" :value="old('booking_invoice_minutes', setting('booking.invoice_minutes', 60))" required />
                                    <x-input-error :messages="$errors->get('booking_invoice_minutes')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="booking_buffer_minutes" value="Jeda Antar Sewa (Menit) *" />
                                    <x-text-input id="booking_buffer_minutes" name="booking_buffer_minutes" type="number" min="0" max="720" class="mt-1 block w-full" :value="old('booking_buffer_minutes', setting('booking.buffer_minutes', 60))" required />
                                    <x-input-error :messages="$errors->get('booking_buffer_minutes')" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="content_terms" value="Syarat & Ketentuan (1 Poin per baris)" />
                                <textarea id="content_terms" name="content_terms" rows="8" class="mt-1 block w-full rounded-md shadow-sm">{{ old('content_terms', setting('content.terms')) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <x-primary-button>Simpan Pengaturan</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>