<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_determine_whether_a_user_is_engineer()
    {
        $foreman = factory('App\User')->states('foreman')->create();
        $engineer = factory('App\User')->states('engineer')->create();

        $this->assertFalse($foreman->isEngineer());
        $this->assertTrue($engineer->isEngineer());
    }

    /** @test */
    public function it_can_gain_foreman_engineer_qualification()
    {
        $date = '2018-05-18';
        $user = factory('App\User')->states('foreman')->create();
        $user = $user->gainEngineerQualification($date);

        $this->assertTrue($user->isEngineer());
        $this->assertEquals($date, $user->engineer_since);
    }

    /** @test */
    public function it_can_get_user_job_preferences_at_all_schedules()
    {
        $user = factory('App\User')->create();

        $this->assertInstanceOf(Collection::class, $user->jobPreferences);
        $this->assertCount(0, $user->jobPreferences);

        $preferredJobIds = [1, 2, 3];
        $schedule = factory('App\Schedule')->create();
        $user->claimJobPreferencesForSchedule($preferredJobIds, $schedule);

        $this->assertCount(1, $user->fresh()->jobPreferences);
    }

    /** @test */
    public function it_can_get_user_job_preference_at_a_given_schedule()
    {
        $schedule = factory('App\Schedule')->create();
        $user = factory('App\User')->create();
        $preferredJobIds = [1, 2, 3];

        $this->assertNull($user->jobPreferenceForSchedule($schedule));

        $user->claimJobPreferencesForSchedule($preferredJobIds, $schedule);

        $this->assertNotNull($jobPreference = $user->jobPreferenceForSchedule($schedule));
        $this->assertEquals($preferredJobIds, $jobPreference->preferences);
    }
}
