<?php

namespace App\Http\Requests;

class StorePackageRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                     => 'required|string|min:1',
            'description'              => 'nullable|string',
            'price'                    => 'required|numeric|min:0',
            'duration_months'          => 'required|integer|min:1',
            'chatbot_access'           => 'nullable|boolean',
            'accessible_category_ids'  => 'nullable|array',
            'accessible_category_ids.*' => 'integer|exists:categories,id',
            'is_active'                => 'nullable|boolean',
        ];
    }
}
