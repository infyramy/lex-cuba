<?php

namespace App\Http\Requests;

class UploadMediaRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:png,jpg,jpeg,gif,webp,svg,ico|max:5120',
        ];
    }
}
