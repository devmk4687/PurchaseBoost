<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageTemplateImageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'upload' => ['required', 'image', 'max:5120'],
        ]);

        $file = $request->file('upload');
        $directory = public_path('uploads/message-templates');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = uniqid('template_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return response()->json([
            'url' => asset('uploads/message-templates/' . $filename),
        ]);
    }
}
