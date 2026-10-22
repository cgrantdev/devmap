<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One-shot: provision an SEO agency account with the limited
 * 'seo_agency' role. Prints the generated password once to the
 * output so Colin can hand it off to the agency; it's hashed
 * before storage.
 */
class CreateSeoAgencyUser extends Command
{
    protected $signature = 'users:create-seo-agency {email} {name=Advantage Collective}';
    protected $description = 'Create an SEO agency user with limited admin access.';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $name = (string) $this->argument('name');

        $existing = User::where('email', $email)->first();
        if ($existing) {
            $this->warn("User {$email} already exists. Updating role to seo_agency.");
            $existing->forceFill([
                'role' => 'seo_agency',
                'email_verified_at' => $existing->email_verified_at ?: now(),
            ])->save();
            $this->info("Role set. Existing password unchanged.");
            return self::SUCCESS;
        }

        $password = Str::random(16);
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'seo_agency',
            'email_verified_at' => now(), // skip verification flow for agency onboarding
        ]);

        $this->info("Created {$email} (id={$user->id}, role=seo_agency).");
        $this->newLine();
        $this->line("Share these one-time:");
        $this->line("  Email:    {$email}");
        $this->line("  Password: {$password}");
        $this->line("  Login:    https://peptidemap.com/admin/login");
        $this->newLine();
        $this->comment("Agency can access: SEO dashboard, Products, Blogs, Encyclopedia,");
        $this->comment("Categories (compare pages), Page Analytics, CEO (GSC read).");
        $this->comment("Blocked: Vendors, Users, Settings, Dev Console, Affiliates, Deals.");

        return self::SUCCESS;
    }
}
