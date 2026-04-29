<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CommunicationTemplate;
use Illuminate\Http\Request;

class CommunicationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = CommunicationTemplate::query()
            ->when($request->channel, fn ($query, $channel) => $query->where('channel', $channel))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate(10);

        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $template = CommunicationTemplate::create($validated);

        return response()->json($template, 201);
    }

    public function show(CommunicationTemplate $message_template)
    {
        return response()->json($message_template);
    }

    public function update(Request $request, CommunicationTemplate $message_template)
    {
        $validated = $this->validateRequest($request, true);

        $message_template->update($validated);

        return response()->json($message_template);
    }

    public function destroy(CommunicationTemplate $message_template)
    {
        $message_template->delete();

        return response()->json(['message' => 'Template deleted successfully.']);
    }

    private function validateRequest(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'channel' => [$required, 'in:email,sms,whatsapp'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => [$required, 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
