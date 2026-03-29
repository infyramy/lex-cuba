<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTopicLinkRequest;
use App\Http\Requests\UpdateTopicLinkRequest;
use App\Http\Traits\ApiResponse;
use App\Models\TopicLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicLinkController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');

        $query = TopicLink::query();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $rows = $query->orderBy('sort_order')->orderBy('created_at')->get();

        return $this->sendOk($rows);
    }

    public function store(StoreTopicLinkRequest $request): JsonResponse
    {
        $link = TopicLink::create($request->validated());

        return $this->sendCreated($link);
    }

    public function show(int $id): JsonResponse
    {
        $link = TopicLink::find($id);

        if (! $link) {
            return $this->sendError(404, 'NOT_FOUND', 'Topic link not found');
        }

        return $this->sendOk($link);
    }

    public function update(UpdateTopicLinkRequest $request, int $id): JsonResponse
    {
        $link = TopicLink::find($id);

        if (! $link) {
            return $this->sendError(404, 'NOT_FOUND', 'Topic link not found');
        }

        $link->update($request->validated());

        return $this->sendOk($link);
    }

    public function destroy(int $id): JsonResponse
    {
        TopicLink::where('id', $id)->delete();

        return $this->sendOk(['success' => true]);
    }
}
