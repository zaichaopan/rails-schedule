<?php

namespace Tests\Unit;

use App\Job;
use App\ChosenUser;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_the_user_a_job_belongs_to()
    {
        $user = factory('App\User')->create();
        $job = factory('App\Job')->create(['user_id' => $user->id]);

        $this->assertInstanceOf('App\User', $jobSchedule = $job->user);
        $this->assertEquals($user->id, $jobSchedule->id);
    }

    /** @test */
    public function it_can_get_the_schedule_a_job_belongs_to()
    {
        $schedule = factory('App\Schedule')->create();
        $job = factory('App\Job')->create(['schedule_id' => $schedule->id]);

        $this->assertInstanceOf('App\Schedule', $jobSchedule = $job->schedule);
        $this->assertEquals($schedule->id, $jobSchedule->id);
    }

    /** @test */
    public function it_can_get_all_jobs_that_belong_to_a_schedule()
    {
        $scheduleA = factory('App\Schedule')->create();
        $jobOneInScheduleA = factory('App\Job')->create(['schedule_id' => $scheduleA->id]);
        $jobTwoInScheduleA = factory('App\Job')->create(['schedule_id' => $scheduleA->id]);

        $scheduleB = factory('App\Schedule')->create();
        $jobOneInScheduleB = factory('App\Job')->create(['schedule_id' => $scheduleB->id]);

        $jobsInScheduleA = Job::ofSchedule($scheduleA)->get()->pluck('id')->toArray();

        $this->assertCount(2, $jobsInScheduleA);
        $this->assertContains($jobOneInScheduleA->id, $jobsInScheduleA);
        $this->assertContains($jobTwoInScheduleA->id, $jobsInScheduleA);
    }

    /** @test */
    public function it_can_set_chosen_user()
    {
        $job = factory('App\Job')->create();

        $this->assertNull($job->chosenUser);

        $userJobPreference = factory('App\UserJobPreference')->create();

        $job->setChosenUser($userJobPreference);

        $this->assertInstanceOf(ChosenUser::class, $job->chosenUser);

        $this->assertEquals($job->chosenUser->user()->id, $userJobPreference->user_id);
    }

    /** @test */
    public function it_can_determine_if_a_job_should_change_chosen_user()
    {
        $engineerJob = factory('App\Job')->states(Job::ENGINEER)->create();

        // when user not qualified
        $slav = factory('App\User')->states(Job::FOREMAN)->create();
        $slaveJobPreference = factory('App\UserJobPreference')->create(['user_id' => $slav->id]);
        $this->assertFalse($engineerJob->shouldChangeChosenUser($slaveJobPreference));

        // when user qualified and without chosen user
        $slav = tap($slav)->update(['engineer_since' => now()]);
        $this->assertTrue($engineerJob->shouldChangeChosenUser($slaveJobPreference->fresh()));

        // when user qualified and with chosen user with more experience
        $kully = factory('App\User')->states(Job::ENGINEER)->create(['engineer_since' => today()->subDay()]);
        $kullyJobPreference = factory('App\UserJobPreference')->create(['user_id' => $kully->id]);
        $engineerJob->setChosenUser($kullyJobPreference);
        $this->assertFalse($engineerJob->shouldChangeChosenUser($slaveJobPreference->fresh()));

        // when user qualified and with chosen user with less experience
        $slav = tap($slav)->update(['engineer_since' => today()->subDays(2)]);
        $this->assertTrue($engineerJob->shouldChangeChosenUser($slaveJobPreference->fresh()));
    }
}
