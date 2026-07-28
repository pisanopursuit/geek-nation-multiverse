# Geek Nation Multiverse v10.9 — Platform Settings

Version 10.9 adds a centralized administration area for platform configuration.

## Installation

1. Upload this build over Version 10.8.1.
2. Preserve your existing `config/config.php` and `uploads/` directory.
3. Sign in as an administrator.
4. Open **Administration → Platform Settings**.
5. The first visit automatically opens `upgrade-platform-settings.php`; run it once.

## Settings included

- Site name, tagline, description, contact email, and footer credit
- Header logo, favicon, and default sharing-image paths
- Homepage announcement and featured-card limit
- Social profile links
- Default SEO title suffix and meta description
- Feature toggles for Universes, Booths, Events, Artist Alley, Academy, Collectors, Companies, and Brands
- Registration toggle
- Maintenance mode and public maintenance message

Feature toggles remove disabled modules from public and member navigation without deleting their data. Administrators retain access during maintenance mode.

No existing tables are changed. The upgrade creates only the `platform_settings` table.
