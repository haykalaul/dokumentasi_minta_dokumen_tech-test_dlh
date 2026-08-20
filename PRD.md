# Product Requirements Document (PRD)

## 1. Problem Statement
The current manual or semi-automated processes for government document submissions are slow, opaque, and hard to track. Furthermore, existing software systems often fail to scale efficiently, suffering from database N+1 query bottlenecks, slow page load speeds, and potential security vulnerabilities like unvalidated file uploads and Insecure Direct Object References (IDOR). 

## 2. Objective
To build an enterprise-grade, high-performance, secure prototype of a Government Document Eligibility System. The system must showcase clean architecture, rigorous stateful workflow transitions, complete auditability, and beautiful front-end user experience, supporting the following scale targets:
- **10,000 Projects / Permohonan**
- **1,000 Applicants / Pemohon**
- **1,000 Reviewers / Penilai**
- **API Response Time**: <200ms
- **Dashboard Load Time**: <300ms

---

## 3. Users & Roles
The system supports two core system roles (and optionally an administrator):
1. **Applicant (Pemohon)**
   - Registers and logs in.
   - Views applicant dashboard with submission summary cards (Draft, Submitted, Revision, Approved, Rejected) and a list of recent projects.
   - Creates and updates Project/Permohonan applications (only allowed in `draft` or `revision_required` states).
   - Uploads supporting documents (validated mime types, stored privately).
   - Submits a project to the workflow (moves state to `submitted`).
   - Views history of reviews and revisions for each project.
2. **Reviewer (Penilai)**
   - Logs in.
   - Views reviewer dashboard with statistics (Pending Review, Approved, Rejected, Revision Requested) and monthly submission trends.
   - Views all projects currently in workflow (`submitted`, `under_review`, `revision_required`, `approved`, `rejected`).
   - Begins review of a project (moves state to `under_review`).
   - Requests a revision with specific feedback/comments (moves state to `revision_required`).
   - Approves a project (moves state to `approved`).
   - Rejects a project (moves state to `rejected`).
   - Views audit log / history of any project.

---

## 4. State Machine Workflow Rules
Status transitions are strictly controlled through a dedicated workflow layer/service to avoid arbitrary status changes.

| Initial State | Target State | Permitted Actor | Description / Actions |
|---|---|---|---|
| *None* | **draft** | Applicant | Project is created and saved. |
| **draft** | **submitted** | Applicant | Project is submitted. Document details lock. |
| **submitted** | **under_review** | Reviewer | Reviewer acknowledges and starts processing. |
| **under_review** | **revision_required** | Reviewer | Reviewer rejects the current state and asks for correction, adding review comments. |
| **under_review** | **approved** | Reviewer | Final approval. Project locks permanently. |
| **under_review** | **rejected** | Reviewer | Final rejection. Project locks permanently. |
| **revision_required** | **submitted** | Applicant | Applicant completes corrections and resubmits the project. |

*Notes:*
- No actor can transition a project to an arbitrary state (e.g. bypassing `submitted` straight to `approved`, or editing a project in `submitted` state).
- Business validation rules are run during each transition.

---

## 5. Database Concept & Schema Design
We use PostgreSQL. All keys use **UUIDs** as specified in `GEMINI.md`.

### Core Tables & Fields
1. **users**
   - `id`: UUID (Primary Key)
   - `name`: String
   - `email`: String (Unique)
   - `password`: String (Hashed)
   - `created_at`, `updated_at`, `deleted_at` (Soft Delete)
   - *Indexes*: `email` (Unique Index)

2. **projects** (Permohonan)
   - `id`: UUID (Primary Key)
   - `user_id`: UUID (Foreign Key pointing to `users.id`)
   - `title`: String
   - `description`: Text
   - `status`: String (Enum/String: draft, submitted, under_review, revision_required, approved, rejected)
   - `created_at`, `updated_at`
   - *Indexes*:
     - `user_id` (Index for query filtering)
     - `status` (Index for dashboard aggregate queries)
     - `created_at` (Index for sorting & recent activity)
     - Composite Index: `(user_id, status)` for fast dashboard analytics.

3. **documents** (Uploaded Documents)
   - `id`: UUID (Primary Key)
   - `project_id`: UUID (Foreign Key pointing to `projects.id`)
   - `filename`: String (Randomly generated secure filename)
   - `original_name`: String (Original uploaded name)
   - `file_path`: String (Private disk storage path)
   - `file_size`: Integer (Bytes)
   - `mime_type`: String
   - `created_at`, `updated_at`
   - *Indexes*: `project_id`

4. **approval_logs** (Audit Log)
   - `id`: UUID (Primary Key)
   - `project_id`: UUID (Foreign Key pointing to `projects.id`)
   - `user_id`: UUID (Foreign Key pointing to `users.id`, who triggered the action)
   - `action`: String (e.g., CREATE, SUBMIT, START_REVIEW, REVISE, APPROVE, REJECT)
   - `old_status`: String (Nullable)
   - `new_status`: String
   - `notes`: Text (Nullable, contains review feedback or audit explanation)
   - `created_at`
   - *Indexes*:
     - `project_id`, `created_at` (Composite Index for sequential project audit histories)
     - `user_id`

---

## 6. API Scope (REST API v1)
All API endpoints are versioned under `/api/v1` and use standard, secure JSON serialization:

- **Authentication Endpoints**:
  - `POST /api/v1/auth/register` (Applicants only)
  - `POST /api/v1/auth/login` (Returns Bearer token)
  - `POST /api/v1/auth/logout` (Revokes current token)
  - `GET /api/v1/auth/me` (Returns authenticated user profile & roles)

- **Dashboard Endpoints**:
  - `GET /api/v1/dashboard` (Returns aggregated metrics tailored to user's role, cached for up to 300 seconds)

- **Project Endpoints**:
  - `GET /api/v1/projects` (Paginated list of projects; filtered for Applicants to own, open to Reviewers for all active projects)
  - `POST /api/v1/projects` (Create project - Applicants only)
  - `GET /api/v1/projects/{project}` (View project details, documents, and audit logs)
  - `PUT /api/v1/projects/{project}` (Update draft or revision_required projects)
  - `DELETE /api/v1/projects/{project}` (Delete draft projects)

- **Workflow Action Endpoints**:
  - `POST /api/v1/projects/{project}/submit` (Submit project to workflow)
  - `POST /api/v1/projects/{project}/reviews` (Start reviewing a project - moves to under_review)
  - `POST /api/v1/projects/{project}/revision` (Request revisions with reasons)
  - `POST /api/v1/projects/{project}/approve` (Approve project)
  - `POST /api/v1/projects/{project}/reject` (Reject project)

- **Audit & History Endpoints**:
  - `GET /api/v1/projects/{project}/history` (Get audit log of all status changes & notes)

---

## 7. Performance & Optimization Requirements
Because we target millions of database rows, the prototype implements best-practice optimizations:
1. **Query Eager Loading**: Avoid N+1 queries on projects, documents, and logs.
2. **Cursor Pagination**: Use `cursorPaginate()` for large datasets instead of offset-based pagination to prevent query degradation on deep pages.
3. **Database Indexing**: Put indexes on status, user_id, created_at, foreign keys, and compound filters.
4. **Caching**: Cache dashboard statistics for 300 seconds, busted only when a workflow status transition occurs for that user/scope.
5. **Background Queues**: Use Laravel Queues to handle asynchronous activities such as complex audits, file post-processing, and logging.

---

## 8. Security Specifications
1. **Authentication**: Handled securely via Laravel Sanctum (Bearer Token).
2. **Authorization**: Strict Policy validation (`app/Policies`) on all project operations. No applicant can view or update another applicant's project (IDOR protection). No applicant can perform reviewer-level transitions.
3. **File Upload Security**:
   - Limit: 10MB
   - Allowed Types: PDF, DOC, DOCX, XLS, XLSX
   - Filenames: Sanitized and hashed.
   - Storage: Saved in `storage/app/private` (never public). Download is served via authenticated routes.
4. **Input Validation**: Strictly enforced through dedicated Form Request classes. No raw request manipulation in controllers.

---

## 9. Non-Functional Requirements & Acceptance Criteria
- **Code Coverage**: Robust Feature and Unit tests covering every workflow state transition, unauthorized access attempts, validation failure cases, and security boundary violations.
- **UI Responsiveness**: Tailored design (government agency feel - clean, blue-white-gray scheme, crisp typography) utilizing Vue 3 with loading spinners, skeleton screen states, validation errors, and clear empty states.
