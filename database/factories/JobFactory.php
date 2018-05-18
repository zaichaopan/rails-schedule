<?php

use App\Job;
use Faker\Generator as Faker;

$factory->define(App\Job::class, function (Faker $faker) {
    return [
        'schedule_id' => function () {
            return factory('App\Schedule')->create()->id;
        },
        'type' => array_random(Job::getAcceptedJobType())
    ];
});

$factory->state(App\Job::class, 'open_for_bid', function (Faker $faker) {
    return [
        'user_id' => null
    ];
});

$factory->state(App\Job::class, 'close_for_bid', function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory('App\User')->create()->id;
        }
    ];
});

$factory->state(App\Job::class, Job::FOREMAN, function (Faker $faker) {
    return [
        'type' => Job::FOREMAN
    ];
});

$factory->state(App\Job::class, Job::ENGINEER, function (Faker $faker) {
    return [
        'type' => Job::ENGINEER
    ];
});
