<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasUuids;

    protected $primaryKey = 'job_id';
    protected $table = 'job_posts';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'job_id',
        'recruiter_id',
        'title',
        'company',
        'location',
        'description',
        'required_skills',
        'nice_to_have_skills',
        'employment_type',
        'experience_level',
        'salary_min',
        'salary_max',
        'application_deadline',
        'is_active',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'nice_to_have_skills' => 'array',
        'application_deadline' => 'date',
        'is_active' => 'boolean',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
    ];

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(JobMatch::class, 'job_id', 'job_id');
    }
}
