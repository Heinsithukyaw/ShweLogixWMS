<?php

namespace App\Models\Batch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileTransferConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'transfer_code',
        'description',
        'transfer_type',
        'direction',
        'protocol',
        'connection_details',
        'file_pattern',
        'local_directory',
        'remote_directory',
        'post_transfer_action',
        'is_active',
    ];

    protected $casts = [
        'connection_details' => 'json',
        'post_transfer_action' => 'json',
        'is_active' => 'boolean',
    ];

    public function schedules()
    {
        return $this->hasMany(FileTransferSchedule::class, 'transfer_configuration_id');
    }

    public function transfers()
    {
        return $this->hasMany(FileTransfer::class, 'transfer_configuration_id');
    }

    public function isInbound()
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound()
    {
        return $this->direction === 'outbound';
    }

    public function isFtpProtocol()
    {
        return in_array($this->protocol, ['ftp', 'ftps', 'sftp']);
    }

    public function isHttpProtocol()
    {
        return in_array($this->protocol, ['http', 'https']);
    }

    public function isS3Protocol()
    {
        return $this->protocol === 's3';
    }

    public function getConnectionCredentialsAttribute()
    {
        $credentials = $this->connection_details['credentials'] ?? [];
        
        // Mask sensitive information
        if (isset($credentials['password'])) {
            $credentials['password'] = '********';
        }
        
        if (isset($credentials['secret_key'])) {
            $credentials['secret_key'] = '********';
        }
        
        if (isset($credentials['api_key'])) {
            $credentials['api_key'] = substr($credentials['api_key'], 0, 4) . '********';
        }
        
        return $credentials;
    }
}