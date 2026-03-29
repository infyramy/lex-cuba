<?php

namespace App\Http\Requests;

class UpdateStatuteRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|min:1',
            'slug' => 'nullable|string',
            'type' => 'sometimes|required|in:link,document',
            'url' => 'nullable|string|url',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ];
    }
}
