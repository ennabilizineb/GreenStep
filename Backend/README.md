# GreenStep — Backend API

PHP **Slim 4** RESTful API for GreenStep (Personal Carbon Footprint & Eco Lifestyle Tracker).
Stack: PHP 8.1+ · Slim 4 · PDO/MySQL · Firebase JWT.

> Maintained by **Member 2 — Backend & API Lead**. Database schema & security policy are owned by **Member 3 — Database & Security Lead**.

## Project structure

```
backend/
├── public/
│   └── index.php            # single entry point (front controller)
├── config/
│   └── settings.php         # env-driven config (db, jwt)
├── src/
│   ├── Database/
│   │   └── Database.php      # hardened PDO connection factory
│   ├── Middleware/
│   │   ├── CorsMiddleware.php
│   │   └── JwtAuthMiddleware.php   # verifies JWT + role gate
│   ├── Support/
│   │   ├── JsonResponse.php        # uniform success/error envelopes
│   │   └── JsonErrorHandler.php    # exceptions -> JSON, never HTML
│   ├── Controllers/
│   │   ├── AuthController.php       # register + login (implemented)
│   │   ├── LogController.php        # activity logs (CRUD #1)
│   │   ├── ChallengeController.php  # challenges (CRUD #2) + join
│   │   ├── TipController.php        # daily eco-tip
│   │   └── AdminController.php      # tips + emission factors (admin)
│   └── Routes/
│       └── Api.php          # the API contract, expressed in code
├── sql/
│   └── schema.sql           # minimal starter schema (Rawan owns final)
├── .env.example
├── .gitignore
└── composer.json
```

## Setup

```bash
cd backend
composer install                 # restore dependencies into vendor/
cp .env.example .env             # then edit .env with your DB + JWT secret
php -r "echo bin2hex(random_bytes(32));"   # generate a JWT_SECRET, paste into .env
mysql -u root -p < sql/schema.sql          # create DB + tables (dev only)
composer start                   # serve at http://localhost:8080
```

Verify it's alive:

```bash
curl http://localhost:8080/api/health
# {"success":true,"data":"ok"}
```

## Response envelope

Every endpoint returns the same shape:

```json
{ "success": true,  "data": { ... } }
{ "success": false, "error": { "message": "...", "details": "..." } }
```

## API contract

| Method | Route | Body | Access | Success |
|---|---|---|---|---|
| POST | `/api/auth/register` | `{ name, email, password }` | Public | 201 |
| POST | `/api/auth/login` | `{ email, password }` | Public | 200 |
| GET | `/api/logs` | — | User | 200 |
| POST | `/api/logs` | `{ activity_type_id, amount, logged_on }` | User | 201 |
| PUT | `/api/logs/{id}` | `{ activity_type_id, amount, logged_on }` | User | 200 |
| DELETE | `/api/logs/{id}` | — | User | 200 |
| GET | `/api/dashboard` | — | User | 200 |
| GET | `/api/tips/daily` | — | User | 200 |
| GET | `/api/challenges` | — | User | 200 |
| POST | `/api/challenges/{id}/join` | — | User | 201 |
| POST | `/api/challenges` | `{ name, description, start_date, end_date, target_co2_reduction }` | Leader | 201 |
| PUT | `/api/challenges/{id}` | `{ name, description, target_co2_reduction }` | Leader | 200 |
| DELETE | `/api/challenges/{id}` | — | Leader | 200 |
| POST | `/api/admin/tips` | `{ title, body, category }` | Admin | 201 |
| PUT | `/api/admin/factors/{id}` | `{ kg_co2_per_unit }` | Admin | 200 |

Protected routes require an `Authorization: Bearer <token>` header.

## Status (Phase 1)

✅ Bootable Slim 4 skeleton, full routing, JWT + CORS middleware, central JSON error handling.
✅ Auth (register/login) implemented end-to-end — the PR2 demonstrable feature.
🔜 CRUD update/delete, dashboard aggregation + carbon calc, challenge join (Phases 3–4 per Gantt).
