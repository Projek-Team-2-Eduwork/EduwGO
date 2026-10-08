<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        Gate::authorize('viewAny', Setting::class);

        return view('admin.pengaturan.edit');
    }

    public function update(SettingRequest $request)
    {
        Gate::authorize('viewAny', Setting::class);

        $data = $request->validated();

        $textFields = [
            'brand.name' => 'brand_name',
            'brand.tagline' => 'brand_tagline',
            'brand.primary_color' => 'brand_primary_color',
            'brand.accent_color' => 'brand_accent_color',
            'contact.whatsapp' => 'contact_whatsapp',
            'contact.address' => 'contact_address',
            'contact.hours' => 'contact_hours',
            'social.instagram' => 'social_instagram',
            'social.facebook' => 'social_facebook',
            'social.twitter' => 'social_twitter',
            'booking.max_days' => 'booking_max_days',
            'booking.invoice_minutes' => 'booking_invoice_minutes',
            'booking.buffer_minutes' => 'booking_buffer_minutes',
            'content.terms' => 'content_terms',
        ];

        foreach ($textFields as $dbKey => $reqKey) {
            if (array_key_exists($reqKey, $data)) {
                Setting::updateOrCreate(['key' => $dbKey], ['value' => $data[$reqKey]]);
            }
        }

        $fileFields = [
            'brand.logo_light' => 'brand_logo_light',
            'brand.logo_dark' => 'brand_logo_dark',
            'brand.favicon' => 'brand_favicon',
        ];

        $disk = Storage::disk('public');

        foreach ($fileFields as $dbKey => $reqKey) {
            if ($request->hasFile($reqKey)) {
                $oldPath = setting($dbKey);
                $file = $request->file($reqKey);

                $filename = uniqid('setting_').'_'.time().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('settings', $filename, 'public');

                Setting::updateOrCreate(['key' => $dbKey], ['value' => 'storage/'.$path]);

                if ($oldPath && str_starts_with($oldPath, 'storage/settings/')) {
                    $oldRelative = str_replace('storage/', '', $oldPath);
                    if ($disk->exists($oldRelative)) {
                        $disk->delete($oldRelative);
                    }
                }
            }
        }

        Cache::forget('settings');
        Cache::forget('settings.all');

        return back()->with('success', 'Pengaturan toko berhasil diperbarui.');
    }
}
