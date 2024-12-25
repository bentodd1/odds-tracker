<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['sport_id', 'name', 'abbreviation', 'location', 'league'];

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function homeGames()
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    public function awayGames()
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }

    public function latestFpi()
    {
        return $this->hasOne(FpiRating::class)
            ->orderByDesc('revision')
            ->whereIn('id', function($query) {
                $query->select(\DB::raw('MIN(id)'))
                    ->from('fpi_ratings')
                    ->groupBy('team_id');
            });
    }
}
