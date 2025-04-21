<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DratingsPrediction extends Model
{
    protected $fillable = [
        'game_id',
        'home_win_probability',
        'away_win_probability',
        'home_moneyline',
        'away_moneyline',
        'home_ev',
        'away_ev',
        'recorded_at'
    ];

    protected $casts = [
        'home_win_probability' => 'decimal:2',
        'away_win_probability' => 'decimal:2',
        'home_moneyline' => 'decimal:2',
        'away_moneyline' => 'decimal:2',
        'home_ev' => 'decimal:2',
        'away_ev' => 'decimal:2',
        'recorded_at' => 'datetime'
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
