<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaseSummaryRequest;
use App\Http\Requests\UpdateCaseSummaryRequest;
use App\Http\Traits\ApiResponse;
use App\Models\CaseSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CaseSummaryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $categoryId = $request->input('category_id');

        $query = CaseSummary::with('category');

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('citation', 'like', "%{$q}%")
                    ->orWhere('summary_text', 'like', "%{$q}%");
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

    public function store(StoreCaseSummaryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $pdfPath = null;
        if ($request->hasFile('pdf_file')) {
            $file = $request->file('pdf_file');
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . time() . '.pdf';
            $pdfPath = $file->storeAs('case-summaries', $filename, 'public');
        }

        $caseSummary = CaseSummary::create([
            'title' => $data['title'],
            'citation' => $data['citation'],
            'summary_text' => $data['summary_text'],
            'category_id' => $data['category_id'] ?? null,
            'pdf_file_path' => $pdfPath,
            'is_published' => $data['is_published'] ?? true,
        ]);

        $caseSummary->load('category');

        return $this->sendCreated($caseSummary);
    }

    public function show(int $id): JsonResponse
    {
        $caseSummary = CaseSummary::with('category')->find($id);

        if (! $caseSummary) {
            return $this->sendError(404, 'NOT_FOUND', 'Case summary not found');
        }

        return $this->sendOk($caseSummary);
    }

    public function update(UpdateCaseSummaryRequest $request, int $id): JsonResponse
    {
        $caseSummary = CaseSummary::find($id);

        if (! $caseSummary) {
            return $this->sendError(404, 'NOT_FOUND', 'Case summary not found');
        }

        $data = $request->validated();

        if ($request->hasFile('pdf_file')) {
            // Delete old PDF
            if ($caseSummary->pdf_file_path) {
                Storage::disk('public')->delete($caseSummary->pdf_file_path);
            }
            $file = $request->file('pdf_file');
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . time() . '.pdf';
            $data['pdf_file_path'] = $file->storeAs('case-summaries', $filename, 'public');
        }

        unset($data['pdf_file']);
        $caseSummary->update($data);
        $caseSummary->load('category');

        return $this->sendOk($caseSummary);
    }

    public function destroy(int $id): JsonResponse
    {
        $caseSummary = CaseSummary::find($id);

        if (! $caseSummary) {
            return $this->sendError(404, 'NOT_FOUND', 'Case summary not found');
        }

        if ($caseSummary->pdf_file_path) {
            Storage::disk('public')->delete($caseSummary->pdf_file_path);
        }

        $caseSummary->delete();

        return $this->sendOk(['success' => true]);
    }
}
