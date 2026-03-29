<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFreeLinkRequest;
use App\Http\Requests\UpdateFreeLinkRequest;
use App\Http\Traits\ApiResponse;
use App\Models\FreeLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FreeLinkController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 50);
        $q = $request->input('q');
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');

        $query = FreeLink::query();

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('url', 'like', "%{$q}%");
            });
        }

        $total = $query->count();
        $rows = $query->orderBy($sortBy, $sortDir)
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function store(StoreFreeLinkRequest $request): JsonResponse
    {
        $data = $request->validated();
        $link = FreeLink::create($data);

        return $this->sendCreated($link);
    }

    public function show(int $id): JsonResponse
    {
        $link = FreeLink::find($id);

        if (! $link) {
            return $this->sendError(404, 'NOT_FOUND', 'Free link not found');
        }

        return $this->sendOk($link);
    }

    public function update(UpdateFreeLinkRequest $request, int $id): JsonResponse
    {
        $link = FreeLink::find($id);

        if (! $link) {
            return $this->sendError(404, 'NOT_FOUND', 'Free link not found');
        }

        $link->update($request->validated());

        return $this->sendOk($link);
    }

    public function destroy(int $id): JsonResponse
    {
        FreeLink::where('id', $id)->delete();

        return $this->sendOk(['success' => true]);
    }
}
