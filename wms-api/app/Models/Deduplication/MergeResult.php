<?php

namespace App\Models\Deduplication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MergeResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'entity_type',
        'source_record_id',
        'target_record_id',
        'merged_record_id',
        'merge_strategy',
        'field_selections',
        'merge_details',
        'is_reversible',
        'backup_data',
    ];

    protected $casts = [
        'field_selections' => 'json',
        'merge_details' => 'json',
        'is_reversible' => 'boolean',
        'backup_data' => 'json',
    ];

    public function match()
    {
        return $this->belongsTo(DuplicateMatch::class, 'match_id');
    }

    public function isNewRecordCreated()
    {
        return $this->merge_strategy === 'create_new';
    }

    public function isSourceKept()
    {
        return $this->merge_strategy === 'keep_source';
    }

    public function isTargetKept()
    {
        return $this->merge_strategy === 'keep_target';
    }

    public function isCustomMerge()
    {
        return $this->merge_strategy === 'custom';
    }

    public function getFieldSelections()
    {
        return $this->field_selections ?? [];
    }

    public function getMergeDetails()
    {
        return $this->merge_details ?? [];
    }

    public function getBackupData()
    {
        return $this->backup_data ?? [];
    }

    public function canBeReversed()
    {
        return $this->is_reversible;
    }
}