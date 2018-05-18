<?php

use Faker\Generator as Faker;

$factory->define(App\UserJobPreference::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory('App\User')->create()->id;
        },
        'schedule_id' => function () {
            return factory('App\Schedule')->create()->id;
        }
    ];
});
