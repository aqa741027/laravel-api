<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $path = Storage::disk('s3')->put('uploads', $request->file('file'));

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('s3')->url($path),
        ]);
    }
}
