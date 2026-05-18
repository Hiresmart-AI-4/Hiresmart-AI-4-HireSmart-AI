<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Job;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::create([
            'user_id' => (string) \Str::uuid(),
            'name' => 'Admin User',
            'email' => 'admin@hiresmart.ai',
            'password' => Hash::make('password123'),
            'role' => 'recruiter',
        ]);

        // Create sample job seeker
        User::create([
            'user_id' => (string) \Str::uuid(),
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'job_seeker',
        ]);

        // Create sample jobs
        $jobs = [
            [
                'title' => 'Senior Laravel Developer',
                'company' => 'Tech Corp Inc.',
                'location' => 'Remote',
                'description' => 'We are looking for an experienced Laravel developer...',
                'required_skills' => ['PHP', 'Laravel', 'MySQL', 'JavaScript', 'REST API'],
                'nice_to_have_skills' => ['Vue.js', 'Docker', 'Redis'],
                'employment_type' => 'full-time',
                'experience_level' => 'senior',
                'salary_min' => 80000,
                'salary_max' => 120000,
                'application_deadline' => now()->addDays(30),
            ],
            [
                'title' => 'Frontend React Developer',
                'company' => 'Creative Solutions',
                'location' => 'New York, NY',
                'description' => 'Join our dynamic team...',
                'required_skills' => ['React', 'JavaScript', 'HTML5', 'CSS3', 'Redux'],
                'nice_to_have_skills' => ['TypeScript', 'Next.js', 'TailwindCSS'],
                'employment_type' => 'full-time',
                'experience_level' => 'mid',
                'salary_min' => 70000,
                'salary_max' => 95000,
                'application_deadline' => now()->addDays(45),
            ],
            [
                'title' => 'DevOps Engineer',
                'company' => 'CloudNative Inc.',
                'location' => 'San Francisco, CA',
                'description' => 'Seeking DevOps expert...',
                'required_skills' => ['AWS', 'Docker', 'Kubernetes', 'CI/CD', 'Terraform'],
                'nice_to_have_skills' => ['Python', 'Go', 'Jenkins'],
                'employment_type' => 'full-time',
                'experience_level' => 'senior',
                'salary_min' => 120000,
                'salary_max' => 160000,
                'application_deadline' => now()->addDays(60),
            ],
        ];

        foreach ($jobs as $job) {
            Job::create([
                'job_id' => (string) \Str::uuid(),
                'title' => $job['title'],
                'company' => $job['company'],
                'location' => $job['location'],
                'description' => $job['description'],
                'required_skills' => $job['required_skills'],
                'nice_to_have_skills' => $job['nice_to_have_skills'],
                'employment_type' => $job['employment_type'],
                'experience_level' => $job['experience_level'],
                'salary_min' => $job['salary_min'],
                'salary_max' => $job['salary_max'],
                'application_deadline' => $job['application_deadline'],
                'is_active' => true,
            ]);
        }
    }
}