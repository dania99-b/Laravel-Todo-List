# Todo List Backend API

A production-oriented REST API for a per-user Todo list, built with **Laravel 12** and **Laravel Sanctum**, organized as a **modular monolith with microservice-oriented boundaries**.

Backend only — no frontend, Blade views, or UI.

---

## Architecture

### Modular monolith with microservice boundaries

The application ships as one deployable Laravel application, but the code is organized into two self-contained modules that each own a clear business capability:

| Module | Responsibility |
|---|---|
| `Auth` (`app/Modules/Auth`) | Registration, login/logout, token issuance and revocation, profile lookup, credential verification |
| `Todo` (`app/Modules/Todo`) | CRUD, status transitions, filtering, searching, sorting, pagination, ownership rules |

`routes/api.php` is the **API gateway**: it is the single external entry point and contains routing, authentication middleware and rate limiting only — no business logic.

This is intentionally a **modular monolith, not distributed microservices**. True microservices would require service discovery, inter-service auth (JWT propagation or mTLS), per-service databases and migrations, distributed transactions/sagas, independent CI/CD, and observability infrastructure — disproportionate for this scope. The boundaries are drawn so each module can be extracted later (see *Extracting a microservice* below).

### Layers inside each module

```
Routes            api.php            endpoint definitions + middleware only
Controllers       Http/Controllers   request/response coordination
Form Requests     Http/Requests      validation + per-request authorization
DTOs              DTO/               typed data crossing layer boundaries
Services          Services/          application use cases / business workflows
Repositories      Repositories/      data-access contract + Eloquent implementation
Models            Models/            Eloquent entities
Policies          Policies/          ownership authorization
Resources         Http/Resources     JSON serialization
Enums             Enums/             status / priority value objects
```

### SOLID in practice

- **SRP** — controllers coordinate HTTP only; `AuthService` handles auth workflows; `TokenService` handles token mechanics; `EloquentTodoRepository` handles persistence.
- **OCP** — filtering/sorting extensibility comes from the whitelisted `TodoFilters` DTO; new list options are added without touching the service workflow.
- **LSP** — `TodoService` works with `TodoRepositoryInterface`; any implementation honoring the contract is substitutable.
- **ISP** — the repository contract exposes only the five operations the service needs; no fat interface.
- **DIP** — `TodoService` depends on `TodoRepositoryInterface` (bound to `EloquentTodoRepository` in `RepositoryServiceProvider`), not on Eloquent. Auth depends on `TokenService` for token mechanics rather than reaching into Sanctum from the controller.

Abstractions are deliberately kept minimal: one repository interface where DIP has real payoff (persistence swap on extraction), no speculative factories/adapters.

## Folder structure

```
app/
├── Models/User.php
├── Modules/
│   ├── Auth/
│   │   ├── DTO/            Credentials, RegisterUserData
│   │   ├── Http/{Controllers,Requests}
│   │   └── Services/       AuthService, TokenService
│   └── Todo/
│       ├── DTO/            TodoData, TodoFilters
│       ├── Enums/          TodoStatus, TodoPriority
│       ├── Http/{Controllers,Requests,Resources}
│       ├── Models/Todo.php
│       ├── Policies/TodoPolicy.php
│       ├── Repositories/   TodoRepositoryInterface + EloquentTodoRepository
│       └── Services/TodoService.php
├── Providers/              AppServiceProvider (rate limiter), RepositoryServiceProvider (DI)
database/{migrations,factories}
routes/api.php
docs/openapi.yaml
tests/Feature/{AuthTest,TodoTest}.php
```

## Prerequisites

- PHP ≥ 8.2 with `mbstring`, `xml`, `sqlite3`, `curl`, `zip` extensions
- Composer 2.x
- SQLite (default) or MySQL/PostgreSQL

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # SQLite default; or configure MySQL in .env
php artisan migrate
php artisan serve                # http://localhost:8000
```

Key `.env` values: `DB_CONNECTION`, `DB_DATABASE` (or `DB_URL` for MySQL), `APP_ENV`, `APP_DEBUG` (must be `false` in production), `SANCTUM_STATEFUL_DOMAINS` if SPA cookies are ever needed (not required for bearer tokens).

## Running tests

```bash
php artisan test          # 25 feature tests, sqlite :memory:
```

## Authentication

Bearer-token auth via Sanctum.

```bash
# Register → returns token
curl -X POST localhost:8000/api/v1/auth/register -H 'Accept: application/json' \
  -d 'name=Rama&email=rama@example.com&password=password123&password_confirmation=password123'

# Use the token on every subsequent request
curl localhost:8000/api/v1/todos -H "Authorization: Bearer <token>" -H 'Accept: application/json'
```

`POST /auth/login` and `POST /auth/register` are rate-limited to **5 requests/minute** per email/IP. `POST /auth/logout` revokes only the current token. Passwords are bcrypt-hashed via the model's `hashed` cast; tokens and hashes are never returned in responses.

## API endpoints

Base path: `/api/v1`. All requests should send `Accept: application/json`.

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/auth/register` | — | Register, returns `{user, token}` (201) |
| POST | `/auth/login` | — | Login, returns `{user, token}` (200) |
| POST | `/auth/logout` | ✓ | Revoke current token (204) |
| GET | `/auth/me` | ✓ | Current user profile |
| GET | `/todos` | ✓ | List own todos (paginated) |
| POST | `/todos` | ✓ | Create todo (201) |
| GET | `/todos/{id}` | ✓ | Show todo |
| PUT | `/todos/{id}` | ✓ | Replace todo (all fields) |
| PATCH | `/todos/{id}/status` | ✓ | Update status only |
| DELETE | `/todos/{id}` | ✓ | Delete todo (204) |

### List query parameters

`status` (`pending|completed`), `priority` (`low|medium|high`), `search` (title+description substring), `due_date_from`, `due_date_to` (dates), `sort_by` (whitelist: `title, status, priority, due_date, created_at, updated_at`), `sort_direction` (`asc|desc`), `per_page` (1–100, default 15).

### Examples

Create:
```bash
curl -X POST localhost:8000/api/v1/todos -H "Authorization: Bearer <token>" \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"title":"Buy milk","priority":"high","due_date":"2026-12-01 10:00:00"}'
```
```json
{
  "data": {
    "id": 1, "title": "Buy milk", "description": null,
    "status": "pending", "priority": "high",
    "due_date": "2026-12-01T10:00:00+00:00",
    "created_at": "2026-10-21T07:00:00+00:00", "updated_at": "2026-10-21T07:00:00+00:00"
  }
}
```

List (filtered/sorted):
```
GET /api/v1/todos?status=pending&priority=high&sort_by=due_date&sort_direction=asc&per_page=20
```
```json
{
  "data": [ { "id": 1, "...": "..." } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "per_page": 20, "total": 1, "...": "..." }
}
```

### Errors

Standard Laravel JSON errors: `422` `{ "message": "...", "errors": {"field": ["..."] } }` for validation, `401` unauthenticated, `403` for accessing another user's todo, `404` unknown id, `429` rate limit, `500` without stack traces when `APP_DEBUG=false`.

## Security notes

- Ownership enforced twice: routes bind the todo then `TodoPolicy` authorizes (`403` for another user's resource); list/show queries are also scoped by `user_id`.
- Mass assignment guarded by `$fillable`; `user_id` is set server-side only.
- Sort columns whitelisted via `TodoFilters::SORTABLE`; `per_page` capped at 100.
- SQL injection prevented by Eloquent parameter binding; enum validation for status/priority.
- Rate limiting on credential endpoints.

## Extracting a module into a standalone microservice

1. **Copy the module** — `app/Modules/Todo` plus its migration and `TodoFactory` move into a new Laravel app nearly unchanged. Replace the `App\Models\User` reference with a `user_id` claim from an issued token (validated via shared JWKS/introspection against the Auth service).
2. **Swap the repository** — `TodoService` already depends on `TodoRepositoryInterface`, so only the infrastructure implementation changes if storage moves to a separate database (its own `todos` table owned solely by the Todo service).
3. **Auth propagation** — the Auth service remains the token issuer; the Todo service verifies Sanctum-compatible tokens or a JWT issued by Auth (`/auth/me` becomes a service-to-service validation call).
4. **Gateway** — keep `/api/v1/...` routes as the public entry point; they can proxy to the extracted service later without changing clients.
5. **What NOT to share** — no shared database between services; sync data via events/API calls only.

## API specification

An OpenAPI 3 description of all endpoints, parameters, and schemas is in [`docs/openapi.yaml`](docs/openapi.yaml). Import it into Swagger UI / Postman / Insomnia.
