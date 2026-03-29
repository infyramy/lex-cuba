<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Traits\ApiResponse;
use App\Models\MemberSubscription;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    use ApiResponse;

    private function formatMember(User $user): array
    {
        $subscription = $user->subscription;

        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'photo_url'         => $user->photo_url,
            'gender'            => $user->gender,
            'phone'             => $user->phone,
            'institution'       => $user->institution,
            'work_study_status' => $user->work_study_status,
            'country'           => $user->country,
            'status'            => $user->status,
            'is_bypassed'       => $user->is_bypassed,
            'created_at'        => $user->created_at,
            'updated_at'        => $user->updated_at,
            'subscription'      => $subscription ? [
                'id'            => $subscription->id,
                'package_id'    => $subscription->package_id,
                'package_name'  => $subscription->package_name,
                'package_price' => $subscription->package_price,
                'subscribed_at' => $subscription->subscribed_at,
                'expires_at'    => $subscription->expires_at,
                'is_active'     => $subscription->isActive(),
                'notes'         => $subscription->notes,
            ] : null,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 10);
        $q       = $request->input('q');
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $status  = $request->input('status');
        $isBypassed = $request->input('is_bypassed');
        $hasSubscription = $request->input('has_subscription');

        $query = User::members()->with('subscription');

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('institution', 'like', "%{$q}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($isBypassed !== null) {
            $query->where('is_bypassed', filter_var($isBypassed, FILTER_VALIDATE_BOOLEAN));
        }

        if ($hasSubscription !== null) {
            if (filter_var($hasSubscription, FILTER_VALIDATE_BOOLEAN)) {
                $query->whereHas('subscription');
            } else {
                $query->whereDoesntHave('subscription');
            }
        }

        $total = $query->count();

        $rows = $query->orderBy($sortBy, $sortDir)
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn ($user) => $this->formatMember($user));

        return $this->sendOk($rows, [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => Hash::make($data['password']),
            'user_type'          => 'member',
            'role'               => 'member',
            'gender'             => $data['gender'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'institution'        => $data['institution'] ?? null,
            'work_study_status'  => $data['work_study_status'] ?? null,
            'country'            => $data['country'] ?? null,
            'status'             => $data['status'] ?? 'active',
            'is_bypassed'        => $data['is_bypassed'] ?? false,
        ]);

        $user->load('subscription');

        return $this->sendCreated($this->formatMember($user));
    }

    public function show(int $id): JsonResponse
    {
        $user = User::members()->with('subscription')->find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Member not found');
        }

        return $this->sendOk($this->formatMember($user));
    }

    public function update(UpdateMemberRequest $request, int $id): JsonResponse
    {
        $user = User::members()->find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Member not found');
        }

        $data = $request->validated();

        $updateData = [];
        $directFields = ['name', 'email', 'gender', 'phone', 'institution', 'work_study_status', 'country', 'status'];
        foreach ($directFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['is_bypassed'])) {
            $updateData['is_bypassed'] = $data['is_bypassed'];
        }

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        $user->load('subscription');

        return $this->sendOk($this->formatMember($user));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($request->user()->id === $id) {
            return $this->sendError(400, 'SELF_DELETE', 'You cannot delete your own account');
        }

        $user = User::members()->find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Member not found');
        }

        $user->delete();

        return $this->sendOk(['success' => true]);
    }

    /**
     * Toggle bypass flag for a member (grants full access without subscription).
     */
    public function toggleBypass(int $id): JsonResponse
    {
        $user = User::members()->find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Member not found');
        }

        $user->update(['is_bypassed' => ! $user->is_bypassed]);
        $user->load('subscription');

        return $this->sendOk($this->formatMember($user));
    }

    /**
     * Update member status (activate / suspend).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = User::members()->find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Member not found');
        }

        $status = $request->input('status');
        if (! in_array($status, ['active', 'suspended'])) {
            return $this->sendError(400, 'BAD_REQUEST', 'Status must be active or suspended');
        }

        $user->update(['status' => $status]);
        $user->load('subscription');

        return $this->sendOk($this->formatMember($user));
    }
}
