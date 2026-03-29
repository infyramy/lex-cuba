<?php

namespace App\Http\Requests;

class StoreQuestionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|integer|exists:categories,id',
            'question_text' => 'required|string|min:1',
            'options' => 'required|array|size:4',
            'options.*' => 'required|string|min:1',
            'correct_option_index' => 'required|integer|min:0|max:3',
            'explanation' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ];
    }
}
