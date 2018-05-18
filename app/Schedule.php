<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    /**
    * Don't auto-apply mass assignment protection.
    *
    * @var array
    */
    protected $guarded = [];

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
