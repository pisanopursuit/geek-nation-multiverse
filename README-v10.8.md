# Geek Nation Multiverse 10.8 — Administration & Moderation Center

## Added
- Administration dashboard at `admin/index.php`
- Unified approval queue at `admin/approvals.php`
- Approval support for booths, artists, events, courses, collector profiles, collectibles, companies, brands, and universes
- Status filtering, content-type filtering, search, preview, manage, notes, and featured controls
- Administration navigation now opens the Administration Center
- Optional moderation audit history installer at `upgrade-moderation-center.php`

## Installation
Upload over Version 10.7 while preserving `config/config.php` and uploaded media. Approval controls work immediately with the existing database. Run `upgrade-moderation-center.php` once to enable the permanent moderation history table.
