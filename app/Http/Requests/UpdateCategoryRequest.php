<?php

namespace App\Http\Requests;

class UpdateCategoryRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|min:1',
            'slug' => 'nullable|string',
            'description' => 'nullable|string',
            'type' => 'nullable|string',
            'icon_url' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ];
    }
}
