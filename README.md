# Doctor appointment API

Laravel REST API take-home for a job application: doctors publish availability, patients book free slots, appointments move through a status lifecycle. **Backend only** — no Vue / SPA.

Built against a written brief (entities, overlap rules, free-slot listing, confirm / complete / cancel with a 24h rule on confirmed → cancelled).

## Stack

- PHP 8.5 · Laravel 13
- MySQL (Docker)
- Pest feature tests · Laravel Pint · Larastan
- OpenAPI via Scramble · Swagger UI in Docker

## Features

- Doctors & patients CRUD
- Doctor availabilities (`starts_at` / `ends_at`, slot duration)
- Free-slot listing (filter by doctor / date range, pagination)
- Appointments: create → `pending` → `confirmed` → `completed`, or `cancelled`
- Business rules: no overlapping availabilities, future-only booking, patient cannot double-book at the same time, invalid status transitions rejected

## API (prefix `/api/v1`)

| Area | Endpoints |
| --- | --- |
| Doctors / patients | `apiResource` CRUD |
| Availabilities | `GET/POST /doctors/{doctor}/availabilities` |
| Free slots | `GET /free-slots` |
| Appointments | `GET/POST /appointments` |
| Status | `POST .../confirm` · `complete` · `cancel` |

Interactive docs: Swagger UI after `docker compose up` (see below).

## Run

```bash
git clone https://github.com/milan-engelsz/doctor-appointment-api.git
cd doctor-appointment-api
docker compose up
```

| Service | URL |
| --- | --- |
| API | http://localhost:8080/api/v1 |
| Swagger UI | http://localhost:8082 |
| phpMyAdmin | http://localhost:8081 |

On start the app container runs `composer install`, creates `.env`, generates the key, then `migrate:refresh --seed` (DB is reset every container start).

## Tests / CI

```bash
docker compose exec app php artisan test
docker compose exec app composer check
```

`composer check` runs Pint (`lint:check`), Larastan (`analyse`), and the test suite.

GitHub Actions runs tests on push / PR (see `.github/workflows/tests.yml`).

## Note

Hiring take-home — not a production product. Brief targeted PHP 8.3+ / Laravel 11+; this repo ships on PHP 8.5 / Laravel 13.
