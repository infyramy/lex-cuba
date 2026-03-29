<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberSubscriptionRequest;
use App\Http\Traits\ApiResponse;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class MemberSubscriptionController extends Controller
{
    use ApiResponse;

    /**
     * Get a member's current subscription.
     */
    public function show(int $memberId): JsonResponse
    {
        $user = User::members()->find($memberId);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Member not found');
        }

        $subscription = MemberSubscription::where('user_id', $memberId)
            ->with('package')
            ->first();

        if (! $subscription) {
            return $this->sendOk(null);
        }

        return $this->sendOk($this->formatSubscription($subscription));
    }

    /**
     * Assign or replace a member's subscription.
     */
    public function store(StoreMemberSubscriptionRequest $request, int $memberId): JsonResponse
    {
        $user = User::members()->find($memberId);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Member not found');
        }

        $data      = $request->validated();
        $package   = Package::findOrFail($data['package_id']);
        $startDate = isset($data['subscribed_at'])
            ? Carbon::parse($data['subscribed_at'])
            : Carbon::now();

        // Use admin-provided expiry, or auto-calculate from package duration
        $expiresAt = isset($data['expires_at'])
            ? Carbon::parse($data['expires_at'])
            : $startDate->copy()->addMonths($package->duration_months);

        // Upsert: one subscription per member
        $subscription = MemberSubscription::updateOrCreate(
            ['user_id' => $memberId],
            [
                'package_id'    => $package->id,
                'package_name'  => $package->name,
                'package_price' => $package->price,
                'subscribed_at' => $startDate,
                'expires_at'    => $expiresAt,
                'notes'         => $data['notes'] ?? null,
            ]
        );

        $subscription->load('package');

        return $this->sendCreated($this->formatSubscription($subscription));
    }

    /**
     * Remove a member's subscription.
     */
    public function destroy(int $memberId): JsonResponse
    {
        $user = User::members()->find($memberId);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Member not found');
        }

        MemberSubscription::where('user_id', $memberId)->delete();

        return $this->sendOk(['success' => true]);
    }

    private function formatSubscription(MemberSubscription $sub): array
    {
        return [
            'id'            => $sub->id,
            'user_id'       => $sub->user_id,
            'package_id'    => $sub->package_id,
            'package_name'  => $sub->package_name,
            'package_price' => $sub->package_price,
            'subscribed_at' => $sub->subscribed_at,
            'expires_at'    => $sub->expires_at,
            'is_active'     => $sub->isActive(),
            'notes'         => $sub->notes,
            'package'       => $sub->package,
        ];
    }
}
