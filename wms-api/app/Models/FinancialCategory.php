<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_code',
        'category_name',
        'parent_id',
        'status'
    ];

    public function parent_category()
    {
        return $this->belongsTo(FinancialCategory::class, 'parent_id');
    }

}
