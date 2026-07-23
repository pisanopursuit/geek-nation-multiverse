# Geek Nation Multiverse — Brands + Import Center v3

This release fixes the MySQL 8 reserved-keyword failure in the Version 2 upgrade.

## Changes
- Renamed `import_items.row_number` to `import_row`.
- Added repair logic for incomplete or manually patched Version 2 installations.
- Kept the upgrade safe to run repeatedly.
- Added rollback for records created by an import batch.
- Updated batch reports to use the new column name.

## Install
Upload the files over the existing site, sign in as an administrator, and run:

`upgrade-brands-imports.php`

Then open **Admin → Import Center**.
