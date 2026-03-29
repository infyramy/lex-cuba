<?php

namespace App\Http\Requests;

class StoreTopicLinkRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|integer|exists:categories,id',
            'title'       => 'required|string|min:1',
            'url'         => 'required|url',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ];
    }
}
