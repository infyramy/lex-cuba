<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStatuteRequest;
use App\Http\Requests\UpdateStatuteRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Statute;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StatuteController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SlugService $slugService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');
        $type = $request->input('type');

        $query = Statute::query();

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
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

    public function store(StoreStatuteRequest $request): JsonResponse
    {
        $data = $request->validated();

        $slug = $this->slugService->uniqueSlug(
            'statutes',
            $data['title'],
            $data['slug'] ?? null,
        );

        $createData = [
            'title' => $data['title'],
            'slug' => $slug,
            'type' => $data['type'],
            'url' => $data['url'] ?? null,
            'description' => $data['description'] ?? null,
            'is_published' => $data['is_published'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ];

        if ($data['type'] === 'document' && $request->hasFile('file')) {
            $file = $request->file('file');

            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . time() . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs('statutes', $filename, 'public');

            $createData['file_path'] = $path;
            $createData['file_name'] = $file->getClientOriginalName();
            $createData['file_type'] = $file->getClientOriginalExtension();
            $createData['file_size'] = $file->getSize();
        }

        $statute = Statute::create($createData);

        return $this->sendCreated($statute);
    }

    public function show(int $id): JsonResponse
    {
        $statute = Statute::find($id);

        if (! $statute) {
            return $this->sendError(404, 'NOT_FOUND', 'Statute not found');
        }

        return $this->sendOk($statute);
    }

    public function update(UpdateStatuteRequest $request, int $id): JsonResponse
    {
        $statute = Statute::find($id);

        if (! $statute) {
            return $this->sendError(404, 'NOT_FOUND', 'Statute not found');
        }

        $data = $request->validated();

        if (isset($data['title']) || isset($data['slug'])) {
            $data['slug'] = $this->slugService->uniqueSlug(
                'statutes',
                $data['title'] ?? $statute->title,
                $data['slug'] ?? null,
                $statute->id,
            );
        }

        // If a new file is uploaded, delete the old one and store the new one
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            if ($statute->file_path) {
                Storage::disk('public')->delete($statute->file_path);
            }

            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . time() . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs('statutes', $filename, 'public');

            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
        }

        $statute->update($data);

        return $this->sendOk($statute);
    }

    public function destroy(int $id): JsonResponse
    {
        $statute = Statute::find($id);

        if (! $statute) {
            return $this->sendError(404, 'NOT_FOUND', 'Statute not found');
        }

        // Delete the file from storage if exists
        if ($statute->file_path) {
            Storage::disk('public')->delete($statute->file_path);
        }

        $statute->delete();

        return $this->sendOk(['success' => true]);
    }

    /**
     * Upload/replace file for an existing statute.
     */
    public function uploadFile(Request $request, int $statute): JsonResponse
    {
        $statuteModel = Statute::find($statute);

        if (! $statuteModel) {
            return $this->sendError(404, 'NOT_FOUND', 'Statute not found');
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx|max:20480',
        ]);

        $file = $request->file('file');

        // Delete old file
        if ($statuteModel->file_path) {
            Storage::disk('public')->delete($statuteModel->file_path);
        }

        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('statutes', $filename, 'public');

        $statuteModel->update([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
        ]);

        return $this->sendOk($statuteModel);
    }
}
