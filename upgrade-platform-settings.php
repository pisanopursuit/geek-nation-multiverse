<?php
require __DIR__ . '/includes/bootstrap.php';
require_admin();

try {
    db()->exec("CREATE TABLE IF NOT EXISTS platform_settings (
        setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
        setting_value TEXT NULL,
        setting_group VARCHAR(60) NOT NULL DEFAULT 'general',
        is_public TINYINT(1) NOT NULL DEFAULT 0,
        updated_by INT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_platform_settings_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_platform_settings_group (setting_group)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $defaults = [
        ['site_name', (string)config('app.name', 'Geek Nation Multiverse'), 'general', 1],
        ['site_tagline', 'Every story. Every fan. One place.', 'general', 1],
        ['site_description', 'The permanent online convention for fans, creators, collectors, educators, artists, and vendors.', 'general', 1],
        ['contact_email', '', 'general', 1],
        ['footer_credit', 'Created by Marc Delsoin, Abdoul Ba, Trevor Rukwava, & Sean Pisano.', 'general', 1],
        ['logo_path', 'assets/geek-nation-multiverse-logo.png', 'branding', 1],
        ['favicon_path', '', 'branding', 1],
        ['default_share_image', 'assets/geek-nation-multiverse-logo.png', 'branding', 1],
        ['facebook_url', '', 'social', 1],
        ['instagram_url', '', 'social', 1],
        ['youtube_url', '', 'social', 1],
        ['tiktok_url', '', 'social', 1],
        ['discord_url', '', 'social', 1],
        ['x_url', '', 'social', 1],
        ['seo_title_suffix', 'Geek Nation Multiverse', 'seo', 1],
        ['seo_default_description', 'Explore universes, artists, booths, events, courses, and collectibles across Geek Nation Multiverse.', 'seo', 1],
        ['feature_universes', '1', 'features', 0],
        ['feature_booths', '1', 'features', 0],
        ['feature_events', '1', 'features', 0],
        ['feature_artists', '1', 'features', 0],
        ['feature_academy', '1', 'features', 0],
        ['feature_collectors', '1', 'features', 0],
        ['feature_companies', '1', 'features', 0],
        ['feature_brands', '1', 'features', 0],
        ['registration_enabled', '1', 'access', 0],
        ['maintenance_mode', '0', 'access', 0],
        ['maintenance_message', 'Geek Nation Multiverse is receiving an upgrade. Please check back shortly.', 'access', 1],
        ['homepage_announcement', '', 'homepage', 1],
        ['homepage_announcement_url', '', 'homepage', 1],
        ['homepage_featured_limit', '4', 'homepage', 0],
    ];
    $stmt = db()->prepare('INSERT IGNORE INTO platform_settings(setting_key,setting_value,setting_group,is_public,updated_by) VALUES(?,?,?,?,?)');
    foreach ($defaults as $row) $stmt->execute([$row[0], $row[1], $row[2], $row[3], (int)user()['id']]);
    flash('success', 'Platform Settings installed successfully.');
    redirect('admin/settings.php');
} catch (Throwable $e) {
    app_header('Platform Settings Upgrade');
    echo '<section class="panel"><h1>Upgrade failed</h1><p>' . e($e->getMessage()) . '</p></section>';
    app_footer();
}
