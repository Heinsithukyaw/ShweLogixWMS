<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Employee;

class DamageInspection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'inspection_number',
        'packed_carton_id',
        'shipment_id',
        'product_id',
        'inspection_type',
        'damage_found',
        'damage_type',
        'damage_severity',
        'damage_description',
        'damage_photos',
        'inspector_id',
        'inspection_notes',
        'inspected_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'damage_found' => 'boolean',
        'damage_photos' => 'json',
        'inspected_at' => 'datetime'
    ];

    /**
     * Get the packed carton that owns the damage inspection.
     */
    public function packedCarton()
    {
        return $this->belongsTo(PackedCarton::class);
    }

    /**
     * Get the shipment that owns the damage inspection.
     */
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the product that owns the damage inspection.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the employee who performed the inspection.
     */
    public function inspector()
    {
        return $this->belongsTo(Employee::class, 'inspector_id');
    }

    /**
     * Scope a query to only include inspections with damage found.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDamageFound($query)
    {
        return $query->where('damage_found', true);
    }

    /**
     * Scope a query to only include inspections with no damage.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNoDamage($query)
    {
        return $query->where('damage_found', false);
    }

    /**
     * Scope a query to only include pre-ship inspections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePreShip($query)
    {
        return $query->where('inspection_type', 'pre_ship');
    }

    /**
     * Scope a query to only include receiving inspections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReceiving($query)
    {
        return $query->where('inspection_type', 'receiving');
    }

    /**
     * Scope a query to only include return inspections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReturn($query)
    {
        return $query->where('inspection_type', 'return');
    }

    /**
     * Scope a query to only include severe damage.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSevereDamage($query)
    {
        return $query->where('damage_severity', 'severe');
    }

    /**
     * Get the number of damage photos.
     *
     * @return int
     */
    public function getDamagePhotoCount()
    {
        $photos = json_decode($this->damage_photos, true);
        
        if (is_array($photos)) {
            return count($photos);
        }
        
        return 0;
    }

    /**
     * Check if the damage is critical.
     *
     * @return bool
     */
    public function isCriticalDamage()
    {
        return $this->damage_found && $this->damage_severity === 'severe';
    }
}