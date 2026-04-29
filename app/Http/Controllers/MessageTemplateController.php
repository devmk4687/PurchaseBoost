<?php

namespace App\Http\Controllers;

use App\Models\CommunicationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $templates = CommunicationTemplate::query()
            ->when($request->channel, fn ($query, $channel) => $query->where('channel', $channel))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('message-templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('message-templates.create', [
            'template' => new CommunicationTemplate([
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        CommunicationTemplate::create($this->validatedData($request));

        return redirect()
            ->route('message-templates.index')
            ->with('success', 'Message template created successfully.');
    }

    public function edit(CommunicationTemplate $message_template): View
    {
        return view('message-templates.edit', [
            'template' => $message_template,
        ]);
    }

    public function update(Request $request, CommunicationTemplate $message_template): RedirectResponse
    {
        $message_template->update($this->validatedData($request));

        return redirect()
            ->route('message-templates.index')
            ->with('success', 'Message template updated successfully.');
    }

    public function destroy(CommunicationTemplate $message_template): RedirectResponse
    {
        $message_template->delete();

        return redirect()
            ->route('message-templates.index')
            ->with('success', 'Message template deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'in:email,sms,whatsapp'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
