<?php

namespace App\Http\Requests;

class StoreStatuteRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:1',
            'slug' => 'nullable|string',
            'type' => 'required|in:link,document',
            'url' => 'nullable|string|url|required_if:type,link',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:20480|required_if:type,document',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ];
    }
}
