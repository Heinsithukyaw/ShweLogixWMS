<?php

namespace App\Models\DataLineage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataSource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'source_code',
        'description',
        'source_type',
        'connection_details',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'connection_details' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
    ];

    public function dataFlows()
    {
        return $this->hasMany(DataFlow::class, 'source_id');
    }

    public function dataEntities()
    {
        return $this->hasMany(DataEntity::class, 'source_id');
    }

    public function isDatabase()
    {
        return in_array($this->source_type, ['mysql', 'postgresql', 'sqlserver', 'oracle', 'mongodb']);
    }

    public function isApi()
    {
        return in_array($this->source_type, ['rest_api', 'soap_api', 'graphql']);
    }

    public function isFile()
    {
        return in_array($this->source_type, ['csv', 'excel', 'json', 'xml']);
    }

    public function isIntegration()
    {
        return in_array($this->source_type, ['sap', 'salesforce', 'shopify', 'woocommerce']);
    }

    public function getConnectionCredentialsAttribute()
    {
        $credentials = $this->connection_details['credentials'] ?? [];
        
        // Mask sensitive information
        if (isset($credentials['password'])) {
            $credentials['password'] = '********';
        }
        
        if (isset($credentials['api_key'])) {
            $credentials['api_key'] = substr($credentials['api_key'], 0, 4) . '********';
        }
        
        return $credentials;
    }
}