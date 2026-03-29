<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\MobileRegisterRequest;
use App\Http\Traits\ApiResponse;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * Register a new member account and return a bearer token.
     */
    public function register(MobileRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'user_type' => 'member',
            'role' => 'member',
            'gender' => $data['gender'] ?? null,
            'phone' => $data['phone'] ?? null,
            'institution' => $data['institution'] ?? null,
            'work_study_status' => $data['work_study_status'] ?? null,
            'country' => $data['country'] ?? null,
            'status' => 'active',
            'is_bypassed' => false,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        $this->auditService->logAuth('register', $user);

        return $this->sendCreated([
            'user' => $this->memberPayload($user),
            'token' => $token,
        ]);
    }

    /**
     * Authenticate a member and return a bearer token (no session).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])
            ->where('user_type', 'member')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->sendError(401, 'INVALID_CREDENTIALS', 'Invalid email or password');
        }

        if ($user->status !== 'active') {
            return $this->sendError(403, 'ACCOUNT_SUSPENDED', 'Your account has been suspended');
        }

        $token = $user->createToken('mobile')->plainTextToken;

        $this->auditService->logAuth('login', $user);

        return $this->sendOk([
            'user' => $this->memberPayload($user),
            'token' => $token,
        ]);
    }

    /**
     * Return the currently authenticated member's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('subscription');

        return $this->sendOk([
            'user' => $this->memberPayload($user),
        ]);
    }

    /**
     * Update the authenticated member's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $fields = ['name', 'gender', 'phone', 'institution', 'work_study_status', 'country'];
        $data = [];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        if (! empty($data)) {
            $user->update($data);
            $user->refresh();
        }

        $user->load('subscription');

        return $this->sendOk([
            'user' => $this->memberPayload($user),
        ]);
    }

    /**
     * Revoke the current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->auditService->logAuth('logout', $user);

        $request->user()->currentAccessToken()->delete();

        return $this->sendOk(['success' => true]);
    }

    /**
     * Rotate the current token (delete old, create new).
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        $token = $request->user()->createToken('mobile')->plainTextToken;

        return $this->sendOk([
            'token' => $token,
        ]);
    }

    /**
     * Revoke all tokens for the authenticated user (logout everywhere).
     */
    public function revokeAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->sendOk(['success' => true]);
    }

    /**
     * Build the member payload for API responses.
     */
    protected function memberPayload(User $user): array
    {
        $subscription = $user->relationLoaded('subscription') ? $user->subscription : null;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'photo_url' => $user->photo_url,
            'gender' => $user->gender,
            'phone' => $user->phone,
            'institution' => $user->institution,
            'work_study_status' => $user->work_study_status,
            'country' => $user->country,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'package_id' => $subscription->package_id,
                'package_name' => $subscription->package_name,
                'package_price' => $subscription->package_price,
                'subscribed_at' => $subscription->subscribed_at,
                'expires_at' => $subscription->expires_at,
                'is_active' => $subscription->isActive(),
                'notes' => $subscription->notes,
            ] : null,
            'accessible_category_ids' => $this->resolveAccessibleCategories($subscription),
        ];
    }

    /**
     * Resolve which category IDs a member can access based on their subscription.
     */
    protected function resolveAccessibleCategories(?MemberSubscription $subscription): array
    {
        if (! $subscription || ! $subscription->isActive()) {
            return [];
        }

        $package = Package::find($subscription->package_id);

        if (! $package || ! is_array($package->accessible_category_ids)) {
            return [];
        }

        return $package->accessible_category_ids;
    }
}
