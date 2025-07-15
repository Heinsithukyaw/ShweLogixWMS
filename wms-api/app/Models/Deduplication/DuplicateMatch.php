<?php

namespace App\Models\Deduplication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DuplicateMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'execution_id',
        'match_group_id',
        'entity_type',
        'record_id_1',
        'record_id_2',
        'match_score',
        'match_details',
        'status',
        'resolution_type',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'match_score' => 'float',
        'match_details' => 'json',
        'resolved_at' => 'datetime',
    ];

    public function execution()
    {
        return $this->belongsTo(DeduplicationExecution::class, 'execution_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function mergeResult()
    {
        return $this->hasOne(MergeResult::class, 'match_id');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isResolved()
    {
        return $this->status === 'resolved';
    }

    public function isIgnored()
    {
        return $this->status === 'ignored';
    }

    public function isMerged()
    {
        return $this->resolution_type === 'merged';
    }

    public function isKept()
    {
        return $this->resolution_type === 'kept';
    }

    public function isDiscarded()
    {
        return $this->resolution_type === 'discarded';
    }

    public function isManuallyResolved()
    {
        return $this->resolved_by !== null;
    }

    public function getMatchDetails()
    {
        return $this->match_details ?? [];
    }

    public function getMatchedFields()
    {
        return $this->match_details['matched_fields'] ?? [];
    }

    public function getFieldScores()
    {
        return $this->match_details['field_scores'] ?? [];
    }
}