<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionPaper extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'year',
        'category_id',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'file_size' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
