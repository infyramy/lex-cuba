<?php

namespace App\Http\Requests;

class UpdateQuestionPaperRequest extends BaseFormRequest
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
            'type' => 'sometimes|required|in:past_year,topical',
            'year' => 'nullable|integer',
            'category_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ];
    }
}
