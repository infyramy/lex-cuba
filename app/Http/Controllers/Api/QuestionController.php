<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');
        $categoryId = $request->input('category_id');

        $query = Question::with('category');

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('question_text', 'like', "%{$q}%");
            });
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

    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $question = Question::create($data);
        $question->load('category');

        return $this->sendCreated($question);
    }

    public function show(int $id): JsonResponse
    {
        $question = Question::with('category')->find($id);

        if (! $question) {
            return $this->sendError(404, 'NOT_FOUND', 'Question not found');
        }

        return $this->sendOk($question);
    }

    public function update(UpdateQuestionRequest $request, int $id): JsonResponse
    {
        $question = Question::find($id);

        if (! $question) {
            return $this->sendError(404, 'NOT_FOUND', 'Question not found');
        }

        $question->update($request->validated());
        $question->load('category');

        return $this->sendOk($question);
    }

    public function destroy(int $id): JsonResponse
    {
        Question::where('id', $id)->delete();

        return $this->sendOk(['success' => true]);
    }
}
