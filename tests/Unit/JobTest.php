<?php

namespace Tests\Unit;

use App\Job;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobTest extends TestCase
{
    use RefreshDatabase;

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
}
