<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:admin,vendor');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            $path = $file->store('public/images');
            $url = Storage::url($path);

            return response()->json(['url' => $url], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'File upload failed: ' . $e->getMessage()], 500);
        }
    }
}
