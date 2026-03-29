<?php

namespace App\Http\Requests;

class UpdateUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->route('user');

        return [
            'name' => 'sometimes|required|string|min:1',
            'email' => 'sometimes|required|email|unique:users,email,'.$userId,
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|string|min:1',
            'is_active' => 'nullable|boolean',
            'gender' => 'nullable|string',
            'phone' => 'nullable|string',
            'institution' => 'nullable|string',
            'work_study_status' => 'nullable|in:working,studying',
            'country' => 'nullable|string',
            'status' => 'nullable|in:active,suspended',
            'is_bypassed' => 'nullable|boolean',
        ];
    }
}
