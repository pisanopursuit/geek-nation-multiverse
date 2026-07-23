<?php
require __DIR__.'/includes/bootstrap.php';
require_admin();

$done = false;
$error = '';

function booth_column_exists(string $column): bool {
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS '
        . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute(['booths', $column]);
    return (bool)$stmt->fetchColumn();
}

function add_booth_column_if_missing(string $column, string $definition): void {
    if (!booth_column_exists($column)) {
        db()->exec("ALTER TABLE booths ADD COLUMN {$column} {$definition}");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        add_booth_column_if_missing('booth_presence', "ENUM('open','away','offline') NOT NULL DEFAULT 'offline' AFTER contact_email");
        add_booth_column_if_missing('location_label', 'VARCHAR(190) NULL AFTER booth_presence');
        add_booth_column_if_missing('hours_text', 'VARCHAR(500) NULL AFTER location_label');
        add_booth_column_if_missing('support_email', 'VARCHAR(190) NULL AFTER hours_text');
        add_booth_column_if_missing('instagram_url', 'VARCHAR(500) NULL AFTER support_email');
        add_booth_column_if_missing('youtube_url', 'VARCHAR(500) NULL AFTER instagram_url');
        add_booth_column_if_missing('tiktok_url', 'VARCHAR(500) NULL AFTER youtube_url');
        add_booth_column_if_missing('discord_url', 'VARCHAR(500) NULL AFTER tiktok_url');
        add_booth_column_if_missing('return_policy', 'TEXT NULL AFTER discord_url');
        add_booth_column_if_missing('shipping_policy', 'TEXT NULL AFTER return_policy');

        run_sql_file(__DIR__.'/database/booth-management-v5.1.sql');
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

app_header('Upgrade Booth Management');
?>
<section class="dashboard-hero">
    <p class="eyebrow">VERSION 5.1.1</p>
    <h1>Booth Management Upgrade</h1>
    <p>Adds booth teams, galleries, downloads, public status, social links, policies, analytics, and expanded product/order controls.</p>
</section>
<section class="form-card">
<?php if ($done): ?>
    <div class="alert success">Booth Management was installed successfully.</div>
    <p><a class="button primary" href="booth/dashboard.php">Open Booth Dashboard</a></p>
<?php else: ?>
    <?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif ?>
    <form method="post">
        <?=csrf_field()?>
        <button class="button primary">Install Version 5.1.1</button>
    </form>
<?php endif ?>
</section>
<?php app_footer();
