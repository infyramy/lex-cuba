<?php

namespace App\Http\Requests;

class UpdatePackageRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                     => 'sometimes|string|min:1',
            'description'              => 'nullable|string',
            'price'                    => 'sometimes|numeric|min:0',
            'duration_months'          => 'sometimes|integer|min:1',
            'chatbot_access'           => 'nullable|boolean',
            'accessible_category_ids'  => 'nullable|array',
            'accessible_category_ids.*' => 'integer|exists:categories,id',
            'is_active'                => 'nullable|boolean',
        ];
    }
}
