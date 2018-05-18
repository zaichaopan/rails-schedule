<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserJobPreference extends Model
{
    protected $guarded = [];

    protected $casts = [
         'preference' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function scopeOfSchedule(Builder $query, Schedule $schedule): Builder
    {
        return $this->where('schedule_id', $schedule->id);
    }

    public function hasPreferences()
    {
        return !is_null($this->preference) && count($this->preference) > 0;
    }

    public function removeInvalidJobChoices(Collection $jobs)
    {
        return $this->preference = collect($this->preference)
            ->intersect(collect($jobs->pluck('id')->toArray()))
            ->all();
    }

    public function removeFirstChoice()
    {
        $preference = $this->preference;
        array_shift($preference);
        $this->preference = $preference;
    }
}
