# Geek Nation Multiverse v10.5 — Homepage Integration

## What changed

- Rebuilt `index.php` on the shared v10.4 component system.
- Preserved the established Comic-Con-inspired visual direction.
- Added a full discovery-first homepage with:
  - Hero, search, and account-aware calls to action
  - Platform highlights
  - Eight universe tiles
  - Artist Alley
  - Booths
  - Panels & Events
  - Multiverse Academy
  - Collectors Marketplace
  - Community activity
  - Featured companies
  - Featured brands
  - Stay Connected call to action
- Added `assets/homepage-v10.5.css` with isolated homepage styling.
- Added database-backed featured content with safe fallbacks when a module is empty or its schema is unavailable.
- No database upgrade is required.

## Installation

Upload the contents over v10.4. Keep the existing `config/config.php` and uploaded media.
