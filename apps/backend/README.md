<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## RAUW Backend API

This Laravel application exposes the backend API for RAUW. Local API development is documented in the repository-level [local development guide](../../docs/local-development.md).

## Backend Authentication

The backend uses bearer token authentication for API consumers. Officer registration and the shared login endpoint return Laravel Sanctum bearer tokens plus safe profile payloads. These endpoints are API-only and do not create session authentication.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/auth/register/user` | None | Register a user and return an immediately usable bearer token. |
| `POST` | `/api/auth/register/officer` | None | Register an officer and return an immediately usable bearer token. |
| `POST` | `/api/auth/login` | None | Authenticate a user, officer, or manager. |
| `GET` | `/api/auth/me` | `Authorization: Bearer <token>` | Return the current actor type and profile. |
| `PATCH` | `/api/auth/me` | `Authorization: Bearer <token>` | Update own username, email, and password; officers may also update badge number. Returns refreshed profile. Departments are not self-service on this path. |
| `PATCH` | `/api/auth/me/districts` | `Authorization: Bearer <token>` | Replace district assignments for the current active manager or active officer. Users receive `403`. |
| `POST` | `/api/auth/logout` | `Authorization: Bearer <token>` | Revoke the current bearer token. |

### Manual Token Flow

1. Send either a JSON officer registration request:

   ```bash
   curl -X POST http://127.0.0.1:8001/api/auth/register/officer \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"username":"new-officer","email":"new.officer@example.com","password":"password123","confirm_password":"password123","badge_number":"BOA-1234","department_ids":[1]}'
   ```

   Or send a JSON login request with `email` and `password` only:

   ```bash
   curl -X POST http://127.0.0.1:8001/api/auth/login \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"email":"demo.user@example.com","password":"password"}'
   ```

2. Copy the `access_token` from the successful response.
3. Send protected requests with `Authorization: Bearer <access_token>`:

   ```bash
   curl http://127.0.0.1:8001/api/auth/me \
     -H "Accept: application/json" \
     -H "Authorization: Bearer <access_token>"
   ```

4. Revoke the current token when finished:

   ```bash
   curl -X POST http://127.0.0.1:8001/api/auth/logout \
     -H "Accept: application/json" \
     -H "Authorization: Bearer <access_token>"
   ```

Successful registration and login responses include:

```json
{
  "token_type": "Bearer",
  "access_token": "<token>",
  "actor_type": "user",
  "profile": {
    "id": 1,
    "username": "demo.user",
    "email": "demo.user@example.com"
  }
}
```

Supported `actor_type` values are `user`, `officer`, and `manager`.

Officer registration always returns `actor_type: "officer"` and uses the same safe officer profile serializer as login, including fields such as `username`, `email`, `badge_number`, `departments`, and a `districts` array of compact district objects when assignments are loaded. `department_ids` is required during registration, must contain at least one existing department ID, and cannot contain duplicates. Optional `district_ids` may be supplied to attach one or more active districts through the `district_officer` pivot. Passwords and secrets are never returned. Registration validates `username` and `badge_number` uniqueness within the officers table and validates `email` uniqueness across users, officers, and managers.

Manager creation uses the `departments` table through `department_ids`. Each manager must have at least one valid department, assignments are stored in the `department_manager` pivot table, and manager auth profiles return a `departments` array of compact objects (`id`, `code`, `name`). Officers keep the same `department_ids` / `department_officer` behavior and auth profiles also return departments in a `departments` array.

Manager and officer district assignments are many-to-many. Managers use the `district_manager` pivot, officers use the `district_officer` pivot, and both actor profile types return `districts` arrays of compact objects (`id`, `name`, `postal_prefix`) instead of a singular actor-side `district_id` or `district` object. Active managers and active officers can replace their own district assignments with `PATCH /api/auth/me/districts` and a JSON body such as `{"district_ids":[1,2]}`; an empty array clears all assignments. Active managers can also replace any officer's assignments with `PATCH /api/officers/{officer}/districts` using the same request body. Users do not have district assignments and receive `403 Forbidden` for self-service district updates. Department assignments are not self-service; use manager-protected department assignment endpoints instead.

District records are managed through `/api/districts`. Authenticated actors can list and show districts. Only active managers can create, update, or delete districts. Deleting a district is blocked with `409 Conflict` while it is assigned to managers, assigned to officers, or referenced by issues.

Issue district handling is intentionally unchanged. `issues.district_id` remains a singular issue location/reference field and is not updated by actor district assignment endpoints or district CRUD.

Department deletion is blocked while a department is assigned to any manager or officer. Reassign those actors first; category pivot rows are still cleaned up automatically when an otherwise unused department is deleted.

Categories are readable by any authenticated actor (`GET /api/categories`, `GET /api/categories/{category}`). Create, update, disable, and hard delete require an authenticated active manager; users, officers, and inactive managers receive `403`.

Issues are listed and shown to any authenticated actor. Create, update, and delete require the authenticated active user who owns the issue (`issues.user_id`). Issue departments are derived server-side from the selected category and returned as a read-only `departments` array; clients must not send department values in create or update bodies. Issue `priority` is a nullable unsigned integer on the same scale as category `priority` (lower number = higher urgency). The server copies the main category's `priority` on create and whenever `category_id` changes; subcategory issues use the parent category's `priority`, not subcategory `weight`. Clients must not POST or PATCH `priority`.

Issue attachments may be uploaded or deleted only by the issue owner (active user). Downloads are allowed for the issue owner, any active officer, and any active manager; other authenticated users receive `403`. Files are served only through the authenticated download endpoint, not via public URLs.

Common auth status codes are:

- `201 Created` for successful officer registration.
- `200 OK` for successful login, profile read/update, district self-service, and logout requests.
- `403 Forbidden` when an authenticated actor is not permitted (for example, users on district self-service, inactive actors on profile PATCH, non-managers on category mutations, non-owners on issue writes).
- `401 Unauthorized` for invalid credentials, inactive or ambiguous accounts, missing tokens, invalid tokens, and revoked tokens.
- `422 Unprocessable Entity` when auth validation fails, including missing or invalid login fields, missing or invalid registration fields, password confirmation mismatch, invalid or missing officer or manager `department_ids`, or duplicate officer username/email/badge number.

For manual API testing, import the Postman collection and local environment from [`../../docs/postman`](../../docs/postman/README.md):

- [`rauw-backend.postman_collection.json`](../../docs/postman/rauw-backend.postman_collection.json)
- [`rauw-local.postman_environment.json`](../../docs/postman/rauw-local.postman_environment.json)

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
