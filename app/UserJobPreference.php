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
         'preferences' => 'array'
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

    public function hasPreferences(): bool
    {
        return !is_null($this->preferences) && count($this->preferences) > 0;
    }

    public function removeInvalidJobChoices(Collection $jobs): void
    {
        $this->preferences = collect($this->preferences)
            ->intersect(collect($jobs->pluck('id')->toArray()))
            ->all();
    }

    public function removeFirstChoiceJob(): void
    {
        $preferences = $this->preferences;
        array_shift($preferences);
        $this->preferences = $preferences;
    }
}
