<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contact> */
class ContactFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'companie_id' => fn (array $attributes) => Customer::factory()->create(['user_id' => $attributes['user_id']])->id, 'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'email' => fake()->safeEmail()];
    }
}
