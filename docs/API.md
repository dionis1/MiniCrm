# MiniCRM API contract

## Authentication

The supported client is the Vue SPA served by Laravel on the same origin. Request `/sanctum/csrf-cookie`, then post `{ "email": "...", "password": "..." }` to `/login` with credentials and the `X-XSRF-TOKEN` cookie header. Axios is configured to do this. Cookies are HTTP-only for session authentication and SameSite=Lax. Laravel 13 uses origin-aware request-forgery protection with token validation as a fallback; cross-site writes without a token are rejected.

Registration: `POST /register` with `name`, `email`, `password`, `password_confirmation` (minimum 12 characters). Logout: `POST /logout`. Recovery: `POST /forgot-password` with `email`; its response is generic for existing/missing users. Reset: `POST /reset-password` with `email`, `token`, `password`, `password_confirmation`. Tokens expire after 60 minutes and can be used once. Local mail is written to the Laravel log.

Business endpoints require `auth:sanctum`. Every record belongs to its current owner; there are no shared-team roles. The API limiter permits 180 requests per minute per user. Login permits 10 attempts per minute per IP, registration/recovery 5. No bearer-token issuance UI is included.

## Resource endpoints

All paths below start with `/api/v1`. Bodies and responses are JSON; send `Accept: application/json`.

| Method | Path | Behavior |
| --- | --- | --- |
| GET | `/me` | Session user |
| GET, POST | `/leads` | Paginated list; create |
| GET, PATCH, DELETE | `/leads/{id}` | Read, update, delete |
| PATCH | `/leads/{id}/stage` | Update stage and version |
| POST | `/leads/{id}/convert` | Create customer/contact exactly once |
| GET | `/leads/{id}/activities` | Latest 100 events |
| GET, POST | `/customers` | Paginated list; create |
| GET, PATCH, DELETE | `/customers/{id}` | Read, update, delete |
| GET, POST | `/customers/{id}/contacts` | Paginated contacts; add |
| PATCH, DELETE | `/contacts/{id}` | Update, delete |
| GET, POST | `/customers/{id}/activities` | Latest 100 events; log interaction |
| GET, POST | `/tasks` | Paginated list; create |
| GET, PATCH, DELETE | `/tasks/{id}` | Read, update, delete |
| PATCH | `/tasks/{id}/complete` | Set `completed: true/false` idempotently |
| GET | `/dashboard/metrics` | Owner-scoped aggregate metrics |
| GET | `/search?q=term` | Up to five results each for leads/customers/tasks |

Customer detail does not embed an unbounded contacts list; use its contacts endpoint. Deleting a customer cascades linked contacts and clears its task/lead foreign keys. The lead's permanent conversion marker survives.

## Filters and pagination

- Leads: `q` (100 characters), `stage`, `sort=created_at|name|deal_value`, `direction=asc|desc`, `per_page=1..100` (default 20), `page>=1`.
- Customers: `q` (100 characters), `tag`, `status=active|inactive`, `sort=name|created_at`, `direction=asc|desc`, `page>=1`. Page size is 20.
- Tasks: `filter=all|today|overdue|open|completed`, `priority=low|normal|high`, `page>=1`. Page size is 20. Today is a UTC day; overdue is before the current instant.
- Contacts: `page`; page size is 20.
- Quick search: `q` (2–80 characters).

Resource lists return `{ "data": [...], "links": {...}, "meta": {...} }`. Resource reads/writes wrap the object in `data`; successful creates return 201, deletes 204. Timelines and quick search return a bounded `data` array/object instead of pagination metadata.

## Lead write example

Create a lead:

```json
{
  "name": "Maya Chen",
  "company_name": "Northstar Studio",
  "email": "maya@example.test",
  "stage": "new",
  "deal_value": 12500.50,
  "probability": 35
}
```

Stages are `new`, `contacted`, `proposal`, `closed_won`, `closed_lost`. The response includes `version` (initially 0), `expected_value`, `converted`, and `converted_customer_id`. Subsequent PATCH requests and conversion POSTs must include the returned integer `version`:

```json
{ "stage": "proposal", "version": 0 }
```

An accepted update increments the version. A stale version, a repeated conversion, or editing a converted lead returns 409 without partially writing records. Conversion accepts `{ "version": 1 }` and returns the new customer's `id` and `name`. A lead can be converted from any unconverted stage; conversion sets `closed_won`. There is no forced sequential sales-stage transition rule.

Task writes accept `title`, `description`, `priority`, ISO `due_at`, `lead_id`, `company_id`. Task responses call the company field `customer_id`. Related IDs must belong to the same owner. Customer/contact tags contain up to 20 distinct strings. Activity inputs allow `call`, `note`, `email`, `meeting`; an email entry is a recorded interaction, not outbound mail.

## Errors

| Status | Meaning |
| --- | --- |
| 401 | Authentication required/session expired |
| 403 | Policy refusal |
| 404 | Missing or inaccessible owner-scoped record |
| 409 | Lead changed elsewhere or already converted |
| 419 | Request-forgery validation failed |
| 422 | Invalid fields/filters, invalid credentials/reset token |
| 429 | Request limit exceeded |

Laravel validation errors use `{"message":"...","errors":{"field":["..."]}}`. Other errors provide `message`. The frontend displays validation feedback, restores failed optimistic moves, and redirects expired sessions to sign-in. It does not automatically retry mutations.
