<?php

namespace App\Models\EDI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdocConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'idoc_type',
        'idoc_version',
        'description',
        'segment_structure',
        'direction',
        'connection_details',
        'is_active',
    ];

    protected $casts = [
        'segment_structure' => 'json',
        'connection_details' => 'json',
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(IdocTransaction::class, 'idoc_configuration_id');
    }

    public function isInbound()
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound()
    {
        return $this->direction === 'outbound';
    }

    public function getTypeAndVersionAttribute()
    {
        return "{$this->idoc_type} {$this->idoc_version}";
    }

    public function getConnectionCredentialsAttribute()
    {
        $credentials = $this->connection_details['credentials'] ?? [];
        
        // Mask sensitive information
        if (isset($credentials['password'])) {
            $credentials['password'] = '********';
        }
        
        if (isset($credentials['client_secret'])) {
            $credentials['client_secret'] = '********';
        }
        
        return $credentials;
    }
}