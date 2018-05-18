<?php

use Faker\Generator as Faker;

$factory->define(App\Schedule::class, function (Faker $faker) {
    return [
        'released_at' => now()->addDays(rand(1, 30))
    ];
});
