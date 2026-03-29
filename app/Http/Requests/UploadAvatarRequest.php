<?php

namespace App\Http\Requests;

class UploadAvatarRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:png,jpg,jpeg,gif,webp|max:2048',
        ];
    }
}
