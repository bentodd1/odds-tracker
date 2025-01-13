<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NBAMargin extends Model
{
    protected $table = 'nba_margins';
    protected $fillable = ['margin', 'occurrences'];
} 