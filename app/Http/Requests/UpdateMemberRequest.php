<?php

namespace App\Http\Requests;

class UpdateMemberRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'sometimes|string|min:1',
            'email'              => 'sometimes|email|unique:users,email,' . $this->route('member'),
            'password'           => 'sometimes|nullable|string|min:8',
            'gender'             => 'nullable|string',
            'phone'              => 'nullable|string',
            'institution'        => 'nullable|string',
            'work_study_status'  => 'nullable|in:working,studying',
            'country'            => 'nullable|string',
            'status'             => 'nullable|in:active,suspended',
            'is_bypassed'        => 'nullable|boolean',
        ];
    }
}
