<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FpiRating extends Model
{
    protected $fillable = [
        'team_id',
        'rating',
        'revision',
        'recorded_at'

    ];

    protected $casts = [
        'rating' => 'float',
        'recorded_at' => 'datetime'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}

