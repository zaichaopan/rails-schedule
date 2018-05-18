<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    const FOREMAN = 'foreman';
    const ENGINEER = 'engineer';

    public $temptChoice;

    protected $guarded = [];

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
        return $this->where('schedule_id', '=', (string)$schedule->id);
    }

    public static function getAcceptedJobType(): array
    {
        return [static::FOREMAN, static::ENGINEER];
    }

    public function shouldReassignTemptChoice(UserJobPreference $userJobPreference): bool
    {
    }
}
