# Geek Nation Multiverse

The public Geek Nation Multiverse platform and its first installable authentication framework.

## Included
- Installer configured for the Geek Nation Multiverse IONOS database
- Public registration with email verification
- Login using either username or email
- SMTP through IONOS on port 587 using STARTTLS
- Password reset
- CSRF protection, prepared statements, secure password hashing, session regeneration, and login throttling
- Administrator approval for company and brand management access
- Existing static homepage retained

## Install
1. Upload all files to the document root for `geeknationmultiverse.com`.
2. Visit `https://geeknationmultiverse.com/install.php`.
3. Enter the database password, administrator password, and SMTP mailbox password.
4. Delete or rename `install.php` after confirming installation. The installer also creates `config/installed.lock`.

Do not commit `config/config.php` or `config/installed.lock`.

## Authors / Created By
Marc Delsoin, Abdoul Ba, Trevor Rukwava, & Sean Pisano

## User Identity Update

This build adds the complete User Identity MVP:

- Multi-step onboarding
- Public, member-only, and private profiles
- Avatar and banner uploads
- Identity types, interests, and favorite universes
- Profile completion tracking
- Profile editing and social links
- Notification preferences
- Administrator management for identity options

### Updating an Existing Installation

1. Upload the updated files over the existing application.
2. Keep your existing `config/config.php` and `config/installed.lock` files.
3. Sign in as the administrator.
4. Open `/upgrade.php` and click **Run Upgrade**.
5. Complete the onboarding flow.

The upgrade is additive and does not remove existing users or authentication data.

### New Installation

Run `/install.php`. The main schema now includes all User Identity tables and starter options.

### Upload Permissions

The following folders must be writable by PHP:

- `uploads/avatars/`
- `uploads/banners/`

Images are restricted to JPG, PNG, WEBP, and GIF files with a maximum size of 5 MB. Executable files are blocked in the upload directory.

## User and Administrator Invitations

After uploading the updated files, visit `/upgrade.php` as an administrator and run the upgrade. Then open `/admin/invitations.php`.

Administrators can invite either:
- **Normal User / Member** — creates an active `fan` account.
- **Administrator** — creates an active `admin` account with full administration access.

Invitation links are single-use, stored as SHA-256 hashes, and expire after seven days. Accepting an invitation verifies the invited email and routes the new account into User Identity onboarding.
