<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id', 
        'home_score', 
        'away_score', 
        'period',
        'home_fpi',
        'away_fpi',
        'date'
    ];

    protected $casts = [
        'home_score' => 'integer',
        'away_score' => 'integer',
        'home_fpi' => 'float',
        'away_fpi' => 'float',
        'date' => 'datetime'
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function fpiPredictedCorrectly(): bool
    {
        if ($this->home_fpi === null || $this->away_fpi === null) {
            return false;
        }

        $fpiPredictedHomeWin = $this->home_fpi > $this->away_fpi;
        $actualHomeWin = $this->home_score > $this->away_score;

        return $fpiPredictedHomeWin === $actualHomeWin;
    }
}
