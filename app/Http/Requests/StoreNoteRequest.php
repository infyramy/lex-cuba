<?php

namespace App\Http\Requests;

class StoreNoteRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:1',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'file' => 'required|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ];
    }
}
