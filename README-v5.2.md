# Geek Nation Multiverse Version 5.2 — Developer Center

Version 5.2 adds a permanent administrator-only testing and diagnostics subsystem.

## Installation

1. Upload the Version 5.2 files over Version 5.1.1.
2. Sign in as an administrator.
3. Open `upgrade-developer-center.php`.
4. Select **Install Version 5.2**.
5. Open **Admin → Developer Center**.

## Complete Test Environment

A generated batch creates connected records for:

- users and profiles
- companies and brands
- hierarchical universes and memberships
- billboard posts, replies, chat, and activity
- booths, teams, galleries, downloads, policies, and views
- physical, digital, and service products
- pending, confirmed, processing, shipped, completed, and cancelled orders

Demo accounts use addresses ending in `@example.test` and the shared password shown after generation. No live emails or payments are triggered.

## Safe cleanup

Every generated record is stored in `developer_demo_records` with its batch identifier and cleanup order. Cleaning a batch removes only records tracked for that batch. Existing real records are not selected by name, date, or content.

## Diagnostics

The Developer Center checks that the tables required by each completed module exist and that the uploads directory is writable.


## Version 5.2.1 fix

- Corrected the Developer Center post-action redirect to `/admin/developer-center.php`.
- Added a visible list of generated demo usernames, emails, roles, and the shared password after test-environment creation.
