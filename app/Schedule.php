<?php

namespace App;

use Illuminate\Support\Collection;
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

    public function userJobPreferences(): HasMany
    {
        return $this->hasMany(UserJobPreference::class);
    }

    public function assignJobs(): void
    {
        $this->matchPreferences($this->getValidUserJobPreferences());
    }

    public function matchPreferences(Collection $userJobPreferences): void
    {
        if (count($userJobPreferences) === 0) {
            return;
        }

        $original = $userJobPreferences->map(function ($item) {return $item;});

        $userJobPreferences->each(function ($userJobPreference, $key) use ($original) {
            if (count($userJobPreference->preferences) === 0) {
                return $original->forget($key);
            }

            $firstChoiceJob = $this->jobs->first(function ($job) use ($userJobPreference) {
                return (int)$job->id === (int)$userJobPreference->preferences[0];
            });

            if (is_null($firstChoiceJob->tempChosenUser)) {
                $firstChoiceJob->tempChosenUser = $userJobPreference;
                return $original->forget($key);
            }

            if ($firstChoiceJob->shouldReassignTempChosenUser($userJobPreference)) {
                $replacedUserJobPreference = $firstChoiceJob->tempChosenUser;
                $firstChoiceJob->tempChosenUser = $userJobPreference;
                $replacedUserJobPreference->removeFirstChoiceJob();
                return $original->push($replacedUserJobPreference);
            }

            $original->forget($key);
            $userJobPreference->removeFirstChoiceJob();
            $original->push($userJobPreference);
        });

        $this->matchPreferences($original);
    }

    public function getValidUserJobPreferences(): Collection
    {
        return $this->userJobPreferences
            ->filter
            ->hasPreferences()
            ->each
            ->removeInvalidJobChoices($this->jobs);
    }
}
