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

    public function userJobPreferences(): HasMany
    {
        return $this->hasMany(UserJobPreference::class);
    }

    public function assignJobs()
    {
        $this->matchPreferences($this->getValidUserJobPreferences());
    }

    public function matchPreferences(Collection $userJobPreferences)
    {
        if (count($userJobPreferences) === 0) {
            return;
        }

        $original = $userJobPreferences->map(function ($item) {return $item;});

        $userJobPreferences->each(function ($userJobPreference, $key) use ($jobs, $original) {
            $preference = $userJobPreference->preference;

            if (count($preferences) === 0) {
                return $original->forget($key);
            }

            $firstChoiceJob = $this->jobs->first(function () use ($preference) {
                return (int)$job->id === (int)$preference[0];
            });

            if (is_null($firstChoiceJob->tempChoice)) {
                $firstChoiceJob->tempChoice = $userJobPreference;
                return $original->forget($key);
            }

            if ($firstChoiceJob->shouldReassignTempChoice($userJobPreference)) {
                $replacedUserJobPreference = $firstChoiceJob->tempChoice;
                $firstChoiceJob->tempChoice = $userJobPreference;
                $replacedUserJobPreference->removeFirstChoice();
                return $original->push($replacedUserJobPreference);
            }

            $original->forget($key);
            $userJobPreference->removeFirstChoice();
            $original->push($userJobPreference);
        });

        $this->matchPreferences($original);
    }

    public function getValidUserJobPreferences()
    {
        return $this->userJobPreferences
            ->filter
            ->hasPreference()
            ->each
            ->removeInvalidJobChoices($this->jobs);
    }
}
