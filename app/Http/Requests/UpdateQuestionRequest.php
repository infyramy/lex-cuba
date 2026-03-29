<?php

namespace App\Http\Requests;

class UpdateQuestionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'question_text' => 'sometimes|required|string|min:1',
            'options' => 'sometimes|required|array|size:4',
            'options.*' => 'required|string|min:1',
            'correct_option_index' => 'sometimes|required|integer|min:0|max:3',
            'explanation' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ];
    }
}
