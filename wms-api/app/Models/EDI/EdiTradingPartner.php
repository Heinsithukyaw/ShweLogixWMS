<?php

namespace App\Models\EDI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BusinessParty;

class EdiTradingPartner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'partner_code',
        'description',
        'business_party_id',
        'edi_standard',
        'edi_version',
        'connection_details',
        'is_active',
    ];

    protected $casts = [
        'connection_details' => 'json',
        'is_active' => 'boolean',
    ];

    public function businessParty()
    {
        return $this->belongsTo(BusinessParty::class);
    }

    public function mappings()
    {
        return $this->hasMany(EdiMapping::class, 'trading_partner_id');
    }

    public function transactions()
    {
        return $this->hasMany(EdiTransaction::class, 'trading_partner_id');
    }

    public function getConnectionTypeAttribute()
    {
        return $this->connection_details['type'] ?? 'unknown';
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