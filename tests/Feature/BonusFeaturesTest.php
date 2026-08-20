<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessDocumentJob;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BonusFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $applicant;

    protected User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Spatie Roles
        $applicantRole = Role::create(['name' => 'applicant', 'guard_name' => 'web']);
        $reviewerRole = Role::create(['name' => 'reviewer', 'guard_name' => 'web']);

        // Create Users
        $this->applicant = User::factory()->create();
        $this->applicant->assignRole($applicantRole);

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole($reviewerRole);

        Storage::fake('local');
    }

    public function test_reviewer_can_export_projects_to_csv(): void
    {
        // Create sample project for export
        Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Project Export Test',
            'description' => 'Test project description.',
            'status' => 'submitted', // Reviewer sees non-drafts
        ]);

        $response = $this->actingAs($this->reviewer)
            ->getJson('/api/v1/projects/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="permohonan_kelayakan_export.csv"');

        $content = $response->streamedContent();

        $this->assertStringContainsString('ID', $content);
        $this->assertStringContainsString('Judul Permohonan', $content);
        $this->assertStringContainsString('Project Export Test', $content);
    }

    public function test_applicant_cannot_export_projects_to_csv(): void
    {
        $response = $this->actingAs($this->applicant)
            ->getJson('/api/v1/projects/export');

        $response->assertStatus(403);
    }

    public function test_document_upload_dispatches_queue_job(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->create('amdal.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->applicant)
            ->postJson('/api/v1/projects', [
                'title' => 'Project Queue Test',
                'description' => 'A project to verify background queue dispatch.',
                'document' => $file,
            ]);

        $response->assertStatus(201);

        // Assert job was pushed to the queue
        Queue::assertPushed(ProcessDocumentJob::class);
    }
}
