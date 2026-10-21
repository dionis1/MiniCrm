# MiniCRM — Laravel 13 API + Vue 3 SPA

A lightweight customer-relationship manager with a private, session-authenticated API and a reactive sales interface.

## Tech stack

- **PHP 8.4 / Laravel 13**, Eloquent, Form Requests, API Resources, Sanctum.
- **Vue 3 Composition API**, Vue Router 4, Pinia 3, Axios.
- **Tailwind CSS 4**, Vite 6, SortableJS (pipeline) and Chart.js.
- SQLite for simple development (configure MySQL or PostgreSQL if needed).
- PHPUnit feature tests, Laravel Pint, Vitest unit tests and Playwright browser smoke tests.

## Features

- Lead CRUD with ownership-scoped API queries, five-stage Kanban and drag-and-drop stage changes.
- Deal value, probability, expected revenue, version-aware updates and rollback of failed optimistic UI changes.
- One-time lead-to-customer conversion in a database transaction, creating a contact.
- Customer directory with server-side filtering, sorting and pagination, profile pages, tags and contacts.
- Activity timeline with manually logged calls, meetings, email interactions and notes.
- Follow-up tasks with due dates, priorities, filters (today, overdue, open, completed), editable forms and completion toggles.
- KPI dashboard, pipeline chart and global Ctrl+K quick search.
- Same-origin Sanctum session cookies, CSRF protection, constrained input validation, authenticated APIs and owner isolation.

**Scope notes:** Logged email activity is not integrated with an email provider. Analytics refresh when reloaded, not through WebSockets. The current UI displays up to 100 leads on the Kanban; server pagination is available for larger datasets. This is a development portfolio application, not an audited production service.

## Local development

Requirements: PHP 8.4 with SQLite extensions, Composer 2, Node.js 22 and npm.

```bash
git clone https://github.com/dionis1/MiniCrm.git
cd MiniCrm
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

For the SQLite database, leave `DB_DATABASE` unset in `.env` to use Laravel's `database/database.sqlite` default, or set it to an **absolute file path** if your environment requires it. **Never run `migrate:fresh` against a database containing real company or employee records.**

Open http://127.0.0.1:8000 and register your account. No public default account or password is seeded.

The frontend is delivered by Laravel on the same origin as the API. That is the supported development setup for cookie-based Sanctum auth. For interactive CSS/JS development, run `npm run dev` in a separate terminal.

## API endpoints

The API prefix is `/api/v1`. Authentication uses `/sanctum/csrf-cookie`, `POST /login`, `POST /register` and `POST /logout` with HTTP-only session cookies.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /api/v1/me | Current session user |
| GET/POST | /api/v1/leads | Paginate/filter or create leads |
| GET/PATCH/DELETE | /api/v1/leads/{id} | Detail, edit or delete |
| PATCH | /api/v1/leads/{id}/stage | Change stage using `version` |
| POST | /api/v1/leads/{id}/convert | Atomically convert using `version` |
| GET | /api/v1/leads/{id}/activities | Lead event history |
| GET/POST | /api/v1/customers | List/filter or create customers |
| GET/PATCH/DELETE | /api/v1/customers/{id} | Customer detail, edit, delete |
| GET/POST | /api/v1/customers/{id}/contacts | Customer contacts |
| PATCH/DELETE | /api/v1/contacts/{id} | Edit/remove contact |
| GET/POST | /api/v1/customers/{id}/activities | Activity feed / log activity |
| GET/POST | /api/v1/tasks | Task list/filter or create |
| PATCH/DELETE | /api/v1/tasks/{id} | Edit/remove task |
| PATCH | /api/v1/tasks/{id}/complete | Set `completed: true/false` |
| GET | /api/v1/dashboard/metrics | Private KPI data |
| GET | /api/v1/search?q= | Scoped quick search |

All business-data endpoints are scoped by the logged-in user's ID. A user cannot attach a task to another user's lead or customer. JSON validation errors use HTTP 422 and conflicting lead versions return HTTP 409.

## Quality checks

```bash
php artisan test
vendor/bin/pint --test
npm run test:unit
npm run build
npm run test:browser
```

Browser tests use a **disposable SQLite database on port 8010** in GitHub Actions. Do not point them at real data.

## Legacy data

The original Laravel 5.8 app had `companies` and `employees`. Their migration history is preserved, and a modern CRM migration adds nullable `user_id` ownership fields. **Pre-existing rows without an owner remain hidden**, rather than being silently assigned to the first person who registers.

Back up existing databases before migrating. Assign existing records to authorized users through a reviewed data migration or administrative process before expecting them in the CRM interface.

The old Bootstrap 4/Blade/Mix UI and the demo credentials were removed. Historical source remains available through Git history.

## Remaining limitations

- Full production hardening and security auditing remain outside the current development scope.
- Imported legacy ownership needs a deliberate migration strategy.
- Full keyboard drag-and-drop alternatives and deeper accessibility validation are still needed.
- Account recovery, email integrations and richer reporting are not yet implemented.
