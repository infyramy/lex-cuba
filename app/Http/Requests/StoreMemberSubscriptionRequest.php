<?php

namespace App\Http\Requests;

class StoreMemberSubscriptionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id'    => 'required|integer|exists:packages,id',
            'subscribed_at' => 'nullable|date',
            'expires_at'    => 'nullable|date|after:subscribed_at',
            'notes'         => 'nullable|string|max:500',
        ];
    }
}
