# Bonus Features Documentation

This document describes the design and configuration of the advanced bonus features implemented in the Government Document Submission System.

---

## 1. Authentication
* **Provider**: Laravel Sanctum.
* **Mechanism**: Bearer token authentication.
* **Endpoints**:
  * `POST /api/v1/auth/register`: Create applicant account and issue Bearer token.
  * `POST /api/v1/auth/login`: Authenticate and issue Bearer token.
  * `POST /api/v1/auth/logout`: Revoke token.
  * `GET /api/v1/auth/me`: Fetch profile and roles.

---

## 2. Roles & Permissions (RBAC)
* **Package**: Spatie Laravel Permission.
* **Roles**:
  * `applicant`: Allowed to create, update, submit, delete draft projects, and download own documents.
  * `reviewer`: Allowed to list workflow projects, start review, request revisions, approve, reject, view timelines, and download any document.
* **Backend Gates**: Enforced in controllers using `$this->authorize()` mapped to Policies (`app/Policies/ProjectPolicy.php`).

---

## 3. High-Performance CSV Export
* **Route**: `GET /api/v1/projects/export` (Reviewer-only).
* **Strategy**: StreamedResponse.
* **Optimization**: Uses database `cursor()` to fetch rows from the PostgreSQL database one-by-one. It formats them using PHP's native `fputcsv` streaming directly to the client instead of compiling a large array in memory. This guarantees `<200ms` start response time and prevents memory exhaustion (*OOM*) for 10,000+ records. Includes UTF-8 BOM for Microsoft Excel compatibility.

---

## 4. Cache Strategy
* **Driver**: Local database cache for development/testing; Redis cache for production.
* **Candidate**: Dashboard metrics aggregation.
* **TTL**: 300 seconds.
* **Invalidation (Bust)**: Caches are cleared immediately upon state machine status transitions inside `WorkflowService.php`:
  ```php
  $this->clearDashboardCache($project->user_id);
  $this->clearReviewerDashboardCache();
  ```

---

## 5. Queue System
* **Driver**: Database queue worker for local development; Redis queues for production.
* **Job**: `ProcessDocumentJob` implements `ShouldQueue`.
* **Execution**: Triggered asynchronously immediately after an applicant uploads a new file inside `DocumentService@upload`.
* **Role**: Simulates file post-processing (e.g., antivirus magic byte validation or watermark generation).

---

## 6. Testing & CI/CD
* **Testing Suite**: 17 Feature test cases covering authentication flow, RBAC gates, state transition rules, and IDOR validation.
* **CI/CD Pipeline**: GitHub Actions (`.github/workflows/ci.yml`) runs on push and pull-requests targeting main/develop. Automatically runs Pint formatting test, compiles Vite frontend assets, sets up an in-memory database, runs the test suite, and triggers Gemini AI Reviewer scanning.
