<?php

namespace App\Http\Requests;

class UpdateFreeLinkRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|min:1',
            'url' => 'sometimes|required|url',
            'icon_image_path' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ];
    }
}
