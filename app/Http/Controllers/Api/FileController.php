<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    /** POST /api/files/upload?kind=drawing|picture (multipart, field "file"). */
    public function upload(Request $r): JsonResponse
    {
        $r->validate([
            'file' => 'required|file|max:51200|mimes:pdf,jpg,jpeg,png,heic',
            'kind' => 'nullable|in:drawing,picture',
        ]);
        $folder = $r->query('kind', $r->input('kind', 'picture')) === 'drawing' ? 'drawings' : 'pictures';
        $path = $r->file('file')->store("uploads/$folder", 'public');

        // path relatif: iOS menggabungkannya dengan base URL server (IP laptop bisa berubah)
        return $this->successResponse('File berhasil diunggah.', ['url' => '/storage/'.$path], 201);
    }
}
