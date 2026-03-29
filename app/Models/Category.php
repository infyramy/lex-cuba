<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'icon_url',
        'sort_order',
        'legal_basis',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the notes for the category.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the questions for the category.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get the case summaries for the category.
     */
    public function caseSummaries(): HasMany
    {
        return $this->hasMany(CaseSummary::class);
    }

    /**
     * Get the topic-specific extra links for the category.
     */
    public function topicLinks(): HasMany
    {
        return $this->hasMany(TopicLink::class);
    }

    /**
     * Get the question papers for the category.
     */
    public function questionPapers(): HasMany
    {
        return $this->hasMany(QuestionPaper::class);
    }

    /**
     * Scope: filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Transform counts to _count format for frontend compatibility.
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        $counts = [];
        if (isset($array['notes_count'])) {
            $counts['notes'] = $array['notes_count'];
            unset($array['notes_count']);
        }
        if (isset($array['questions_count'])) {
            $counts['questions'] = $array['questions_count'];
            unset($array['questions_count']);
        }
        if (isset($array['case_summaries_count'])) {
            $counts['caseSummaries'] = $array['case_summaries_count'];
            unset($array['case_summaries_count']);
        }
        if (isset($array['question_papers_count'])) {
            $counts['questionPapers'] = $array['question_papers_count'];
            unset($array['question_papers_count']);
        }

        if (!empty($counts)) {
            $array['_count'] = $counts;
        }

        return $array;
    }
}
