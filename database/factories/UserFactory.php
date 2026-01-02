<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'avatar_url' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Add provider id (e.g. github_id, google_id) to the factory state.
     */
    public function withProvider(string $provider, ?string $id = null): static
    {
        $col = strtolower($provider) . '_id';
        return $this->state(fn (array $attributes) => [
            $col => $id ?? fake()->uuid(),
        ]);
    }

    public function withGithubId(?string $id = null): static { return $this->withProvider('github', $id); }
    public function withGoogleId(?string $id = null): static { return $this->withProvider('google', $id); }
    public function withFacebookId(?string $id = null): static { return $this->withProvider('facebook', $id); }
    public function withTwitterId(?string $id = null): static { return $this->withProvider('twitter', $id); }
    public function withAppleId(?string $id = null): static { return $this->withProvider('apple', $id); }
}
