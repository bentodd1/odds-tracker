<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KalshiWeatherCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'location',
        'event_prefix', // For example, 'KXHIGHDEN' for Denver temperature
    ];

    /**
     * Get the weather markets associated with this category
     */
    public function markets(): HasMany
    {
        return $this->hasMany(KalshiWeatherMarket::class, 'category_id');
    }
} 