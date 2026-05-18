<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Resume extends Model
{
    use HasUuids;

    protected $primaryKey = 'resume_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'resume_id',
        'user_id',
        'title',
        'file_url',
        'original_filename',
        'file_type',
        'file_size',
        'parsed_content',
        'parsed_data',
        'ats_score',
        'version',
        'is_active',
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(Analysis::class, 'resume_id', 'resume_id')->latestOfMany('created_at');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class, 'resume_id', 'resume_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ResumeVersion::class, 'resume_id', 'resume_id');
    }
}
