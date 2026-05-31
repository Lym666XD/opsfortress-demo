# 2026-05-31 v0.5 Foundation Schema Changelog

## Purpose

This update adds a narrow v0.5 database foundation for the latest workbook/spec direction without implementing the PDF engine, offline sync runtime, or v0.5 importers.

## Migration Added

- `database/migrations/2026_05_31_000001_create_v0_5_foundation_tables.php`

## Schema Added

- `jurisdiction_regulatory_profiles`
- `workplace_external_parties`
- `asset_types`
- `assets`
- `workplace_asset_assignments`
- `worker_task_session_assets`
- `generated_documents`
- `document_delivery_events`

## Existing Tables Extended

- `prestart_questions.swms_version_id`
- `posttask_questions.swms_version_id`

The old task-level unique question indexes were split into:

- legacy task-level uniqueness when `swms_version_id IS NULL`
- version-aware uniqueness when `swms_version_id IS NOT NULL`

## Models Added

- `App\Domain\OpsFortress\Jurisdictions\Models\JurisdictionRegulatoryProfile`
- `App\Domain\OpsFortress\Workplaces\Models\WorkplaceExternalParty`
- `App\Domain\OpsFortress\Assets\Models\AssetType`
- `App\Domain\OpsFortress\Assets\Models\Asset`
- `App\Domain\OpsFortress\Assets\Models\WorkplaceAssetAssignment`
- `App\Domain\Whs\Runtime\Models\WorkerTaskSessionAsset`
- `App\Domain\Shared\Documents\Models\GeneratedDocument`
- `App\Domain\Shared\Documents\Models\DocumentDeliveryEvent`
- `App\Domain\Whs\Swms\Models\PosttaskQuestion`

## Tests Added/Updated

- Added `tests/Feature/Database/V05FoundationSchemaTest.php`.
- Updated `tests/Feature/Database/V03SchemaContractTest.php` so it no longer treats the new v0.5 `generated_documents` table as an obsolete legacy table.

## Documentation Updated

- `README.md`
- `MILESTONE.md`
- `IMPLEMENTATION_ROADMAP.md`
- `IMPORTER_INTAKE_NOTES.md`
- `docs/README.md`
- `docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml`
- `docs/V0_5_DB_DESIGN_REVIEW_2026_05_31.md`

## Boundaries

This update does not implement:

- PDF generation
- email sending
- offline sync
- Word Exchange transformation/import
- External Register import
- v0.5 workbook importers
- dashboard/corrective-action workflows

The new tables only reserve the stable relational foundation needed before those implementation slices are built.

## Verification

- `php artisan migrate:fresh --seed` passed against PostgreSQL.
- `php artisan migrate:rollback --step=1 && php artisan migrate` passed.
- `php artisan test` passed against PostgreSQL: 78 tests / 672 assertions / 8 skipped.
- `php vendor/bin/pint --test` passed.
- `php artisan route:list` passed with 36 routes.
