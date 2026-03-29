<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statute extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'url',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'description',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
