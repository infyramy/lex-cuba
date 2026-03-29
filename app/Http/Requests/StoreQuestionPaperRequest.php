<?php

namespace App\Http\Requests;

class StoreQuestionPaperRequest extends BaseFormRequest
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
            'type' => 'required|in:past_year,topical',
            'year' => 'nullable|integer',
            'category_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf|max:20480',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ];
    }
}
