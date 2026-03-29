<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionPaperRequest;
use App\Http\Requests\UpdateQuestionPaperRequest;
use App\Http\Traits\ApiResponse;
use App\Models\QuestionPaper;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuestionPaperController extends Controller
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
        $categoryId = $request->input('category_id');

        $query = QuestionPaper::with('category');

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
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

    public function store(StoreQuestionPaperRequest $request): JsonResponse
    {
        $data = $request->validated();
        $file = $request->file('file');

        $slug = $this->slugService->uniqueSlug(
            'question_papers',
            $data['title'],
            $data['slug'] ?? null,
        );

        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('question-papers', $filename, 'public');

        $paper = QuestionPaper::create([
            'title' => $data['title'],
            'slug' => $slug,
            'type' => $data['type'],
            'year' => $data['year'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'is_published' => $data['is_published'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $paper->load('category');

        return $this->sendCreated($paper);
    }

    public function show(int $id): JsonResponse
    {
        $paper = QuestionPaper::with('category')->find($id);

        if (! $paper) {
            return $this->sendError(404, 'NOT_FOUND', 'Question paper not found');
        }

        return $this->sendOk($paper);
    }

    public function update(UpdateQuestionPaperRequest $request, int $id): JsonResponse
    {
        $paper = QuestionPaper::find($id);

        if (! $paper) {
            return $this->sendError(404, 'NOT_FOUND', 'Question paper not found');
        }

        $data = $request->validated();

        if (isset($data['title']) || isset($data['slug'])) {
            $data['slug'] = $this->slugService->uniqueSlug(
                'question_papers',
                $data['title'] ?? $paper->title,
                $data['slug'] ?? null,
                $paper->id,
            );
        }

        $paper->update($data);
        $paper->load('category');

        return $this->sendOk($paper);
    }

    public function destroy(int $id): JsonResponse
    {
        $paper = QuestionPaper::find($id);

        if (! $paper) {
            return $this->sendError(404, 'NOT_FOUND', 'Question paper not found');
        }

        // Delete the file from storage
        if ($paper->file_path) {
            Storage::disk('public')->delete($paper->file_path);
        }

        $paper->delete();

        return $this->sendOk(['success' => true]);
    }

    /**
     * Upload/replace PDF file for an existing question paper.
     */
    public function uploadFile(Request $request, int $questionPaper): JsonResponse
    {
        $paper = QuestionPaper::find($questionPaper);

        if (! $paper) {
            return $this->sendError(404, 'NOT_FOUND', 'Question paper not found');
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:20480',
        ]);

        $file = $request->file('file');

        // Delete old file
        if ($paper->file_path) {
            Storage::disk('public')->delete($paper->file_path);
        }

        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('question-papers', $filename, 'public');

        $paper->update([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
        ]);

        $paper->load('category');

        return $this->sendOk($paper);
    }
}
