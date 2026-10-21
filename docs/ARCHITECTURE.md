# Architecture decisions

## A Laravel API and a same-origin Vue SPA

Laravel serves the Vue shell and `/api/v1` from the same origin. Sanctum uses a session cookie, and browser mutations require CSRF protection. Pinia holds current session state; Axios does not store bearer tokens in localStorage. Vue Router gates screens for convenience; the server independently authorizes each resource. An expired session returns the UI to sign-in without retrying a write.

Each account owns its own CRM records. This is not a company/team tenancy model: users cannot invite colleagues or share records. Owner-scoped lookups return 404 for another user's IDs. Policies additionally protect invokable domain actions when they are called outside an HTTP controller. Contacts must belong to both the acting user and a customer owned by that user. Foreign task links are validated against the current owner.

## Explicit domain boundaries

Controllers validate transport input through Form Requests and return API Resources. Focused invokable actions perform business writes and enforce policies. `CrmRules` shares field validation between HTTP requests and direct domain calls. Backed enums represent lead stages, task priorities, and customer status. Low-level `RecordActivity` writes audit entries within the calling transaction; it is not an endpoint or an authorization boundary.

Creating/updating/deleting CRM data, managing contacts, and converting leads keep their business record and audit entry in the same database transaction. Tests inject persistence failures to prove rollback rather than merely asserting happy paths.

Lead conversion is the main concurrency example. A conditional database update claims the current `version` only if `converted_at` and `converted_company_id` are empty. The action then reads the claimed current model, creates a customer and contact, links the customer, and records the conversion. Any failure rolls back the claim and all writes. The durable conversion timestamp survives deletion of the customer, preventing another conversion. Conflicting versions return 409. Converted leads are read-only.

Lead updates use optimistic concurrency. Customer, contact, and task edits currently use ordinary writes; they do not claim the same lost-update protection. Task completion uses a conditional update and records an event only when its completion state actually changes, making repeated completion requests idempotent.

## Query and interface behavior

The pipeline searches and paginates on the server (25 leads per UI page, at most 100 per API request). An ID tiebreaker keeps ordering stable when timestamps match. Column totals describe the displayed page; dashboard metrics cover the owner's full dataset. Customers, contacts, and tasks are paginated. Timelines currently show the latest 100 events.

Indexes support ownership, sorting, stage/status/priority filters, contacts, and due-date ranges. Substring `LIKE` search is intended for small local datasets; a normal B-tree does not accelerate a leading-wildcard search. No full-text scalability claim is made. Development/test lazy loading is prohibited to catch missing eager loads. Customer detail uses the paginated contacts endpoint instead of eagerly loading every contact.

Vue request-generation guards ignore responses from older filters or disposed pages. Pipeline moves update optimistically, restore Vue's DOM ownership after SortableJS operations, and roll back failures. Conflicts refresh current server data. Native stage menus provide a keyboard alternative to dragging. Dialogs trap focus, close on Escape, and restore focus. Tests cover those interactions and narrow-screen overflow. Task timestamps travel as ISO UTC; local date inputs render the browser's wall time, including daylight-saving changes. Dashboard day filters use UTC.

## Local data and verification

Docker Compose runs one local app container. SQLite, uploaded storage, sessions, logs, and a generated local encryption key persist in a named volume. There is no automatic demo seeding. The optional seeder calls application actions, preserves existing demo edits and account passwords, and refuses production environments. Its two accounts demonstrate owner isolation. The seeder recognizes demo records by owner and stable email/title; deleting a demo record may recreate it on the next run.

PHP tests force an in-memory database and test-only settings, including when called from the Compose container. Browser tests start a separate disposable Docker container on port 8010 with no data-volume mount. Their fixtures never touch the personal Compose database. Test screenshots belong in test output, while README images use synthetic demo data.

The historical companies/employees migration chain is retained. Existing rows with no owner stay hidden. Assigning legacy ownership requires an explicit reviewed data migration. SQLite is the verified database; MySQL/PostgreSQL support has not been tested. Docker is for local use, and external mail delivery/public hosting are outside this repository's scope.
