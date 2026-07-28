# Geek Nation Multiverse v10.9.1

Platform Settings database upgrade hotfix.

## Fixed

The `platform_settings.updated_by` column now uses `BIGINT UNSIGNED`, matching `users.id`. This resolves MySQL error 3780 when creating the foreign key.

## Install

Upload over Version 10.9, preserve `config/config.php` and `uploads/`, then revisit **Administration → Platform Settings**. The upgrade will run again automatically.
