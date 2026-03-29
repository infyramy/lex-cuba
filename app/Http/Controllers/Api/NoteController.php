<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NoteController extends Controller
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

        $query = Note::with('category');

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
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

    public function store(StoreNoteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $file = $request->file('file');

        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('notes', $filename, 'public');

        $note = Note::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_published' => $data['is_published'] ?? true,
        ]);

        $note->load('category');

        return $this->sendCreated($note);
    }

    public function show(int $id): JsonResponse
    {
        $note = Note::with('category')->find($id);

        if (! $note) {
            return $this->sendError(404, 'NOT_FOUND', 'Note not found');
        }

        return $this->sendOk($note);
    }

    public function update(UpdateNoteRequest $request, int $id): JsonResponse
    {
        $note = Note::find($id);

        if (! $note) {
            return $this->sendError(404, 'NOT_FOUND', 'Note not found');
        }

        $note->update($request->validated());
        $note->load('category');

        return $this->sendOk($note);
    }

    public function destroy(int $id): JsonResponse
    {
        $note = Note::find($id);

        if (! $note) {
            return $this->sendError(404, 'NOT_FOUND', 'Note not found');
        }

        // Delete the file from storage
        if ($note->file_path) {
            Storage::disk('public')->delete($note->file_path);
        }

        $note->delete();

        return $this->sendOk(['success' => true]);
    }

    /**
     * Upload/replace file for an existing note.
     */
    public function uploadFile(Request $request, int $note): JsonResponse
    {
        $noteModel = Note::find($note);

        if (! $noteModel) {
            return $this->sendError(404, 'NOT_FOUND', 'Note not found');
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpeg,jpg,png|max:10240',
        ]);

        $file = $request->file('file');

        // Delete old file
        if ($noteModel->file_path) {
            Storage::disk('public')->delete($noteModel->file_path);
        }

        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('notes', $filename, 'public');

        $noteModel->update([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
        ]);

        $noteModel->load('category');

        return $this->sendOk($noteModel);
    }
}
