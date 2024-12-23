<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_id',
        'home_team_id',
        'away_team_id',
        'commence_time',
        'season',
        'game_id',
        'completed'
    ];


    protected $casts = [
        'commence_time' => 'datetime',
        'completed' => 'boolean'
    ];

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function spreads()
    {
        return $this->hasMany(Spread::class);
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    public function moneyLines()
    {
        return $this->hasMany(MoneyLine::class);
    }
}
