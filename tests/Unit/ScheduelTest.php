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
    public function it_can_get_valid_user_job_preferences()
    {
        $schedule = factory('App\Schedule')->create();
        $foremanJobOne = factory('App\Job')->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);
        $foremanJobTwo = factory('App\Job')->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);
        $engineerJobOne = factory('App\Job')->states(Job::ENGINEER)->create(['schedule_id' => $schedule->id]);
        $engineerJobTwo = factory('App\Job')->states(Job::ENGINEER)->create(['schedule_id' => $schedule->id]);

        $kully = factory('App\User')->states(Job::FOREMAN)->create();
        $slav = factory('App\User')->states(Job::ENGINEER)->create();

        $kullyJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $kully->id,
            'schedule_id' => $schedule->id,
            'preferences' => [$engineerJobOne->id, $foremanJobOne->id, $foremanJobTwo->id, 'abc']
        ]);

        $schedule = $schedule->fresh();
        $validUserJobPreferences = $schedule->getValidUserJobPreferences();

        $this->assertCount(1, $validUserJobPreferences);
        $this->assertEquals($kullyJobPreference->id, $validUserJobPreferences->first()->id);
        $this->assertEquals([$foremanJobOne->id, $foremanJobTwo->id], $validUserJobPreferences->first()->preferences);

        $slavJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $slav->id,
            'schedule_id' => $schedule->id,
            'preferences' => [$engineerJobTwo->id, $engineerJobOne->id, $foremanJobOne->id, $foremanJobTwo->id, 'abc', $foremanJobOne->id]
        ]);

        $schedule = $schedule->fresh();
        $validUserJobPreferences = $schedule->getValidUserJobPreferences();
        $validSlavJobPreference = $validUserJobPreferences->first(function ($item) use ($slavJobPreference) {
            return (int)$item->id === (int)$slavJobPreference->id;
        });

        $this->assertCount(2, $validUserJobPreferences);
        $this->assertEquals([$engineerJobTwo->id, $engineerJobOne->id, $foremanJobOne->id, $foremanJobTwo->id], $validSlavJobPreference->preferences);

        // job preferences that not for this schedule
        $john = factory('App\User')->create();
        $johnJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $john->id,
            'preferences' => [$engineerJobOne->id, $foremanJobOne->id, $foremanJobTwo->id]
        ]);

        // job preferences that null or empty
        $steve = factory('App\User')->create();
        $steveJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $steve->id,
            'schedule_id' => $schedule->id,
            'preferences' => null
        ]);

        $mike = factory('App\User')->create();
        $mikeJobPreference = factory('App\UserJobPreference')->create([
            'user_id' => $mike->id,
            'schedule_id' => $schedule->id,
            'preferences' => []
        ]);

        $schedule = $schedule->fresh();
        $validUserJobPreferences = $schedule->getValidUserJobPreferences();
        $validUserJobPreferenceIds = $validUserJobPreferences->pluck('id')->all();

        $this->assertCount(2, $validUserJobPreferences);
        $this->assertTrue(in_array($kullyJobPreference->id, $validUserJobPreferenceIds));
        $this->assertTrue(in_array($slavJobPreference->id, $validUserJobPreferenceIds));
        $this->assertFalse(in_array($johnJobPreference->id, $validUserJobPreferenceIds));
        $this->assertFalse(in_array($steveJobPreference->id, $validUserJobPreferenceIds));
        $this->assertFalse(in_array($mikeJobPreference->id, $validUserJobPreferenceIds));
    }

    /** @test */
    public function it_can_assign_jobs()
    {
        $schedule = factory('App\Schedule')->create();

        $foremanJobOne  = factory('App\Job')->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);
        $foremanJobTwo = factory('App\Job')->states(Job::FOREMAN)->create(['schedule_id' => $schedule->id]);
        $engineerJobOne = factory('App\Job')->states(Job::ENGINEER)->create(['schedule_id' => $schedule->id]);

        $slav = factory('App\User')->states(Job::ENGINEER)->create(['engineer_since' => today(), 'foreman_since' => today()]);
        $kully = factory('App\User')->states(Job::ENGINEER)->create(['engineer_since' => today()->subDays(2)]);
        $mike = factory('App\User')->states(Job::FOREMAN)->create(['foreman_since' => today()->subDays(2)]);

        $slavJobPreference = factory('App\UserJobPreference')->create([
            'schedule_id' => $schedule->id,
            'user_id' => $slav->id,
            'preferences' => [$engineerJobOne->id, $foremanJobTwo->id, $foremanJobOne->id]
        ]);

        $kullyJobPreference = factory('App\UserJobPreference')->create([
            'schedule_id' => $schedule->id,
            'user_id' => $kully->id,
            'preferences' => [$engineerJobOne->id, $foremanJobOne->id, $foremanJobTwo->id]
        ]);

        $MikeJobPreference = factory('App\UserJobPreference')->create([
            'schedule_id' => $schedule->id,
            'user_id' => $mike->id,
            'preferences' => [$foremanJobTwo->id, $foremanJobOne->id]
        ]);

        $schedule = $schedule->fresh();
        $schedule->assignJobs();
        $engineerJobOne = $engineerJobOne->fresh();
        $foremanJobTwo = $foremanJobTwo->fresh();
        $foremanJobOne = $foremanJobOne->fresh();

        $this->assertEquals($slav->id, $foremanJobOne->user_id);
        $this->assertEquals($mike->id, $foremanJobTwo->user_id);
        $this->assertEquals($kully->id, $engineerJobOne->user_id);
    }
}
