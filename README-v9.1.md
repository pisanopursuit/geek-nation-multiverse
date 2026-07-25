# Geek Nation Multiverse v9.1

Collector Marketplace installer compatibility fix.

## Fix
- Collector user foreign-key columns now use `BIGINT UNSIGNED`, matching `users.id`.
- Corrects MySQL 8 error 3780 during installation.

## Install
Upload the Website folder, sign in as an administrator, and run `upgrade-collector-marketplace.php` again.
