<?php

namespace App;

use Carbon\Carbon;
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

    public function gainEngineerQualification(string $date): self
    {
        return tap($this)->update(['engineer_since' => $date]);
    }

    public function claimJobPreferencesForSchedule(array $jobIds, Schedule $schedule): UserJobPreference
    {
        return $this->jobPreferences()->create(['preferences' => $jobIds, 'schedule_id' => $schedule->id]);
    }

    public function jobPreferenceForSchedule(Schedule $schedule): ?UserJobPreference
    {
        return $this->jobPreferences()->where('schedule_id', $schedule->id)->first();
    }

    public function hasMoreExperienceAsForeman(User $user): bool
    {
        return Carbon::createFromFormat('Y-m-d', $this->foreman_since)
            ->lt(Carbon::createFromFormat('Y-m-d', $user->foreman_since));
    }

    public function hasMoreExperienceAsEngineer(User $user):bool
    {
        return Carbon::createFromFormat('Y-m-d', $this->engineer_since)
            ->lt(Carbon::createFromFormat('Y-m-d', $user->engineer_since));
    }
}
