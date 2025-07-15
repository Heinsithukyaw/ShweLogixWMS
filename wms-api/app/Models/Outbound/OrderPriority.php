<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesOrder;
use App\Models\User;

class OrderPriority extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'priority_score',
        'priority_level',
        'priority_factors',
        'priority_calculated_at',
        'priority_reason',
        'is_manual_override',
        'set_by',
    ];

    protected $casts = [
        'priority_factors' => 'array',
        'priority_calculated_at' => 'datetime',
        'is_manual_override' => 'boolean',
    ];

    // Relationships
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function setBy()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    // Scopes
    public function scopeByLevel($query, $level)
    {
        return $query->where('priority_level', $level);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority_level', ['high', 'urgent', 'critical']);
    }

    public function scopeManualOverrides($query)
    {
        return $query->where('is_manual_override', true);
    }

    // Methods
    public function calculatePriority($factors = [])
    {
        $score = 100; // Base score
        
        // Customer tier factor
        if (isset($factors['customer_tier'])) {
            $tierMultipliers = [
                'platinum' => 50,
                'gold' => 30,
                'silver' => 10,
                'bronze' => 0
            ];
            $score += $tierMultipliers[$factors['customer_tier']] ?? 0;
        }
        
        // Order value factor
        if (isset($factors['order_value'])) {
            $score += min(($factors['order_value'] / 1000) * 5, 50);
        }
        
        // Ship date urgency
        if (isset($factors['ship_date'])) {
            $daysUntilShip = now()->diffInDays($factors['ship_date'], false);
            if ($daysUntilShip <= 0) {
                $score += 100; // Past due
            } elseif ($daysUntilShip <= 1) {
                $score += 75; // Due today/tomorrow
            } elseif ($daysUntilShip <= 3) {
                $score += 25; // Due within 3 days
            }
        }
        
        // Determine priority level based on score
        $level = 'normal';
        if ($score >= 250) {
            $level = 'critical';
        } elseif ($score >= 200) {
            $level = 'urgent';
        } elseif ($score >= 150) {
            $level = 'high';
        } elseif ($score < 75) {
            $level = 'low';
        }
        
        $this->priority_score = $score;
        $this->priority_level = $level;
        $this->priority_factors = $factors;
        $this->priority_calculated_at = now();
        
        return $this;
    }

    public function isPastDue()
    {
        return $this->priority_score >= 250;
    }

    public function isUrgent()
    {
        return in_array($this->priority_level, ['urgent', 'critical']);
    }
}