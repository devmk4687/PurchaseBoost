<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MessageTemplateImageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'upload' => ['required', 'image', 'max:5120'],
        ]);

        $file = $request->file('upload');
        $filename = uniqid('template_', true) . '.' . $file->getClientOriginalExtension();
        Storage::disk('public')->putFileAs('message-templates', $file, $filename);
        $publicDirectory = public_path('uploads/message-templates');

        if (! is_dir($publicDirectory)) {
            mkdir($publicDirectory, 0755, true);
        }

        copy(
            storage_path('app/public/message-templates/' . $filename),
            $publicDirectory . DIRECTORY_SEPARATOR . $filename
        );

        return response()->json([
            'url' => $this->publicImageUrl($filename),
        ]);
    }

    public function show(string $filename): BinaryFileResponse
    {
        $filename = basename($filename);
        $path = storage_path('app/public/message-templates/' . $filename);

        abort_unless(is_file($path), 404);

        return response()->file($path);
    }

    private function publicImageUrl(string $filename): string
    {
        return asset('uploads/message-templates/' . $filename);
    }
}
