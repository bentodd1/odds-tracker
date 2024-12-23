<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoneyLine extends Model
{
    protected $fillable = ['game_id', 'casino_id', 'home_odds', 'away_odds', 'recorded_at'];

    protected $dates = ['recorded_at'];

    protected $casts = [
        'home_odds' => 'decimal:2',
        'away_odds' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function casino()
    {
        return $this->belongsTo(Casino::class);
    }

    public function result()
    {
        return $this->hasOne(MoneyLineResult::class);
    }

    public function getHomeImpliedProbabilityAttribute()
    {
        return $this->calculateImpliedProbability($this->home_odds) * 100;
    }

    public function getAwayImpliedProbabilityAttribute()
    {
        return $this->calculateImpliedProbability($this->away_odds) * 100;
    }

    private function calculateImpliedProbability($odds)
    {
        if ($odds > 0) {
            return 100 / ($odds + 100);
        } else {
            return abs($odds) / (abs($odds) + 100);
        }
    }
}
