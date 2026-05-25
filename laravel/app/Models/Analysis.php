<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analysis extends Model
{
    use HasUuids;

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['analysis_id'];
    }

    protected $primaryKey = 'analysis_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'analysis_id',
        'resume_id',
        'user_id',
        'skills',
        'total_score',
        'strengths',
        'weaknesses',
        'missing_keywords',
        'summary',
    ];

    protected $casts = [
        'skills' => 'array',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'missing_keywords' => 'array',
    ];

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id', 'resume_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
