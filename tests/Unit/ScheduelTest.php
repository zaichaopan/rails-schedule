<?php

namespace Tests\Unit;

use App\Job;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_associated_jobs()
    {
        $schedule = factory('App\Schedule')->create();

        $this->assertInstanceOf(Collection::class, $schedule->jobs);
        $this->assertCount(0, $schedule->jobs);

        $job = factory('App\Job')->create(['schedule_id' => $schedule->id]);

        $this->assertCount(1, $jobs = $schedule->fresh()->jobs);
        $this->assertEquals($job->id, $jobs->first()->id);
    }

    /** @test */
    public function it_can_get_associated_user_job_preferences()
    {
        $schedule = factory('App\Schedule')->create();

        $this->assertInstanceOf(Collection::class, $schedule->userJobPreferences);
        $this->assertCount(0, $schedule->userJobPreferences);

        $userJobPreference = factory('App\UserJobPreference')->create(['schedule_id' => $schedule->id]);

        $this->assertCount(1, $userJobPreferences = $schedule->fresh()->userJobPreferences);
        $this->assertEquals($userJobPreference->id, $userJobPreferences->first()->id);
    }

    /** @test */
    public function it_can_remove_empty_preferences()
    {
        $schedule = factory('App\Schedule')->create();

        $slav = factory('App\User')->create();

        $slavJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $slav->id,
            'schedule_id' => $schedule->id,
            'preferences' => []
        ]);

        $this->assertCount(0, $schedule->fresh()->getValidUserJobPreferences());
    }

    /** @test */
    public function it_can_remove_null_preferences()
    {
        $schedule = factory('App\Schedule')->create();

        $slav = factory('App\User')->create();

        $slavJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $slav->id,
            'schedule_id' => $schedule->id,
            'preferences' => null
        ]);

        $this->assertCount(0, $schedule->fresh()->getValidUserJobPreferences());
    }

    /** @test */
    public function it_can_remove_job_preferences_from_another_schedule()
    {
        $slav = factory('App\User')->states(Job::FOREMAN)->create();
        $schedule = factory('App\Schedule')->create();
        $jobInSchedule = factory('App\Job')->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);
        $jobFromDifferentSchedule = factory('App\Job')->create();

        $slavJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $slav->id,
            'schedule_id' => $schedule->id,
            'preferences' => [$jobInSchedule->id, $jobFromDifferentSchedule->Id]
        ]);

        $validUserJobPreferences = $schedule->fresh()->getValidUserJobPreferences();
        $this->assertCount(1, $validUserJobPreferences);
        $this->assertEquals([$jobInSchedule->id], $validUserJobPreferences->first()->preferences);
    }

    /** @test */
    public function it_can_remove_unqualified_job_preferences()
    {
        $slav = factory('App\User')->states(Job::FOREMAN)->create();
        $schedule = factory('App\Schedule')->create();
        $foremanJob = factory('App\Job')->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);
        $engineerJob = factory('App\Job')->states(Job::ENGINEER)->create(['schedule_id' => $schedule->id]);

        $slavJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $slav->id,
            'schedule_id' => $schedule->id,
            'preferences' => [$foremanJob->id, $engineerJob->Id]
        ]);

        $validUserJobPreferences = $schedule->fresh()->getValidUserJobPreferences();
        $this->assertCount(1, $validUserJobPreferences);
        $this->assertEquals([$foremanJob->id], $validUserJobPreferences->first()->preferences);
    }

    /** @test */
    public function it_can_remove_duplicate_job_preferences()
    {
        $slav = factory('App\User')->states(Job::FOREMAN)->create();
        $schedule = factory('App\Schedule')->create();
        $foremanJob = factory('App\Job')->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);

        $slavJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $slav->id,
            'schedule_id' => $schedule->id,
            'preferences' => [$foremanJob->id, $foremanJob->id]
        ]);

        $validUserJobPreferences = $schedule->fresh()->getValidUserJobPreferences();
        $this->assertCount(1, $validUserJobPreferences);
        $this->assertEquals([$foremanJob->id], $validUserJobPreferences->first()->preferences);
    }

    /** @test */
    public function it_can_assign_jobs()
    {
        $schedule = factory('App\Schedule')->create();

        $foremanJobOne = factory('App\Job')->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);
        $foremanJobTwo = factory('App\Job')->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);
        $foremanJobThree = factory('App\Job')->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);
        $engineerJobOne = factory('App\Job')->states(Job::ENGINEER)->create(['schedule_id' => $schedule->id]);
        $engineerJobTwo = factory('App\Job')->states(Job::ENGINEER)->create(['schedule_id' => $schedule->id]);

        $slav = factory('App\User')->states(Job::ENGINEER)->create(['engineer_since' => today()]);
        $kully = factory('App\User')->states(Job::ENGINEER)->create(['engineer_since' => today()->subDays(2)]);
        $mike = factory('App\User')->states(Job::FOREMAN)->create(['foreman_since' => today()->subDays(2)]);
        $alex = factory('App\User')->states(Job::FOREMAN)->create(['foreman_since' => today()->subDays(3)]);
        $steve = factory('App\User')->states(Job::FOREMAN)->create(['foreman_since' => today()->subDays(5)]);

        $slavJobPreference = factory('App\UserJobPreference')->create([
            'schedule_id' => $schedule->id,
            'user_id' => $slav->id,
            'preferences' => [
                $engineerJobOne->id,
                $engineerJobTwo->id,
                $foremanJobTwo->id,
                $foremanJobOne->id,
                $foremanJobThree->id
            ]
        ]);

        $kullyJobPreference = factory('App\UserJobPreference')->create([
            'schedule_id' => $schedule->id,
            'user_id' => $kully->id,
            'preferences' => [
                $engineerJobOne->id,
                $engineerJobTwo->id,
                $foremanJobOne->id,
                $foremanJobTwo->id,
                $foremanJobThree->id
            ]
        ]);

        $mikeJobPreference = factory('App\UserJobPreference')->create([
            'schedule_id' => $schedule->id,
            'user_id' => $mike->id,
            'preferences' => [
                $foremanJobTwo->id,
                $foremanJobThree->id,
                $foremanJobOne->id
            ]
        ]);

        $alexJobPreference = factory('App\UserJobPreference')->create([
            'schedule_id' => $schedule->id,
            'user_id' => $alex->id,
            'preferences' => [
                $foremanJobTwo->id,
                $foremanJobOne->id,
                $foremanJobThree->id
            ]
        ]);

        $steveJobPreference = factory('App\UserJobPreference')->create([
            'schedule_id' => $schedule->id,
            'user_id' => $steve->id,
            'preferences' => [
                $foremanJobThree->id,
                $foremanJobOne->id,
                $foremanJobTwo->id
            ]
        ]);

        $schedule = $schedule->fresh();
        $schedule->assignJobs();
        $foremanJobOne = $foremanJobOne->fresh();
        $foremanJobTwo = $foremanJobTwo->fresh();
        $foremanJobThree = $foremanJobThree->fresh();
        $engineerJobOne = $engineerJobOne->fresh();
        $engineerJobTwo = $engineerJobTwo->fresh();

        $this->assertEquals($mike->id, $foremanJobOne->user_id);
        $this->assertEquals($alex->id, $foremanJobTwo->user_id);
        $this->assertEquals($steve->id, $foremanJobThree->user_id);
        $this->assertEquals($kully->id, $engineerJobOne->user_id);
        $this->assertEquals($slav->id, $engineerJobTwo->user_id);
    }
}
