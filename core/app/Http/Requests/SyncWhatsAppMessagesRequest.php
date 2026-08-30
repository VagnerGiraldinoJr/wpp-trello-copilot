<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncWhatsAppMessagesRequest extends FormRequest
{
    /**
     * No authentication is required for this local-first tool.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact' => ['required', 'string', 'max:255'],
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.body' => ['required', 'string'],
            'messages.*.sent_at' => ['nullable', 'string'],
            'synced_at' => ['required'],
        ];
    }
}
