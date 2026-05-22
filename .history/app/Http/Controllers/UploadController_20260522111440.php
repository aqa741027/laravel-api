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

    public function temporaryUrl(Request $request)
{
    $request->validate([
        'path' => 'required|string',
    ]);

    $url = Storage::disk('s3')->temporaryUrl(
        $request->path,
        now()->addMinutes(5)
    );

    return response()->json([
        'success' => true,
        'temporary_url' => $url,
        'expires_in_minutes' => 5,
    ]);
}
}
