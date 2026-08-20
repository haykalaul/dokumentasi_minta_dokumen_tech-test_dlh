<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Roles with UUIDs
        $applicantRole = Role::create(['id' => (string) Str::uuid(), 'name' => 'applicant', 'guard_name' => 'web']);
        $reviewerRole = Role::create(['id' => (string) Str::uuid(), 'name' => 'reviewer', 'guard_name' => 'web']);

        // 2. Pre-hash password for performance
        $password = Hash::make('password');

        // Create Demo Accounts
        $demoApplicantId = (string) Str::uuid();
        $demoApplicant = User::create([
            'id' => $demoApplicantId,
            'name' => 'Demo Applicant',
            'email' => 'applicant@example.com',
            'password' => $password,
        ]);
        $demoApplicant->assignRole($applicantRole);

        $demoReviewerId = (string) Str::uuid();
        $demoReviewer = User::create([
            'id' => $demoReviewerId,
            'name' => 'Demo Reviewer',
            'email' => 'reviewer@example.com',
            'password' => $password,
        ]);
        $demoReviewer->assignRole($reviewerRole);

        $this->command->info('Demo accounts created.');

        // 3. Bulk Seed Users (1000 applicants & 1000 reviewers)
        $applicants = [];
        $reviewers = [];
        $modelHasRoles = [];

        // Generate Applicants
        $applicantIds = [];
        for ($i = 1; $i <= 1000; $i++) {
            $id = (string) Str::uuid();
            $applicantIds[] = $id;
            $applicants[] = [
                'id' => $id,
                'name' => "Applicant {$i}",
                'email' => "applicant{$i}@example.com",
                'password' => $password,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $modelHasRoles[] = [
                'role_id' => $applicantRole->id,
                'model_type' => User::class,
                'model_id' => $id,
            ];
        }

        // Generate Reviewers
        $reviewerIds = [];
        for ($i = 1; $i <= 1000; $i++) {
            $id = (string) Str::uuid();
            $reviewerIds[] = $id;
            $reviewers[] = [
                'id' => $id,
                'name' => "Reviewer {$i}",
                'email' => "reviewer{$i}@example.com",
                'password' => $password,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $modelHasRoles[] = [
                'role_id' => $reviewerRole->id,
                'model_type' => User::class,
                'model_id' => $id,
            ];
        }

        // Insert Users in chunks of 500
        $this->command->info('Inserting users...');
        foreach (array_chunk($applicants, 500) as $chunk) {
            DB::table('users')->insert($chunk);
        }
        foreach (array_chunk($reviewers, 500) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        // Assign Roles in chunks
        $this->command->info('Assigning roles...');
        foreach (array_chunk($modelHasRoles, 500) as $chunk) {
            DB::table('model_has_roles')->insert($chunk);
        }

        // 4. Bulk Seed Projects (10,000 projects)
        $statuses = ['draft', 'submitted', 'under_review', 'revision_required', 'approved', 'rejected'];
        $projects = [];

        $this->command->info('Generating projects data...');
        for ($i = 1; $i <= 10000; $i++) {
            $id = (string) Str::uuid();
            $status = $statuses[array_rand($statuses)];
            $userId = $applicantIds[array_rand($applicantIds)];

            // Mix in some projects for the demo user
            if ($i <= 15) {
                $userId = $demoApplicantId;
            }

            $projects[] = [
                'id' => $id,
                'user_id' => $userId,
                'title' => 'Permohonan Kelayakan Dokumen '.Str::random(8),
                'description' => "Deskripsi pengajuan permohonan kelayakan dokumen nomor {$i}.",
                'status' => $status,
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now(),
            ];
        }

        $this->command->info('Inserting projects...');
        foreach (array_chunk($projects, 500) as $chunk) {
            DB::table('projects')->insert($chunk);
        }

        // 5. Bulk Seed Documents & Logs
        $documents = [];
        $approvalLogs = [];

        $this->command->info('Generating documents & approval logs...');
        foreach ($projects as $project) {
            $projectId = $project['id'];
            $status = $project['status'];
            $userId = $project['user_id'];
            $createdAt = $project['created_at'];

            // Every project has 1 document
            $docId = (string) Str::uuid();
            $documents[] = [
                'id' => $docId,
                'project_id' => $projectId,
                'filename' => Str::random(40).'.pdf',
                'original_name' => 'dokumen_persyaratan.pdf',
                'file_path' => 'private/documents/'.Str::random(40).'.pdf',
                'file_size' => rand(100000, 5000000), // 100KB to 5MB
                'mime_type' => 'application/pdf',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            // Add CREATE log
            $approvalLogs[] = [
                'id' => (string) Str::uuid(),
                'project_id' => $projectId,
                'user_id' => $userId,
                'action' => 'CREATE',
                'old_status' => null,
                'new_status' => 'draft',
                'notes' => 'Project created as draft.',
                'created_at' => $createdAt,
            ];

            if ($status !== 'draft') {
                // Add SUBMIT log
                $submittedTime = $createdAt->copy()->addMinutes(rand(10, 60));
                $approvalLogs[] = [
                    'id' => (string) Str::uuid(),
                    'project_id' => $projectId,
                    'user_id' => $userId,
                    'action' => 'SUBMIT',
                    'old_status' => 'draft',
                    'new_status' => 'submitted',
                    'notes' => 'Project submitted for review.',
                    'created_at' => $submittedTime,
                ];

                if ($status !== 'submitted') {
                    $reviewerId = $reviewerIds[array_rand($reviewerIds)];
                    $reviewTime = $submittedTime->copy()->addHours(rand(1, 24));

                    // Add START_REVIEW log
                    $approvalLogs[] = [
                        'id' => (string) Str::uuid(),
                        'project_id' => $projectId,
                        'user_id' => $reviewerId,
                        'action' => 'START_REVIEW',
                        'old_status' => 'submitted',
                        'new_status' => 'under_review',
                        'notes' => 'Reviewer started reviewing the project.',
                        'created_at' => $reviewTime,
                    ];

                    if ($status === 'revision_required') {
                        $revisionTime = $reviewTime->copy()->addHours(rand(1, 12));
                        $approvalLogs[] = [
                            'id' => (string) Str::uuid(),
                            'project_id' => $projectId,
                            'user_id' => $reviewerId,
                            'action' => 'REVISE',
                            'old_status' => 'under_review',
                            'new_status' => 'revision_required',
                            'notes' => 'Please update document and resubmit.',
                            'created_at' => $revisionTime,
                        ];
                    } elseif ($status === 'approved') {
                        $approveTime = $reviewTime->copy()->addHours(rand(1, 12));
                        $approvalLogs[] = [
                            'id' => (string) Str::uuid(),
                            'project_id' => $projectId,
                            'user_id' => $reviewerId,
                            'action' => 'APPROVE',
                            'old_status' => 'under_review',
                            'new_status' => 'approved',
                            'notes' => 'All documents are valid. Project approved.',
                            'created_at' => $approveTime,
                        ];
                    } elseif ($status === 'rejected') {
                        $rejectTime = $reviewTime->copy()->addHours(rand(1, 12));
                        $approvalLogs[] = [
                            'id' => (string) Str::uuid(),
                            'project_id' => $projectId,
                            'user_id' => $reviewerId,
                            'action' => 'REJECT',
                            'old_status' => 'under_review',
                            'new_status' => 'rejected',
                            'notes' => 'Missing essential environment permits. Project rejected.',
                            'created_at' => $rejectTime,
                        ];
                    }
                }
            }
        }

        $this->command->info('Inserting documents...');
        foreach (array_chunk($documents, 500) as $chunk) {
            DB::table('documents')->insert($chunk);
        }

        $this->command->info('Inserting approval logs...');
        foreach (array_chunk($approvalLogs, 500) as $chunk) {
            DB::table('approval_logs')->insert($chunk);
        }

        $this->command->info('Database seeding completed successfully!');
    }
}
