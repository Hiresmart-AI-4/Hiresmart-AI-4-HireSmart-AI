<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumeVersion extends Model
{
    use HasUuids;

    protected $primaryKey = 'version_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'version_id',
        'resume_id',
        'version_number',
        'file_url',
        'changes',
        'notes',
        'ats_score',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id', 'resume_id');
    }
}
