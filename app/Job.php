<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    const FOREMAN = 'foreman';
    const ENGINEER = 'engineer';

    /**
     * Temporarily store user with preferences for scheduling
     *
     * @var ChosenUser
     */
    public $chosenUser;

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
        return $this->where('schedule_id', '=', $schedule->id);
    }

    public static function getAcceptedJobType(): array
    {
        return [static::FOREMAN, static::ENGINEER];
    }

    public function shouldChangeChosenUser(UserJobPreference $userJobPreference): bool
    {
        if (!$userJobPreference->user->isQualifiedForJob($this)) {
            return false;
        }

        if (is_null($this->chosenUser)) {
            return true;
        }

        $type = ucfirst($this->type);

        return method_exists($userJobPreference->user, $method = "hasMoreExperienceAs{$this->type}Than")
            ? $userJobPreference->user->{$method}($this->chosenUser->user())
            : false;
    }

    public function setChosenUser(UserJobPreference $userJobPreference)
    {
        $this->chosenUser = app(ChosenUser::class)->setUserJobPreference($userJobPreference);
    }

    public function assignUser(): void
    {
        if (!is_null($this->chosenUser)) {
            $this->update(['user_id' => $this->chosenUser->user()->id]);
        }
    }
}
