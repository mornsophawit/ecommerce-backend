<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:super_admin,store_admin,customer,cahier');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            // Store explicitly on the "public" disk (storage/app/public) so Flysystem
            // creates the directory/file with public-readable permissions, not the
            // private (owner-only) defaults used by the generic "local" disk.
            $path = $file->store('images', 'public');
            $url = Storage::disk('public')->url($path);

            return response()->json(['url' => $url], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'File upload failed: ' . $e->getMessage()], 500);
        }
    }
}
