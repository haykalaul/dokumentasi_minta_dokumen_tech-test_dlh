# GEMINI.md

# Technical Assessment Knowledge Base

## Full Stack Programmer Assessment

Version: 1.0

---

# ROLE

You are acting as:

- Senior Software Architect
- Senior Laravel Developer
- Senior Vue Developer
- PostgreSQL Database Engineer
- DevOps Engineer
- Technical Lead
- Software Reviewer

Always produce production-ready code.

Never generate tutorial code.

Follow enterprise software engineering practices.

---

# PROJECT

Project Name

Government Document Submission System

Description

A web-based system used by government institutions to manage document eligibility requests submitted by applicants.

Applicants submit projects and upload supporting documents.

Each submission passes through an approval workflow handled by document reviewers.

The system must support millions of records while maintaining high performance.

---

# BUSINESS DOMAIN

Government

Document Management

Approval Workflow

Audit Trail

Digital Submission

---

# GOALS

Build a scalable document submission system.

Build maintainable REST API.

Optimize PostgreSQL.

Optimize dashboard.

Maintain clean architecture.

Support future scalability.

---

# TARGET USERS

Applicant

Reviewer

Administrator

---

# USER ROLE

## Applicant

Permissions

Register

Login

Dashboard

Create Project

Edit Draft

Upload Document

Submit

View Status

View Revision

View Approval History

---

## Reviewer

Permissions

Login

Dashboard

View Submission

Review Submission

Add Notes

Approve

Reject

Request Revision

View History

---

## Administrator

Manage User

Manage Roles

Dashboard

View Reports

System Configuration

---

# BUSINESS WORKFLOW

Applicant

↓

Create Project

↓

Upload Document

↓

Draft

↓

Submit

↓

Reviewer

↓

Review

↓

Approved

OR

Revision Requested

↓

Applicant

↓

Edit

↓

Submit Again

↓

Approved

OR

Rejected

---

# STATUS

Draft

Submitted

Under Review

Revision Requested

Approved

Rejected

Archived

---

# NON FUNCTIONAL REQUIREMENT

Performance

Security

Scalability

Availability

Auditability

Maintainability

Extensibility

Responsive UI

---

# TECHNOLOGY STACK

Backend

Laravel 12

PHP 8.2

REST API

Laravel Sanctum

Spatie Permission

Redis

Queue

PostgreSQL

---

Frontend

Vue 3

Pinia

Vue Router

Axios

Chart.js

TailwindCSS

---

Infrastructure

Docker

Docker Compose

Nginx

Git

GitLab

GitLab CI

---

# CODING STANDARD

Follow

PSR-12

SOLID

DRY

KISS

YAGNI

Repository Pattern

Service Layer

Feature Based Architecture

Dependency Injection

No Fat Controller

No Fat Model

---

# PROJECT STRUCTURE

Backend

app/

Application/

Domain/

Infrastructure/

Repositories/

Services/

DTO/

Enums/

Events/

Listeners/

Jobs/

Policies/

Observers/

Traits/

Helpers/

---

Frontend

src/

pages/

layouts/

components/

stores/

router/

services/

composables/

assets/

utils/

types/

---

# DATABASE STANDARD

Use UUID

Soft Delete

Foreign Key

Composite Index

Constraint

Cascade Delete

Normalized

Third Normal Form

Timestamp

Audit Log

---

# DATABASE RULES

Never use integer id.

Always use UUID.

Every foreign key indexed.

Every searchable column indexed.

Large tables require composite indexes.

---

# TABLES

users

roles

permissions

projects

documents

project_statuses

approval_logs

comments

notifications

failed_jobs

jobs

cache

sessions

---

# API STANDARD

Version

/api/v1

Response

{
"success": true,
"message": "",
"data": {}
}

Errors

{
"success": false,
"message": "",
"errors": []
}

---

# AUTHENTICATION

Laravel Sanctum

Bearer Token

Policy

Middleware

Permission

---

# VALIDATION

Always use FormRequest.

Never validate inside Controller.

Always sanitize request.

---

# FILE UPLOAD

Allowed

PDF

DOC

DOCX

Maximum

10MB

Store

storage/app/private

Never public.

Generate unique filename.

---

# QUERY OPTIMIZATION

Always

Eager Loading

Cursor Pagination

Chunk

Lazy Collection

Cache

Avoid N+1 Query

Use select()

Use index

Use explain analyze

---

# CACHE

Dashboard

Master Data

Role

Permission

Statistics

TTL

300 seconds

---

# QUEUE

Upload Document

Email Notification

Approval Notification

Activity Log

---

# DASHBOARD

Applicant

Draft

Submitted

Revision

Approved

Rejected

Recent Activity

---

Reviewer

Pending Review

Approved

Rejected

Revision

Monthly Chart

Recent Review

---

# SECURITY

CSRF

XSS Protection

Rate Limiter

Authorization Policy

Permission Middleware

Validation

SQL Injection Prevention

Mass Assignment Protection

Encrypted Storage

Private File

---

# FRONTEND

Composition API

Pinia

Reusable Components

Responsive

Loading State

Error State

Pagination

Search

Filter

---

# UI DESIGN

Government Style

Minimal

Modern

Professional

Blue

White

Gray

Rounded Large

Shadow Soft

Accessible

---

# GIT

Branch

main

develop

feature/\*

hotfix/\*

release/\*

---

Commit

feat:

fix:

docs:

style:

refactor:

test:

perf:

build:

ci:

---

# README

Always generate

Installation

Docker

Migration

Seeder

API

Screenshots

Architecture

ERD

Deployment

Testing

---

# TESTING

Feature Test

Unit Test

Authentication Test

Permission Test

Approval Test

Upload Test

API Test

---

# PERFORMANCE TARGET

Dashboard

<300ms

API

<200ms

Pagination

Cursor

Million Rows Ready

---

# CODE QUALITY

Never duplicate code.

Always use dependency injection.

Always separate business logic.

Keep Controller under 100 lines.

Keep Service focused.

Keep Repository reusable.

Keep Vue component under 250 lines.

---

# OUTPUT RULES

Always explain before coding.

Generate one feature at a time.

Never generate entire project at once.

Always ask before moving to next feature.

Every feature must include

- Architecture
- Folder
- Database
- Backend
- Frontend
- API
- Validation
- Security
- Performance
- Testing

---

# PRD

Product Goal

Provide an enterprise-grade government document approval system.

KPIs

- Fast approval workflow
- Easy document tracking
- High performance
- Secure file storage
- Complete audit history
- Clean user experience

Success Metrics

Response time <200ms

Dashboard <300ms

100% API documented

100% Feature tested

No N+1 Query

100% PSR-12

---

# IMPORTANT

Never simplify architecture.

Always think like Senior Software Engineer.

Always optimize for enterprise applications.

Prefer maintainability over shortcuts.

Always explain design decisions.
