<?php

namespace App\Http\Requests;

class MobileRegisterRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:1',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'gender' => 'required|string',
            'phone' => 'required|string',
            'institution' => 'required|string',
            'work_study_status' => 'required|in:working,studying',
            'country' => 'required|string',
        ];
    }
}
