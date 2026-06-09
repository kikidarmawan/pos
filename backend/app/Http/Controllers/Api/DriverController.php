<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $drivers = Driver::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('no_ktp', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate($request->per_page ?? 50);

        return response()->json($drivers);
    }

    private function saveAsWebP($file, $folder)
    {
        $filename = $folder . '/' . uniqid() . '.webp';
        $image = @\imagecreatefromstring(file_get_contents($file->path()));
        
        if (!$image) {
            // Jika gagal load gambar, fallback save biasa
            return $file->store($folder, 'public');
        }

        ob_start();
        \imagewebp($image, null, 80);
        $content = ob_get_contents();
        ob_end_clean();
        
        Storage::disk('public')->put($filename, $content);
        imagedestroy($image);
        
        return $filename;
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_ktp' => 'required|string|size:16|unique:drivers,no_ktp',
            'name' => 'required|string|max:60',
            'address' => 'nullable|string',
            'gender' => 'nullable|in:L,P',
            'birth_place' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
            'phone' => 'required|string|max:20|unique:drivers,phone',
            'ktp_photo' => 'nullable|image|max:2048',
            'photo' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['ktp_photo', 'photo']);

        if ($request->hasFile('ktp_photo')) {
            $data['ktp_photo'] = $this->saveAsWebP($request->file('ktp_photo'), 'drivers/ktp');
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->saveAsWebP($request->file('photo'), 'drivers/photo');
        }

        $driver = Driver::create($data);

        return response()->json([
            'message' => 'Sopir berhasil ditambahkan',
            'driver' => $driver,
        ], 201);
    }

    public function show(Driver $driver)
    {
        return response()->json($driver);
    }

    public function update(Request $request, Driver $driver)
    {
        $request->validate([
            'no_ktp' => 'required|string|size:16|unique:drivers,no_ktp,' . $driver->id,
            'name' => 'required|string|max:60',
            'address' => 'nullable|string',
            'gender' => 'nullable|in:L,P',
            'birth_place' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
            'phone' => 'required|string|max:20|unique:drivers,phone,' . $driver->id,
            'ktp_photo' => 'nullable|image|max:2048',
            'photo' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['ktp_photo', 'photo']);

        if ($request->hasFile('ktp_photo')) {
            if ($driver->ktp_photo) {
                Storage::disk('public')->delete($driver->ktp_photo);
            }
            $data['ktp_photo'] = $this->saveAsWebP($request->file('ktp_photo'), 'drivers/ktp');
        }

        if ($request->hasFile('photo')) {
            if ($driver->photo) {
                Storage::disk('public')->delete($driver->photo);
            }
            $data['photo'] = $this->saveAsWebP($request->file('photo'), 'drivers/photo');
        }

        $driver->update($data);

        return response()->json([
            'message' => 'Sopir berhasil diperbarui',
            'driver' => $driver,
        ]);
    }

    public function destroy(Driver $driver)
    {
        if ($driver->ktp_photo) {
            Storage::disk('public')->delete($driver->ktp_photo);
        }
        if ($driver->photo) {
            Storage::disk('public')->delete($driver->photo);
        }

        $driver->delete();

        return response()->json([
            'message' => 'Sopir berhasil dihapus',
        ]);
    }
}
