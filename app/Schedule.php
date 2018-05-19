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
        if (count($validUserJobPreferences = $this->getValidUserJobPreferences()) === 0) {
            return;
        }

        $this->matchPreferences($validUserJobPreferences);

        $this->jobs->each->assignUser();
    }

    public function matchPreferences(Collection $userJobPreferences): void
    {
        if (count($userJobPreferences) === 0) {
            return;
        }

        $original = $userJobPreferences->map(function ($item) {return $item;});

        $userJobPreferences->each(function ($userJobPreference, $key) use ($original) {
            if ($userJobPreference->hasNotPreferences()) {
                return $original->forget($key);
            }

            $firstChoiceJob = $this->jobs->first(function ($job) use ($userJobPreference) {
                return (int)$job->id === (int)$userJobPreference->preferences[0];
            });

            if (is_null($firstChoiceJob->chosenUser)) {
                $firstChoiceJob->setChosenUser($userJobPreference);
                return $original->forget($key);
            }

            if ($firstChoiceJob->shouldChangeChosenUser($userJobPreference)) {
                $currentChosenUser = $firstChoiceJob->chosenUser;
                $firstChoiceJob->setChosenUser($userJobPreference);
                $original->forget($key);
                $currentChosenUser->userJobPreference->removeFirstChoiceJob();
                return $original->push($currentChosenUser->userJobPreference);
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
