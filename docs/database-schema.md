# Database Schema - Government Document Submission System

This document outlines the database schema design, normalization, relationships, indexing strategies, and design decisions for the Government Document Eligibility System.

---

## 1. Overview
The database is designed using **PostgreSQL** for production and **SQLite** for local feature and unit testing. The schema aims to support millions of document pengajuan (projects) with optimized queries, avoiding N+1 issues, utilizing composite indexes, and using UUIDs for all primary keys to guarantee global uniqueness and prevent sequence scan vulnerabilities.

---

## 2. Entity Relationship Diagram (ERD)

```
  +-------------+              +-----------------+
  |    users    | 1 -------- * |    projects     |
  +-------------+              +-----------------+
         |                              |
         |                              | 1
         |                              v
         |                      +-----------------+
         |                      |    documents    |
         |                      +-----------------+
         |                              ^
         |                              |
         | 1                            | *
         +------------------------------+
         |
         | 1
         v
  +-----------------+
  |  approval_logs  | * <------- 1 (project_id)
  +-----------------+
```

---

## 3. Tables

### 1. `users`
Represents system actors (Applicants and Reviewers) with Spatie Roles/Permissions.

| Column | Type | Nullable | Default | Index | Description |
|--------|------|----------|---------|-------|-------------|
| `id` | uuid | NO | UUID() | PK | Primary Key |
| `name` | string | NO | - | - | User's full name |
| `email` | string | NO | - | UNIQUE | Unique login email |
| `email_verified_at` | timestamp | YES | NULL | - | Email verification time |
| `password` | string | NO | - | - | Hashed password |
| `remember_token` | string | YES | NULL | - | Remember-me token |
| `created_at` | timestamp | YES | NULL | - | Creation timestamp |
| `updated_at` | timestamp | YES | NULL | - | Update timestamp |
| `deleted_at` | timestamp | YES | NULL | - | Soft-delete timestamp |

### 2. `projects` (Permohonan/Pengajuan)
Represents the main document submission entity.

| Column | Type | Nullable | Default | Index | Description |
|--------|------|----------|---------|-------|-------------|
| `id` | uuid | NO | UUID() | PK | Primary Key |
| `user_id` | uuid | NO | - | INDEX, FK | Applicant who created the project |
| `title` | string | NO | - | - | Title of document request |
| `description` | text | NO | - | - | Description of project scope |
| `status` | string | NO | - | INDEX | Current state in workflow |
| `created_at` | timestamp | YES | NULL | INDEX | Creation timestamp (desc sorting) |
| `updated_at` | timestamp | YES | NULL | - | Update timestamp |

* *Composite Index*: `(user_id, status)` for fast dashboard metrics aggregations.

### 3. `documents` (Uploaded files)
Stores metadata of privately stored document attachments.

| Column | Type | Nullable | Default | Index | Description |
|--------|------|----------|---------|-------|-------------|
| `id` | uuid | NO | UUID() | PK | Primary Key |
| `project_id` | uuid | NO | - | INDEX, FK | Project this document belongs to |
| `filename` | string | NO | - | - | Unique UUID-based file name |
| `original_name` | string | NO | - | - | Original uploaded file name |
| `file_path` | string | NO | - | - | Path in private storage disk |
| `file_size` | bigint | NO | - | - | File size in bytes |
| `mime_type` | string | NO | - | - | Real MIME type resolved from binary check |
| `created_at` | timestamp | YES | NULL | - | Upload timestamp |
| `updated_at` | timestamp | YES | NULL | - | Update timestamp |

### 4. `approval_logs` (Audit Log / Timeline)
Stores historical record of state machine transitions and reviewer feedback.

| Column | Type | Nullable | Default | Index | Description |
|--------|------|----------|---------|-------|-------------|
| `id` | uuid | NO | UUID() | PK | Primary Key |
| `project_id` | uuid | NO | - | INDEX, FK | Project associated with this action |
| `user_id` | uuid | NO | - | INDEX, FK | Actor who triggered the transition |
| `action` | string | NO | - | - | Action name (e.g. CREATE, SUBMIT, REVISE, etc.) |
| `old_status` | string | YES | NULL | - | State before transition |
| `new_status` | string | NO | - | - | State after transition |
| `notes` | text | YES | NULL | - | Review feedback or audit explanation |
| `created_at` | timestamp | YES | NULL | INDEX | Timestamp of transition |

* *Composite Index*: `(project_id, created_at)` for loading sequential project timelines.

---

## 4. Normalization Audit
The schema complies with the **Third Normal Form (3NF)**:
* **1NF**: All table columns represent atomic values. No repeating groups.
* **2NF**: All non-key fields are fully functionally dependent on their table primary keys (UUIDs).
* **3NF**: There are no transitive dependencies. Non-key columns do not depend on other non-key columns (e.g., user profile details are not stored in projects, and project properties are not duplicated in approval logs).

---

## 5. Relationships
1. **User → Project (1:N)**: A user can submit multiple projects. Guarded in policy to prevent IDOR.
2. **Project → Document (1:1 / 1:N)**: A project has one active document. If updated, the old document is deleted and replaced.
3. **Project → ApprovalLog (1:N)**: A project compiles sequential audit steps representing its workflow progression.
4. **User → ApprovalLog (1:N)**: A user (applicant or reviewer) generates audit logs upon triggering status changes.

---

## 6. Primary Key Strategy
Consistent usage of **UUIDs (Universally Unique Identifiers)** across all tables (including Spatie permissions tables and Laravel Sanctum tokens). This is achieved via:
* Migration setup: `$table->uuid('id')->primary();`
* Model setup: `use App\Traits\HasUuid;`
* Foreign Key definitions: `$table->uuid('user_id');`

---

## 7. Status & Enum Lifecycle
Status is stored as a `string` to ensure portability, readability, and compatibility between different database engines (SQLite during tests and PostgreSQL in production).
State transitions are guarded in `WorkflowService.php`:
```
draft -> submitted -> under_review -> (revision_required -> submitted) OR approved OR rejected
```

---

## 8. Foreign Key Constraints
* **users.id** in `projects.user_id`: `cascadeOnDelete()`
* **projects.id** in `documents.project_id`: `cascadeOnDelete()`
* **projects.id** in `approval_logs.project_id`: `cascadeOnDelete()`
* **users.id** in `approval_logs.user_id`: `cascadeOnDelete()` (Note: Soft deletion on users is handled via model relation `withTrashed()`).

---

## 9. Indexing Strategy
To optimize query performance for millions of database rows, the following indexes are set:

1. **Single-Column Indexes**:
   * `projects.user_id` (Filtering own projects for applicants).
   * `projects.status` (Dashboard aggregates filtering).
   * `projects.created_at` (Default chronological sort).
   * `approval_logs.user_id` (Auditing actor actions).

2. **Composite Indexes**:
   * `projects(user_id, status)`: Promotes sub-second execution for applicant dashboard metrics queries.
   * `approval_logs(project_id, created_at)`: Optimizes sequential loading of timeline history logs.

---

## 10. File Storage Strategy
Binary files are **never** stored in the database. Instead, files are uploaded to private storage (`storage/app/private/documents`), names are hashed into secure UUIDs, and metadata (original name, path, biner MIME, size) is saved in the `documents` table. Authentication is verified via Policy before serving the file stream using `Storage::download()`.

---

## 11. Design Decisions
1. **Model Soft Deletes on User only**:
   If a user is soft-deleted, their projects and audit trails are preserved for historical audit purposes. Relations in `Project.php` and `ApprovalLog.php` are configured with `withTrashed()` to avoid null pointer exceptions during serialization.
2. **String Status column**:
   Avoided native PostgreSQL enums to facilitate migrations across multiple SQL platforms and allow easier workflow extension.
