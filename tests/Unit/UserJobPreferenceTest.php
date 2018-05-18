<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserJobPreferenceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_the_user_who_a_job_preference_belongs_to()
    {
        $user = factory('App\User')->create();
        $userJobPreference = factory('App\UserJobPreference')->create(['user_id' => $user->id]);

        $this->assertInstanceOf('App\User', $userJobPreference->user);
        $this->assertEquals($user->id, $userJobPreference->user->id);
    }

    /** @test */
    public function it_can_get_the_schedule_that_a_job_preference_belongs_to()
    {
        $schedule= factory('App\Schedule')->create();
        $userJobPreference = factory('App\UserJobPreference')->create(['schedule_id' => $schedule->id]);

        $this->assertInstanceOf('App\Schedule', $userJobPreference->schedule);
        $this->assertEquals($schedule->id, $userJobPreference->schedule->id);
    }
}
