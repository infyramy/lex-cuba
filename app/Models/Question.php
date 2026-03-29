<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'category_id',
        'question_text',
        'options',
        'correct_option_index',
        'explanation',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_option_index' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
