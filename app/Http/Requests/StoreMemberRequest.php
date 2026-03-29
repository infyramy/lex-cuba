<?php

namespace App\Http\Requests;

class StoreMemberRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'required|string|min:1',
            'email'              => 'required|email|unique:users,email',
            'password'           => 'required|string|min:8',
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
