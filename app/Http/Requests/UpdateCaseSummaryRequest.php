<?php

namespace App\Http\Requests;

class UpdateCaseSummaryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|min:1',
            'citation' => 'sometimes|required|string|min:1',
            'summary_text' => 'sometimes|required|string|min:1',
            'category_id' => 'nullable|integer|exists:categories,id',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
            'is_published' => 'nullable|boolean',
        ];
    }
}
