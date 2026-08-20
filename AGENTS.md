# AI Coding Agent Guidelines (AGENTS.md)

This document outlines the strict engineering guidelines, conventions, rules, and architecture choices for the AI coding agent working on the Government Document Eligibility System. Adhere to these principles without exception to produce high-performance, robust, and clean code.

---

## 1. Clean Architecture & Structure
We use a clean, decoupled layer approach in Laravel:
- **Controllers**: Handlers for HTTP inputs and routing. Keep them under 100 lines. Do not put business logic here. Delegate to Services or Actions.
- **Form Requests (`app/Http/Requests`)**: 100% of validation must occur here. Never validate inside Controllers.
- **Services/Actions (`app/Services` or `app/Actions`)**: All domain rules, state transitions, file storage handling, and audit logging live in this layer. Services are dependency-injected into Controllers.
- **Models (`app/Models`)**: House Eloquent configuration, relationships, and custom casts. Avoid fat models; do not write heavy calculations or transitions directly in models. Use Services instead.
- **API Resources (`app/Http/Resources`)**: Strict formatting of JSON outputs. Never return raw model arrays from controllers.
- **Policies (`app/Policies`)**: Centralized access rules. Every single endpoint showing or modifying a project must be guarded by a Policy check.

---

## 2. Coding Conventions (PSR-12 & Modern PHP)
- Write modern, type-safe PHP (PHP 8.2+). Use strict types: `declare(strict_types=1);` where applicable.
- Define explicit method argument types and return type declarations on every single function.
- Strictly adhere to PSR-12 formatting. Use Laravel Pint (`./vendor/bin/pint`) to auto-format changes.
- Never suppress warnings, bypass type checking, or use raw object reflection/prototype hacking.

---

## 3. Database Rules (PostgreSQL & Eloquent)
- **Primary Keys**: Always use UUIDs for database tables (`id` as UUID). Define a trait or base setup for handling UUID generation on model boot.
- **Foreign Keys**: Every foreign key column must have an index and explicit foreign key constraints.
- **Aggregates and Queries**:
  - Never use `Model::all()`. Use `select()` to fetch only necessary columns.
  - Always use Eager Loading (`with(['relation'])`) to eliminate N+1 queries.
  - Use `cursorPaginate()` or `paginate()` for listings.
- **Indices**:
  - Index status, user_id, and created_at fields.
  - Use composite indexes where filters use multiple fields (e.g. `(user_id, status)`).
- **Transactions**: Wrap multi-table modifications (e.g. creating a project + saving its initial log) inside a Database Transaction (`DB::transaction(...)`).

---

## 4. API Response Standards
Every response from the REST API must conform to this precise format:

### Success Response
```json
{
  "success": true,
  "message": "Operation successful.",
  "data": {
    "item": {}
  }
}
```

### Error Response (e.g., Validation or Exception)
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "field_name": [
      "The field_name field is required."
    ]
  }
}
```

- Always return the correct HTTP Status Codes (e.g., `200 OK`, `201 Created`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `422 Unprocessable Entity`).

---

## 5. Security & Authorization Rules
- **No IDOR**: Every request reading, updating, or deleting a resource must verify that the authenticated user has access rights using Laravel Policies.
- **No Mass Assignment**: Explicitly define `$fillable` fields in models. Never use `$guarded = []`.
- **Upload Guards**: Check mime types against hardcoded allowed extensions (`pdf`, `doc`, `docx`, `xls`, `xlsx`) using PHP's `file` info rather than trusting user-sent headers. Generate unique UUID filenames before storing them in private storage (`storage/app/private`).

---

## 6. Frontend (Vue 3 / Inertia or REST + Pinia)
Since we are using a REST API approach for this prototype, we will build a clean Vue 3 Single Page Application (SPA) that talks to our REST endpoints.
- **State Management**: Use Pinia for session, user info, dashboard metrics, and project catalogs.
- **Composition API**: Use `<script setup>` syntax for all Vue 3 components.
- **Component Limits**: Keep components under 250 lines. Break down heavy interfaces into modular sub-components.
- **Styling**: Use Tailwind CSS utility classes. Stick to a modern, crisp blue-white-gray palette with gentle curves and shadows.
- **Asynchronous States**: Always implement loading indicators (spinners, skeletons) and graceful error UI displays.

---

## 7. Testing Rules
Write comprehensive tests for every feature before declaring it "done":
- **Feature Tests**: Put testing endpoints, role-based gates, file uploads, and state machine validations in `tests/Feature`.
- **Unit Tests**: Test core domain rules, status validation matrices, and service calculations in `tests/Unit`.
- **Harness**: Run `php artisan test` to confirm 100% of tests are passing after any change.

---

## 8. Definition of Done (DoD)
A feature is complete only if:
1. All database migrations, models, seeds, and API routes are defined and optimized.
2. Form Requests validate 100% of user inputs.
3. Access boundaries are protected by Laravel Policies.
4. Response formats strictly follow the success/error standard.
5. All Feature & Unit tests are passing successfully.
6. The codebase passes static analysis and formatting tests (`pint`).
7. Changes are logged incrementally via structured, meaningful Git commits.

---

## 9. Database Rules
- **Database Engine**: PostgreSQL 17 for production environments; SQLite (in-memory) for testing.
- **ID Strategy**: Universally Unique Identifiers (UUIDv4) for all primary and foreign key columns. Incrementing integer IDs are prohibited except for default system tools (e.g. personal access tokens).
- **Naming Convention**: `snake_case` for table names (pluralized, e.g. `projects`) and column names; singular `PascalCase` for model classes (e.g. `Project`).
- **Relationship Convention**: Define clean Eloquent relation methods. Relationships linking to soft-deletable models (such as `User`) must chain `->withTrashed()` to prevent NPE crashes in history, logs, or details serialization.
- **Migration Convention**: All migrations must be reversible. Foreign key constraints must have explicit indexes and appropriate cascading actions (e.g. `cascadeOnDelete()`).
- **Constraint Policy**: Enforce constraints (nullability, uniqueness, foreign keys) at the database layer while validating requests in Form Requests.
- **Prohibited Destructive Migration**: Do not run destructive database actions (e.g., raw table drops or modifications of existing columns) on production schemas. Use migrations to add fields or modify constraints safely.
