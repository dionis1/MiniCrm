<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'name' => fake()->name(), 'company_name' => fake()->company(), 'email' => fake()->safeEmail(), 'stage' => 'new', 'deal_value' => fake()->numberBetween(1000, 30000), 'probability' => 25, 'version' => 0];
    }
}
