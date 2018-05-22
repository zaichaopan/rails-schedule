<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserJobPreference extends Model
{
    protected $guarded = [];

    protected $with = [
        'user'
    ];

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
        return !$this->hasNotPreferences();
    }

    public function hasNotPreferences(): bool
    {
        return empty($this->preferences);
    }

    public function removeInvalidJobChoices(Collection $jobs): void
    {
        $this->preferences = collect($this->preferences)
            ->intersect(collect($jobs->pluck('id')->toArray()))
            ->filter(function ($preference) use ($jobs) {
                $job = $jobs->first(function ($job) use ($preference) {
                    return (int)$job->id === (int)$preference;
                });

                return $job ? $this->user->isQualifiedForJob($job) : false;
            })->unique()->values()->all();
    }

    public function removeFirstChoiceJob(): void
    {
        $preferences = $this->preferences;
        array_shift($preferences);
        $this->preferences = $preferences;
    }
}
