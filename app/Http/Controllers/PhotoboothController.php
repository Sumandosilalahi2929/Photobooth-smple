<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PhotoboothController extends Controller
{
    public function index()
    {
        return Inertia::render('Photobooth/Index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required',
        ]);

        $imageData = $request->image;
        @list($type, $imageData) = explode(';', $imageData);
        @list(, $imageData) = explode(',', $imageData);

        $imageData = base64_decode($imageData);

        $imageName = 'photos/' . Str::random(20) . '.png';

        Storage::disk('public')->put($imageName, $imageData);

        return redirect()->back()->with('message', 'Foto berhasil diunggah!');
    }
}
