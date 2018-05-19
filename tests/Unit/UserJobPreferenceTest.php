<?php

namespace Tests\Unit;

use App\Job;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserJobPreferenceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_the_user_whom_a_job_preference_belongs_to()
    {
        $user = factory('App\User')->create();
        $userJobPreference = factory('App\UserJobPreference')->create(['user_id' => $user->id]);

        $this->assertInstanceOf('App\User', $userJobPreference->user);
        $this->assertEquals($user->id, $userJobPreference->user->id);
    }

    /** @test */
    public function it_can_get_the_schedule_that_a_job_preference_belongs_to()
    {
        $schedule = factory('App\Schedule')->create();
        $userJobPreference = factory('App\UserJobPreference')->create(['schedule_id' => $schedule->id]);

        $this->assertInstanceOf('App\Schedule', $userJobPreference->schedule);
        $this->assertEquals($schedule->id, $userJobPreference->schedule->id);
    }

    /** @test */
    public function it_can_determine_if_the_preferences_is_set_or_not()
    {
        $userJobPreference = factory('App\UserJobPreference')->create(['preferences' => []]);
        $this->assertFalse($userJobPreference->hasPreferences());
        $this->assertTrue($userJobPreference->hasNotPreferences());

        $userJobPreference = tap($userJobPreference)->update(['preferences' => null]);
        $this->assertFalse($userJobPreference->hasPreferences());
        $this->assertTrue($userJobPreference->hasNotPreferences());

        $userJobPreference = factory('App\UserJobPreference')->create(['preferences' => ['1']]);
        $this->assertTrue($userJobPreference->hasPreferences());
        $this->assertFalse($userJobPreference->hasNotPreferences());
    }

    /** @test */
    public function it_can_remove_invalid_job_choices_from_preferences()
    {
        $slav = factory('App\User')->states(Job::FOREMAN)->create();
        $schedule = factory('App\Schedule')->create();
        $foremanJobs = factory('App\Job', 3)->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);
        $engineerJob = factory('App\Job')->states(Job::ENGINEER)->create(['schedule_id' => $schedule->id]);
        $invalidJobIds = [100, 'abc', null, $engineerJob->id];

        $slavJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $slav->id,
            'preferences' => $invalidJobIds,
            'schedule_id' => $schedule->id
        ]);

        $jobs = $schedule->fresh()->jobs;
        $slavJobPreference->removeInvalidJobChoices($jobs);

        $this->assertEquals([], $slavJobPreference->preferences);

        $slav->update(['engineer_since' => now()]);
        $slavJobPreference = $slavJobPreference->fresh();
        $slavJobPreference->removeInvalidJobChoices($jobs);

        $this->assertEquals([$engineerJob->id], $slavJobPreference->preferences);
    }

    /** @test */
    public function it_can_remove_first_choice_job_from_preferences_temporarily()
    {
        $userJobPreference = factory('App\UserJobPreference')->create(['preferences' => [1, 2, 3]]);
        $userJobPreference->removeFirstChoiceJob();

        $this->assertEquals([2, 3], $userJobPreference->preferences);
        $this->assertEquals([1, 2, 3], $userJobPreference->fresh()->preferences);
    }
}
