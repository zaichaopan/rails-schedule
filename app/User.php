<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',  'foreman_since', 'engineer_since'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
    * The attributes that should be cast to native types.
    *
    * @var array
    */
    protected $casts = [
        'foreman_since' => 'date:Y-m-d',
        'engineer_since' => 'date:Y-m-d'
    ];

    public function jobPreferences(): HasMany
    {
        return $this->hasMany(UserJobPreference::class);
    }

    public function isEngineer(): bool
    {
        return !is_null($this->engineer_since);
    }

    public function isForeman() : bool
    {
        return !is_null($this->foreman_since);
    }

    public function gainEngineerQualification(string $date): self
    {
        return tap($this)->update(['engineer_since' => $date]);
    }

    public function submitJobPreferencesForSchedule(array $jobIds, Schedule $schedule): UserJobPreference
    {
        return $this->jobPreferences()->create(['preferences' => $jobIds, 'schedule_id' => $schedule->id]);
    }

    public function jobPreferenceForSchedule(Schedule $schedule): ?UserJobPreference
    {
        return $this->jobPreferences()->where('schedule_id', $schedule->id)->first();
    }

    public function hasMoreExperienceAsForemanThan(User $user): bool
    {
        return $this->isForeman() && $this->foreman_since->diffInDays($user->foreman_since, false) > 0;
    }

    public function hasMoreExperienceAsEngineerThan(User $user):bool
    {
        return $this->isEngineer() && $this->engineer_since->diffInDays($user->engineer_since, false) > 0;
    }

    public function isQualifiedForJob(Job $job): bool
    {
        $type = ucfirst($job->type);

        return method_exists($this, $method = "is{$type}")
            ? $this->{$method}()
            : false;
    }
}
