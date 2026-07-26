# Geek Nation Multiverse — Import Center Guide

## Overview

There is currently **one** bulk-import feature in the platform: the **Admin Import Center**, at `admin/imports.php`. It imports **Companies** and **Brands** from a CSV or TSV file.

No other module — Universes, Booths/Marketplace, Events, Artist Alley, Academy, or Collector Marketplace — has a bulk-import feature. Those are all created one record at a time through their own `create.php` forms. If a bulk importer is ever added for one of them, add a section to this file for it.

## Requirements to use it

1. You must be signed in as an **admin** (`require_admin()`). There is no self-service import for brand/company owners.
2. The Brands + Import Center database tables must be installed. If they aren't, visiting `admin/imports.php` redirects you to `upgrade-brands-imports.php` — click **Install Upgrade** (safe to run more than once; it also repairs older v2 installs). Once installed, `admin/imports.php` is reachable directly, and is linked from the admin dashboard tile ("Imports — Platform data center") and from **Admin → Companies** / **Admin → Brands**.

## How to run an import

1. Go to **Admin → Import Center** (`admin/imports.php`).
2. Choose **Import type**: Companies or Brands.
3. Choose a **CSV or TSV file** and click **Preview Import**.
   - The file is parsed but nothing is written to the database yet.
   - Max **5,000 data rows** per file. Fully blank rows are skipped automatically.
4. Review the **preview**: it shows every parsed column header and the first 10 rows, so you can catch a bad header mapping before committing.
5. Choose how to handle **duplicates**:
   - **Skip existing records** — leave the existing row untouched, mark it `skipped` in the report.
   - **Update existing records** — overwrite the existing row's fields with the file's values.
6. Click **Run Import**. Every row is processed and classified as `imported`, `updated`, `skipped`, or `error`, and a batch report is saved permanently.
7. All new records land with **status = Draft**, whether skipped/updated or not — they still need to go through the normal approval flow on **Admin → Companies** / **Admin → Brands**.

You can click **Cancel** on the preview screen at any point before Run Import — nothing has been saved yet.

## File format rules

- Extension must be `.csv` or `.tsv`.
- Delimiter is auto-detected per file (tab vs. comma) by comparing counts on the header line, so either format works without choosing one explicitly.
- A UTF-8 BOM on the header line is stripped automatically.
- **Column headers are normalized**: lowercased, non-alphanumeric characters collapsed to `_`, leading/trailing `_` trimmed. E.g. `Public E-Mail` → `public_e_mail`. **A header that doesn't normalize to one of the expected field names below (or one of its aliases) is silently ignored** — the importer does not error on unknown/misspelled columns, it just treats that field as absent. Double-check your header spelling against the templates.
- Template files with the exact expected headers are provided in `import-templates/`:
  - `import-templates/companies-template.csv`
  - `import-templates/brands-template.csv`

### Companies — expected columns

| Column | Required? | Notes |
|---|---|---|
| `name` | **Required** | Alias: `company_name`. Missing → row errors ("Missing company name"). |
| `short_description` | optional | |
| `description` | optional | |
| `website` | optional | |
| `public_email` | optional | Alias: `email` |
| `location` | optional | |
| `category` | optional | |
| `founded_year` | optional | Non-numeric/zero is stored as empty |

Dedup key: case-insensitive exact match on `companies.name`.

### Brands — expected columns

| Column | Required? | Notes |
|---|---|---|
| `company_name` | **Required** | Alias: `parent_company`. Must match an existing company by name (status `approved` or `draft`), case-insensitive. No match → row errors ("Parent company not found: …"). |
| `name` | **Required** | Alias: `brand_name`. The brand's own name. |
| `short_description` | optional | |
| `description` | optional | |
| `website` | optional | |
| `public_email` | optional | Alias: `email` |
| `category` | optional | |
| `founded_year` | optional | Non-numeric/zero is stored as empty |

Dedup key: brand name (case-insensitive), scoped to the resolved parent company.

## Import history and rollback

- The bottom of the Import Center page lists the last 25 batches (date, type, filename, status, results). Click a filename to open its full row-by-row report (row number, action, source value, any error/skip message).
- **Rollback** ("Rollback Imported Records" button on a batch report) deletes only the records **this batch created** (`action = imported`). It does **not** undo rows the batch **updated** — those stay changed. This is a one-way, asymmetric undo: use "Skip existing records" mode instead of "Update" if you want a fully rollback-safe import.
- A batch can only be rolled back once; a second attempt is rejected.

## Where this lives in the code

| Concern | File |
|---|---|
| Upload / preview / run / rollback / history UI + logic | `admin/imports.php` |
| One-time DB installer | `upgrade-brands-imports.php` (runs `database/brands-imports.sql`) |
| Import tables schema (`import_batches`, `import_items`, plus `brands`/`brand_members`/etc.) | `database/brands-imports.sql` |
| Shared CSV/TSV parser (`csv_rows()`) | `includes/functions.php` |
| Feature-installed check (`brands_schema_ready()`) | `includes/functions.php` |
| Sample files | `import-templates/companies-template.csv`, `import-templates/brands-template.csv` |
