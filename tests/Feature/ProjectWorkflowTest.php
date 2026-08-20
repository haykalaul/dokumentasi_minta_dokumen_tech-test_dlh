<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $applicant;

    protected User $otherApplicant;

    protected User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Roles
        $applicantRole = Role::create(['name' => 'applicant', 'guard_name' => 'web']);
        $reviewerRole = Role::create(['name' => 'reviewer', 'guard_name' => 'web']);

        // 2. Create Users
        $this->applicant = User::factory()->create(['name' => 'John Applicant', 'email' => 'john@example.com']);
        $this->applicant->assignRole($applicantRole);

        $this->otherApplicant = User::factory()->create(['name' => 'Jane Applicant', 'email' => 'jane@example.com']);
        $this->otherApplicant->assignRole($applicantRole);

        $this->reviewer = User::factory()->create(['name' => 'Officer Reviewer', 'email' => 'officer@example.com']);
        $this->reviewer->assignRole($reviewerRole);

        Storage::fake('local');
    }

    public function test_user_can_register_as_applicant(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New Applicant',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['access_token', 'user']]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_user_can_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password', // Default factory password
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['access_token', 'user']]);
    }

    public function test_unauthenticated_user_cannot_access_projects(): void
    {
        $response = $this->getJson('/api/v1/projects');
        $response->assertStatus(401);
    }

    public function test_applicant_can_create_project_with_valid_document(): void
    {
        $file = UploadedFile::fake()->create('amdal_draft.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->applicant)
            ->postJson('/api/v1/projects', [
                'title' => 'Pembangunan Pabrik Kertas',
                'description' => 'Rencana pembangunan pabrik kertas kapasitas besar.',
                'document' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Pembangunan Pabrik Kertas')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('projects', ['title' => 'Pembangunan Pabrik Kertas', 'status' => 'draft']);
    }

    public function test_applicant_cannot_create_project_with_invalid_document_type(): void
    {
        // Spoofing file extension to .pdf but mime is exe/malicious
        $file = UploadedFile::fake()->create('malware.pdf', 100, 'application/x-msdownload');

        $response = $this->actingAs($this->applicant)
            ->postJson('/api/v1/projects', [
                'title' => 'Pembangunan Pabrik Kertas',
                'description' => 'Rencana pembangunan pabrik kertas kapasitas besar.',
                'document' => $file,
            ]);

        // Should trigger validation error
        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_applicant_can_update_draft_project(): void
    {
        $project = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Initial Title',
            'description' => 'Initial Description',
            'status' => 'draft',
        ]);

        Document::create([
            'project_id' => $project->id,
            'filename' => 'old.pdf',
            'original_name' => 'old.pdf',
            'file_path' => 'private/documents/old.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
        ]);

        $newFile = UploadedFile::fake()->create('updated_amdal.docx', 800, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($this->applicant)
            ->putJson("/api/v1/projects/{$project->id}", [
                'title' => 'Updated Title',
                'description' => 'Updated Description',
                'document' => $newFile,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.description', 'Updated Description');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'Updated Title']);
    }

    public function test_applicant_cannot_update_submitted_project(): void
    {
        $project = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Submitted Project',
            'description' => 'Details here',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->applicant)
            ->putJson("/api/v1/projects/{$project->id}", [
                'title' => 'Hack Attempt',
                'description' => 'Details here',
            ]);

        $response->assertStatus(403);
    }

    public function test_applicant_can_submit_project(): void
    {
        $project = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Ready to Submit',
            'description' => 'Details here',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->applicant)
            ->postJson("/api/v1/projects/{$project->id}/submit");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'submitted']);
        $this->assertDatabaseHas('approval_logs', ['project_id' => $project->id, 'action' => 'SUBMIT']);
    }

    public function test_reviewer_can_see_submitted_projects_but_not_drafts(): void
    {
        // 1. Create a draft project (Reviewer should NOT see this)
        $draftProj = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Draft Project',
            'description' => 'Draft text',
            'status' => 'draft',
        ]);

        // 2. Create a submitted project (Reviewer should see this)
        $submittedProj = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Submitted Project',
            'description' => 'Submitted text',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->reviewer)->getJson('/api/v1/projects');

        $response->assertStatus(200);
        $projectIds = collect($response->json('data.data'))->pluck('id')->toArray();

        $this->assertContains($submittedProj->id, $projectIds);
        $this->assertNotContains($draftProj->id, $projectIds);
    }

    public function test_reviewer_can_start_review(): void
    {
        $project = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Submitted Project',
            'description' => 'Submitted text',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/v1/projects/{$project->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'under_review');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'under_review']);
    }

    public function test_reviewer_can_request_revision_with_notes(): void
    {
        $project = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Review Project',
            'description' => 'Review text',
            'status' => 'under_review',
        ]);

        // Notes is required for revision requests
        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/v1/projects/{$project->id}/revision", [
                'notes' => 'Tolong lengkapi dokumen tata ruang wilayah.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'revision_required');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'revision_required']);
        $this->assertDatabaseHas('approval_logs', [
            'project_id' => $project->id,
            'action' => 'REVISE',
            'notes' => 'Tolong lengkapi dokumen tata ruang wilayah.',
        ]);
    }

    public function test_applicant_can_edit_revision_and_resubmit(): void
    {
        $project = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Revision Needed',
            'description' => 'Text here',
            'status' => 'revision_required',
        ]);

        Document::create([
            'project_id' => $project->id,
            'filename' => 'old_rev.pdf',
            'original_name' => 'old_rev.pdf',
            'file_path' => 'private/documents/old_rev.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
        ]);

        $newFile = UploadedFile::fake()->create('revised_doc.pdf', 600, 'application/pdf');

        // 1. Applicant updates the details and file
        $updateResponse = $this->actingAs($this->applicant)
            ->putJson("/api/v1/projects/{$project->id}", [
                'title' => 'Revised Title',
                'description' => 'Revised Description',
                'document' => $newFile,
            ]);

        $updateResponse->assertStatus(200);

        // 2. Applicant resubmits the project
        $submitResponse = $this->actingAs($this->applicant)
            ->postJson("/api/v1/projects/{$project->id}/submit");

        $submitResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');
    }

    public function test_reviewer_can_approve_project(): void
    {
        $project = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Reviewing',
            'description' => 'Reviewing details',
            'status' => 'under_review',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/v1/projects/{$project->id}/approve", [
                'notes' => 'Memenuhi seluruh kriteria kelayakan dokumen.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'approved']);
    }

    public function test_reviewer_can_reject_project(): void
    {
        $project = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Reviewing',
            'description' => 'Reviewing details',
            'status' => 'under_review',
        ]);

        // Notes is required for rejection
        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/v1/projects/{$project->id}/reject", [
                'notes' => 'Dokumen AMDAL tidak memenuhi standar KLHS.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'rejected']);
    }

    public function test_unauthorized_applicant_cannot_access_other_applicant_project_details_idor(): void
    {
        $project = Project::create([
            'user_id' => $this->applicant->id,
            'title' => 'Secret Project',
            'description' => 'Confidential details',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->otherApplicant)
            ->getJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(403);
    }
}
