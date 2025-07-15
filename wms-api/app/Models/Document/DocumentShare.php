<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'share_token',
        'shared_by',
        'shared_with',
        'share_notes',
        'expires_at',
        'is_password_protected',
        'password_hash',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_password_protected' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function isExpired()
    {
        if (!$this->expires_at) {
            return false;
        }

        return now()->gt($this->expires_at);
    }

    public function verifyPassword($password)
    {
        if (!$this->is_password_protected) {
            return true;
        }

        return password_verify($password, $this->password_hash);
    }
}