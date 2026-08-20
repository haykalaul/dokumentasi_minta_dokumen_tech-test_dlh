# Seed Data Configuration

This document outlines the dataset volume, seeding strategy, performance optimizations, and demo credentials used to validate the Government Document Eligibility System under load.

---

## 1. Target Volume
The database contains a default production-ready benchmark dataset generating:
* **Applicants (Pemohon)**: 1,000 accounts
* **Reviewers (Penilai)**: 1,000 accounts
* **Total Users**: 2,002 accounts (including 2 manual demo accounts)
* **Projects (Permohonan)**: 10,000 pengajuan
* **Documents (Lampiran)**: 10,000 files (1 per project)
* **Approval Logs (Audit Trails)**: ~20,000+ chronological states (CREATE and transition steps based on status distribution)

---

## 2. Status Distribution Strategy
The 10,000 projects are realistically distributed across the 6 system workflow states to simulate live operations:
* **Draft**: ~16.6% (Only applicant-visible)
* **Submitted**: ~16.6% (Pending review)
* **Under Review**: ~16.6% (Active reviewer lock)
* **Revision Required**: ~16.6% (Returned to applicant)
* **Approved**: ~16.6% (Permanent final lock)
* **Rejected**: ~16.6% (Permanent final lock)

---

## 3. Performance Seeding Optimizations
To seed 22,000+ records in under a minute without database timeouts:
1. **Pre-hashed Passwords**: Password `password` is hashed once (`Hash::make('password')`) and reused for all 2,000 accounts. This saves 2,000 CPU cycles of bcrypt hashing.
2. **Chunked Inserts**: Records are compiled in memory and inserted into the database in chunks of `500` using `DB::table()->insert()` instead of slower single-row Eloquent creation.
3. **UUID Generation**: Pre-generated in PHP using `Str::uuid()` to resolve foreign keys deterministically before chunk insertion.

---

## 4. Demo Credentials
Use these developer credentials to log in and test role gates:

### Applicant (Pemohon)
* **Email**: `applicant@example.com`
* **Password**: `password`

### Reviewer (Penilai)
* **Email**: `reviewer@example.com`
* **Password**: `password`

### Dummy Batches
* **Applicants**: `applicant1@example.com` through `applicant1000@example.com` (Password: `password`)
* **Reviewers**: `reviewer1@example.com` through `reviewer1000@example.com` (Password: `password`)
