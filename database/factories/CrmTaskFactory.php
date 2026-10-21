<?php

namespace Database\Factories;

use App\Models\CrmTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CrmTask> */
class CrmTaskFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'title' => fake()->sentence(4), 'priority' => 'normal', 'due_at' => now()->addDays(2)];
    }
}
