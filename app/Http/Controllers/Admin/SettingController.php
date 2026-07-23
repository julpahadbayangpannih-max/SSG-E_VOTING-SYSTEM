<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsActivity;
use App\Models\Setting;
use App\Support\Branding;
use App\Support\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    use LogsActivity;

    private function admin()
    {
        return Auth::guard('admin')->user();
    }

    public function index()
    {
        $brand = Branding::get();
        $licenseActivated = License::isActivated();
        $licenseKey = Setting::getValue('license_key');

        return view('admin.settings', compact('brand', 'licenseActivated', 'licenseKey'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_short_name' => ['required', 'string', 'max:50'],
            'school_tagline' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        Setting::setValue('school_name', $data['school_name']);
        Setting::setValue('school_short_name', $data['school_short_name']);
        Setting::setValue('school_tagline', $data['school_tagline'] ?? '');

        if ($request->hasFile('logo')) {
            $oldPath = Setting::getValue('school_logo_path');
            $path = $request->file('logo')->store('branding', 'public');
            Setting::setValue('school_logo_path', $path);

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        Branding::forget();

        $admin = $this->admin();
        $this->auditLog($request, 'settings_updated', 'admin', $admin->id, $admin->name, [
            'school_name' => $data['school_name'],
            'logo_changed' => $request->hasFile('logo'),
        ]);

        return redirect()->route('admin.settings.index')->with('status', 'Branding settings saved.');
    }
}
