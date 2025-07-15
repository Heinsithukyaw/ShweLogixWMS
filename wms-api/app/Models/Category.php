<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_code',
        'category_name',
        'parent_id',
        'hierarchy_level',
        'applicable_industry',
        'storage_condition',
        'handling_instructions',
        'tax_category',
        'uom_id',
        'description',
        'status'
    ];

    // protected $with = ['unit_of_measure','parent'];

    public function parent_category()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function unit_of_measure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function brands()
    {
        return $this->hasMany(Brand::class, 'category_id');
    }

    public function subcategory_brands()
    {
        return $this->hasMany(Brand::class, 'subcategory_id');
    }

}
