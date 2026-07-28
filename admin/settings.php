<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
if (!platform_settings_schema_ready()) redirect('upgrade-platform-settings.php');

$definitions = [
    'general' => [
        'title' => 'General',
        'description' => 'Core identity and contact information used throughout the platform.',
        'fields' => [
            'site_name' => ['Site name', 'text'],
            'site_tagline' => ['Tagline', 'text'],
            'site_description' => ['Site description', 'textarea'],
            'contact_email' => ['Public contact email', 'email'],
            'footer_credit' => ['Footer credit', 'text'],
        ],
    ],
    'branding' => [
        'title' => 'Branding',
        'description' => 'Paths are relative to the application root, such as assets/logo.png or uploads/branding/logo.webp.',
        'fields' => [
            'logo_path' => ['Header logo path', 'text'],
            'favicon_path' => ['Favicon path', 'text'],
            'default_share_image' => ['Default social sharing image', 'text'],
        ],
    ],
    'homepage' => [
        'title' => 'Homepage',
        'description' => 'Control the site-wide announcement and default number of featured cards.',
        'fields' => [
            'homepage_announcement' => ['Announcement text', 'text'],
            'homepage_announcement_url' => ['Announcement link', 'url'],
            'homepage_featured_limit' => ['Featured items per section', 'number'],
        ],
    ],
    'social' => [
        'title' => 'Social Links',
        'description' => 'Leave any network blank to hide it from public areas.',
        'fields' => [
            'facebook_url' => ['Facebook URL', 'url'],
            'instagram_url' => ['Instagram URL', 'url'],
            'youtube_url' => ['YouTube URL', 'url'],
            'tiktok_url' => ['TikTok URL', 'url'],
            'discord_url' => ['Discord URL', 'url'],
            'x_url' => ['X URL', 'url'],
        ],
    ],
    'seo' => [
        'title' => 'SEO Defaults',
        'description' => 'Default metadata used when a page does not provide custom values.',
        'fields' => [
            'seo_title_suffix' => ['Page title suffix', 'text'],
            'seo_default_description' => ['Default meta description', 'textarea'],
        ],
    ],
    'features' => [
        'title' => 'Feature Toggles',
        'description' => 'Disable a module to remove it from public and member navigation without deleting its data.',
        'fields' => [
            'feature_universes' => ['Universes', 'checkbox'],
            'feature_booths' => ['Booths', 'checkbox'],
            'feature_events' => ['Panels & Events', 'checkbox'],
            'feature_artists' => ['Artist Alley', 'checkbox'],
            'feature_academy' => ['Multiverse Academy', 'checkbox'],
            'feature_collectors' => ['Collectors Marketplace', 'checkbox'],
            'feature_companies' => ['Companies', 'checkbox'],
            'feature_brands' => ['Brands', 'checkbox'],
        ],
    ],
    'access' => [
        'title' => 'Access & Maintenance',
        'description' => 'Administrators can always sign in and access the platform during maintenance mode.',
        'fields' => [
            'registration_enabled' => ['Allow new registrations', 'checkbox'],
            'maintenance_mode' => ['Maintenance mode', 'checkbox'],
            'maintenance_message' => ['Maintenance message', 'textarea'],
        ],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $allowed = [];
    foreach ($definitions as $group => $section) {
        foreach ($section['fields'] as $key => $field) $allowed[$key] = [$group, $field[1]];
    }
    $stmt = db()->prepare('INSERT INTO platform_settings(setting_key,setting_value,setting_group,is_public,updated_by) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),setting_group=VALUES(setting_group),updated_by=VALUES(updated_by),updated_at=CURRENT_TIMESTAMP');
    foreach ($allowed as $key => [$group, $type]) {
        $value = $type === 'checkbox' ? (isset($_POST[$key]) ? '1' : '0') : trim((string)($_POST[$key] ?? ''));
        if ($type === 'number') $value = (string)max(1, min(12, (int)$value));
        $stmt->execute([$key, $value, $group, in_array($group, ['general','branding','social','seo','homepage','access'], true) ? 1 : 0, (int)user()['id']]);
    }
    clear_platform_settings_cache();
    flash('success', 'Platform settings saved.');
    redirect('admin/settings.php');
}

$values = platform_settings_all();
app_header('Platform Settings');
gnm_page_hero([
    'eyebrow' => 'ADMINISTRATION',
    'title' => 'Platform Settings',
    'description' => 'Manage branding, homepage defaults, module availability, SEO, social links, and platform access from one place.',
    'actions' => [['label'=>'Administration Home','href'=>base_url('admin/index.php'),'variant'=>'ghost']],
]);
?>
<form method="post" class="gnm-settings-form">
<?=csrf_field()?>
<nav class="gnm-settings-nav" aria-label="Settings sections">
<?php foreach ($definitions as $group => $section): ?><a href="#settings-<?=e($group)?>"><?=e($section['title'])?></a><?php endforeach; ?>
</nav>
<?php foreach ($definitions as $group => $section): ?>
<section class="gnm-section gnm-settings-section" id="settings-<?=e($group)?>">
    <?php gnm_section_header($section['title'], '', '', $section['description']); ?>
    <div class="gnm-settings-grid">
    <?php foreach ($section['fields'] as $key => [$label, $type]): $value = (string)($values[$key] ?? ''); ?>
        <?php if ($type === 'checkbox'): ?>
            <label class="gnm-setting-toggle">
                <input type="checkbox" name="<?=e($key)?>" value="1" <?=$value === '1' ? 'checked' : ''?>>
                <span><strong><?=e($label)?></strong><small><?=$value === '1' ? 'Currently enabled' : 'Currently disabled'?></small></span>
            </label>
        <?php else: ?>
            <label class="gnm-setting-field <?= $type === 'textarea' ? 'gnm-setting-field--wide' : '' ?>">
                <span><?=e($label)?></span>
                <?php if ($type === 'textarea'): ?>
                    <textarea name="<?=e($key)?>" rows="4"><?=e($value)?></textarea>
                <?php else: ?>
                    <input type="<?=e($type)?>" name="<?=e($key)?>" value="<?=e($value)?>" <?= $type === 'number' ? 'min="1" max="12"' : '' ?>>
                <?php endif; ?>
            </label>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>
<div class="gnm-settings-save"><button class="button primary large" type="submit">Save Platform Settings</button></div>
</form>
<?php app_footer();
