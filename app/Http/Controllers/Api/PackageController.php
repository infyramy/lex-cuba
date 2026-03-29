<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 10);
        $q       = $request->input('q');
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = Package::query();

        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        $total = $query->count();
        $rows  = $query->withCount('subscriptions')
            ->orderBy($sortBy, $sortDir)
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return $this->sendOk($rows, [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = Package::create($request->validated());

        return $this->sendCreated($package);
    }

    public function show(int $id): JsonResponse
    {
        $package = Package::withCount('subscriptions')->find($id);

        if (! $package) {
            return $this->sendError(404, 'NOT_FOUND', 'Package not found');
        }

        return $this->sendOk($package);
    }

    public function update(UpdatePackageRequest $request, int $id): JsonResponse
    {
        $package = Package::find($id);

        if (! $package) {
            return $this->sendError(404, 'NOT_FOUND', 'Package not found');
        }

        $package->update($request->validated());

        return $this->sendOk($package);
    }

    public function destroy(int $id): JsonResponse
    {
        $package = Package::find($id);

        if (! $package) {
            return $this->sendError(404, 'NOT_FOUND', 'Package not found');
        }

        // Check if any members currently use this package
        if ($package->subscriptions()->exists()) {
            return $this->sendError(409, 'PACKAGE_IN_USE', 'This package has active subscribers and cannot be deleted. Deactivate it instead.');
        }

        $package->delete();

        return $this->sendOk(['success' => true]);
    }
}
