# Database schema

Entity-relationship overview for the RAUW (WIJK) domain model. Laravel framework tables (`cache`, `jobs`, `sessions`, `password_reset_tokens`, `personal_access_tokens`) are included in migrations and in the DBML source but are omitted from the diagram below.

## ERD diagram

![RAUW domain ERD](../assets/TLE4%20ballenbak%20(1).svg)

## DBML source

The editable schema definition for [dbdiagram.io](https://dbdiagram.io) lives in [`DBML.txt`](DBML.txt). Table and column notes are in Dutch where they match product terminology.

When the schema changes:

1. Update migrations in `apps/backend/database/migrations/`.
2. Keep `DBML.txt` in sync for documentation and diagram tooling.
3. Regenerate or replace the ERD SVG in [`assets/`](../assets/) if the visual diagram is updated.

## Related documentation

| Document | Purpose |
|----------|---------|
| [`DBML.txt`](DBML.txt) | Canonical DBML (dbdiagram.io) |
| [API guides](api/README.md) | Domain behaviour tied to tables |
| [OpenAPI schemas](openapi/schemas.yaml) | API request/response models |
| [Backend app README](../apps/backend/README.md) | Migrations, seeders, local DB setup |
