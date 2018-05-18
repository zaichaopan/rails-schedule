<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_get_associated_jobs()
    {
        $schedule = factory('App\Schedule')->create();

        $this->assertInstanceOf(Collection::class, $schedule->jobs);
        $this->assertCount(0, $schedule->jobs);

        $job = factory('App\Job')->create(['schedule_id' => $schedule->id]);

        $this->assertCount(1, $jobs = $schedule->fresh()->jobs);
        $this->assertEquals($job->id, $jobs->first()->id);
    }
}
