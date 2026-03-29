<?php

namespace App\Http\Requests;

class StoreCaseSummaryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:1',
            'citation' => 'required|string|min:1',
            'summary_text' => 'required|string|min:1',
            'category_id' => 'nullable|integer|exists:categories,id',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
            'is_published' => 'nullable|boolean',
        ];
    }
}
