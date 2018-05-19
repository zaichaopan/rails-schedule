<?php

namespace Tests\Unit;

use App\Job;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_determine_if_a_user_is_foreman()
    {
        $user = factory('App\User')->create(['foreman_since' => null]);

        $this->assertFalse($user->isForeman());

        $user->update(['foreman_since' => now()]);

        $this->assertTrue($user->fresh()->isForeman());
    }

    /** @test */
    public function it_can_determine_if_user_is_engineer()
    {
        $foreman = factory('App\User')->states(Job::FOREMAN)->create();
        $engineer = factory('App\User')->states(Job::ENGINEER)->create();

        $this->assertFalse($foreman->isEngineer());
        $this->assertTrue($engineer->isEngineer());
    }

    /** @test */
    public function it_can_gain_foreman_engineer_qualification()
    {
        $date = '2018-05-18';
        $user = factory('App\User')->states(Job::FOREMAN)->create();
        $user = $user->gainEngineerQualification($date);

        $this->assertTrue($user->isEngineer());
        $this->assertEquals($date, $user->engineer_since->toDateString());
    }

    /** @test */
    public function it_can_get_user_job_preferences_for_all_schedules()
    {
        $user = factory('App\User')->create();

        $this->assertInstanceOf(Collection::class, $user->jobPreferences);
        $this->assertCount(0, $user->jobPreferences);

        $preferredJobIds = [1, 2, 3];
        $schedule = factory('App\Schedule')->create();
        $user->submitJobPreferencesForSchedule($preferredJobIds, $schedule);

        $this->assertCount(1, $user->fresh()->jobPreferences);
    }

    /** @test */
    public function it_can_get_user_job_preference_for_a_given_schedule()
    {
        $schedule = factory('App\Schedule')->create();
        $user = factory('App\User')->create();
        $preferredJobIds = [1, 2, 3];

        $this->assertNull($user->jobPreferenceForSchedule($schedule));

        $user->submitJobPreferencesForSchedule($preferredJobIds, $schedule);

        $this->assertNotNull($jobPreference = $user->jobPreferenceForSchedule($schedule));
        $this->assertEquals($preferredJobIds, $jobPreference->preferences);
    }

    /** @test */
    public function it_can_determine_if_a_user_is_qualified_for_a_job()
    {
        $foreman = factory('App\User')->states(Job::FOREMAN)->create();
        $engineer = factory('App\User')->states(Job::ENGINEER)->create();
        $foremanJob = factory('App\Job')->states(Job::FOREMAN)->create();
        $engineerJob = factory('App\Job')->states(Job::ENGINEER)->create();

        $this->assertTrue($foreman->isQualifiedForJob($foremanJob));
        $this->assertTrue($engineer->isQualifiedForJob($foremanJob));
        $this->assertFalse($foreman->isQualifiedForJob($engineerJob));
        $this->assertTrue($engineer->isQualifiedForJob($engineerJob));
    }

    /** @test */
    public function it_can_determine_if_a_foreman_has_more_experience_than_another()
    {
        $slav = factory('App\User')->states(Job::FOREMAN)->create(['foreman_since' => '2018-05-15']);
        $kully = factory('App\User')->states(Job::FOREMAN)->create(['foreman_since' => '2018-05-17']);

        $this->assertTrue($slav->hasMoreExperienceAsForemanThan($kully));
        $this->assertFalse($kully->hasMoreExperienceAsForemanThan($slav));

        $kully = tap($kully)->update(['foreman_since' => '2018-05-15']);

        $this->assertFalse($slav->hasMoreExperienceAsForemanThan($kully));
        $this->assertFalse($kully->hasMoreExperienceAsForemanThan($slav));
    }

    /** @test */
    public function it_can_determine_if_an_engineer_has_more_experience_than_another()
    {
        $slav = factory('App\User')->states(Job::ENGINEER)->create(['engineer_since' => '2018-05-15']);
        $kully = factory('App\User')->states(Job::ENGINEER)->create(['engineer_since' => '2018-05-17']);

        $this->assertTrue($slav->hasMoreExperienceAsEngineerThan($kully));
        $this->assertFalse($kully->hasMoreExperienceAsEngineerThan($slav));

        $kully = tap($kully)->update(['engineer_since' => '2018-05-15']);

        $this->assertFalse($slav->hasMoreExperienceAsEngineerThan($kully));
        $this->assertFalse($kully->hasMoreExperienceAsEngineerThan($slav));
    }
}
