<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SlugService $slugService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 50);
        $q = $request->input('q');
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');
        $type = $request->input('type');

        $query = Category::query();

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        if ($type) {
            $query->ofType($type);
        }

        $total = $query->count();

        $rows = $query->withCount(['notes', 'questions', 'caseSummaries'])
            ->orderBy($sortBy, $sortDir)
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

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $slug = $this->slugService->uniqueSlug('categories', $data['name'], $data['slug'] ?? null);

        $category = Category::create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'notes',
            'icon_url' => $data['icon_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $category->loadCount(['notes', 'questions', 'caseSummaries']);

        return $this->sendCreated($category);
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::withCount(['notes', 'questions', 'caseSummaries'])->find($id);

        if (! $category) {
            return $this->sendError(404, 'NOT_FOUND', 'Category not found');
        }

        return $this->sendOk($category);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return $this->sendError(404, 'NOT_FOUND', 'Category not found');
        }

        $data = $request->validated();

        $slug = $this->slugService->uniqueSlug('categories', $data['name'], $data['slug'] ?? null, $id);

        $category->update([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? $category->type,
            'icon_url' => $data['icon_url'] ?? $category->icon_url,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        $category->loadCount(['notes', 'questions', 'caseSummaries']);

        return $this->sendOk($category);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::find($id);

        if ($category) {
            $category->delete();
        }

        return $this->sendOk(['success' => true]);
    }
}
