<?php

namespace App\Http\Requests;

class UpdateNoteRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|min:1',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ];
    }
}
