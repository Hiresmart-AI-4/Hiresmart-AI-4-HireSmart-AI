<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobMatch extends Model
{
    use HasUuids;

    protected $primaryKey = 'match_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'match_id',
        'user_id',
        'job_id',
        'resume_id',
        'match_score',
        'skill_match',
        'missing_skills',
        'recommendations',
        'is_viewed',
        'is_applied',
    ];

    protected $casts = [
        'skill_match' => 'array',
        'missing_skills' => 'array',
        'recommendations' => 'array',
        'is_viewed' => 'boolean',
        'is_applied' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id', 'resume_id');
    }
}
