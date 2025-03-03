<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Team extends Model
{
    public const LEAGUE_A = 'A';
    public const LEAGUE_B = 'B';
    public const LEAGUES = [self::LEAGUE_A, self::LEAGUE_B];

    use HasFactory;

    protected $fillable = [
        'name',
        'league',
    ];

    public function homeMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'away_team_id');
    }

    public function standing(): HasOne
    {
        return $this->hasOne(Standing::class);
    }

    public function matches()
    {
        return $this->homeMatches->merge($this->awayMatches);
    }
}
