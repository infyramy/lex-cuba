<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'gender' => $user->gender,
            'phone' => $user->phone,
            'institution' => $user->institution,
            'work_study_status' => $user->work_study_status,
            'country' => $user->country,
            'status' => $user->status,
            'is_bypassed' => $user->is_bypassed,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $status = $request->input('status');
        $role = $request->input('role');
        $isBypassed = $request->input('is_bypassed');

        $query = User::admins();

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

        if ($role) {
            $query->where('role', $role);
        }

        if ($isBypassed !== null) {
            $query->where('is_bypassed', filter_var($isBypassed, FILTER_VALIDATE_BOOLEAN));
        }

        $total = $query->count();

        $rows = $query->orderBy($sortBy, $sortDir)
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn ($user) => $this->formatUser($user));

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Resolve role_id from role name
        $roleId = null;
        if (! empty($data['role'])) {
            $role = Role::where('name', $data['role'])->first();
            $roleId = $role?->id;
        }

        $user = User::create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => Hash::make($data['password']),
            'user_type'          => 'admin',
            'role'               => $data['role'] ?? 'admin',
            'role_id'            => $roleId,
            'is_active'          => $data['is_active'] ?? true,
            'gender'             => $data['gender'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'institution'        => $data['institution'] ?? null,
            'work_study_status'  => $data['work_study_status'] ?? null,
            'country'            => $data['country'] ?? null,
            'status'             => $data['status'] ?? 'active',
            'is_bypassed'        => $data['is_bypassed'] ?? false,
        ]);

        return $this->sendCreated($this->formatUser($user));
    }

    public function show(int $id): JsonResponse
    {
        $user = User::admins()->find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'User not found');
        }

        return $this->sendOk($this->formatUser($user));
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::admins()->find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'User not found');
        }

        $data = $request->validated();

        $updateData = [];

        $directFields = ['name', 'email', 'gender', 'phone', 'institution', 'work_study_status', 'country', 'status'];
        foreach ($directFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
            $role = Role::where('name', $data['role'])->first();
            $updateData['role_id'] = $role?->id;
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }
        if (isset($data['is_bypassed'])) {
            $updateData['is_bypassed'] = $data['is_bypassed'];
        }
        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return $this->sendOk($this->formatUser($user));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($request->user()->id === $id) {
            return $this->sendError(400, 'SELF_DELETE', 'You cannot delete your own account');
        }

        $user = User::admins()->find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'User not found');
        }

        $user->delete();

        return $this->sendOk(['success' => true]);
    }

    /**
     * Toggle bypass flag for a user (grants full access without subscription).
     */
    public function toggleBypass(int $user): JsonResponse
    {
        $userModel = User::find($user);

        if (! $userModel) {
            return $this->sendError(404, 'NOT_FOUND', 'User not found');
        }

        $userModel->update(['is_bypassed' => ! $userModel->is_bypassed]);

        return $this->sendOk($this->formatUser($userModel));
    }

    /**
     * Update user status (activate / suspend).
     */
    public function updateStatus(Request $request, int $user): JsonResponse
    {
        $userModel = User::find($user);

        if (! $userModel) {
            return $this->sendError(404, 'NOT_FOUND', 'User not found');
        }

        $status = $request->input('status');
        if (! in_array($status, ['active', 'suspended'])) {
            return $this->sendError(400, 'BAD_REQUEST', 'Status must be active or suspended');
        }

        $userModel->update(['status' => $status]);

        return $this->sendOk($this->formatUser($userModel));
    }
}
