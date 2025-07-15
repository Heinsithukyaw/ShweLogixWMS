<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_category_id',
        'title',
        'description',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'metadata',
        'reference_type',
        'reference_id',
        'uploaded_by',
        'expires_at',
        'is_confidential',
    ];

    protected $casts = [
        'metadata' => 'json',
        'file_size' => 'integer',
        'expires_at' => 'datetime',
        'is_confidential' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number', 'desc');
    }

    public function permissions()
    {
        return $this->hasMany(DocumentPermission::class);
    }

    public function shares()
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function latestVersion()
    {
        return $this->hasOne(DocumentVersion::class)->orderBy('version_number', 'desc');
    }

    public function getFullPathAttribute()
    {
        return storage_path('app/' . $this->file_path);
    }
}