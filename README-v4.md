# Geek Nation Multiverse — Version 4 Universe Engine

Version 4 adds a user-centered Universe Engine on top of the existing identity, company, brand, and import systems.

## Installation

1. Upload the contents over the current website files.
2. Sign in as an administrator.
3. Open `upgrade-universes.php`.
4. Select **Install Upgrade**.
5. Open **Universes** from the main navigation.

The upgrade is repeatable and preserves the favorite-universe selections already stored in `user_universes`.

## Features

- Unlimited nested universe hierarchy using `parent_id`
- Root universes for Comics, Fantasy, Sci-Fi, Gaming, Anime & Manga, Tabletop, Horror, and Cosplay
- Existing universes automatically placed under appropriate roots where possible
- Public universe directory and profile pages
- Join and leave universe memberships
- Member counts and newest-member displays
- Breadcrumb navigation through nested worlds
- Admin universe creation and editing
- Universe logo and banner uploads
- Draft, pending, approved, and suspended statuses
- Featured and active controls
- Universe moderator-ready database structure
- Activity history for joins and departures

## Shell + Skin Theme Engine

The site shell remains consistent: navigation, grids, cards, buttons, spacing, and accessibility behavior. Each universe stores its own skin tokens:

- Primary, secondary, and accent colors
- Background, surface, and text colors
- Display and body-font descriptions
- Texture language
- Iconography style
- Imagery treatment

Universe pages apply these values through scoped CSS custom properties without changing the shared application shell.
