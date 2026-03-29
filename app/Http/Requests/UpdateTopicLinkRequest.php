<?php

namespace App\Http\Requests;

class UpdateTopicLinkRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'      => 'sometimes|string|min:1',
            'url'        => 'sometimes|url',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ];
    }
}
