<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingException extends Model
{
    use HasFactory;

    protected $fillable = [
        'exception_code',
        'asn_id',
        'asn_detail_id',
        'exception_type',
        'item_id',
        'item_description',
        'severity',
        'status',
        'reported_by_id',
        'assigned_to_id',
        'reported_date',
        'description',
        'resolved_date',
    ];

    public function asn()
    {
        return $this->belongsTo(AdvancedShippingNotice::class, 'asn_id');
    }

    public function asn_detail()
    {
        return $this->belongsTo(AdvancedShippingNoticeDetail::class, 'asn_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function reported_emp()
    {
        return $this->belongsTo(Employee::class, 'reported_by_id');
    }

    public function assigned_emp()
    {
        return $this->belongsTo(Employee::class, 'assigned_to_id');
    }

}
