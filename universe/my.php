<?php
require __DIR__ . '/../includes/bootstrap.php';
require_auth();

if (!universe_engine_ready()) {
    if ((user()['role'] ?? '') === 'admin') redirect('../upgrade-universes.php');
    http_response_code(503);
    exit('Universe Engine has not been installed.');
}

$viewer = user();
$userId = (int)$viewer['id'];
$isAdmin = ($viewer['role'] ?? '') === 'admin';

$joinedStmt = db()->prepare(
    "SELECT u.*,
        (SELECT COUNT(*) FROM user_universes members WHERE members.universe_id=u.id) member_count,
        (SELECT COUNT(*) FROM universes children WHERE children.parent_id=u.id AND children.status='approved' AND children.is_active=1) child_count
     FROM user_universes uu
     JOIN universes u ON u.id=uu.universe_id
     WHERE uu.user_id=? AND u.status='approved' AND u.is_active=1
     ORDER BY uu.joined_at DESC, u.name"
);
$joinedStmt->execute([$userId]);
$joinedUniverses = $joinedStmt->fetchAll();

$createdStmt = db()->prepare(
    "SELECT u.*,
        (SELECT COUNT(*) FROM user_universes members WHERE members.universe_id=u.id) member_count,
        (SELECT COUNT(*) FROM universes children WHERE children.parent_id=u.id) child_count
     FROM universes u
     WHERE u.created_by=?
     ORDER BY u.updated_at DESC, u.created_at DESC, u.name"
);
$createdStmt->execute([$userId]);
$createdUniverses = $createdStmt->fetchAll();

$pendingCount = 0;
foreach ($createdUniverses as $universe) {
    if (($universe['status'] ?? '') === 'pending') $pendingCount++;
}

function my_universe_card(array $universe, bool $created, bool $isAdmin): void {
    $status = (string)($universe['status'] ?? 'approved');
    $isPublic = $status === 'approved' && !empty($universe['is_active']);
    $viewUrl = base_url('universe/view.php?slug=' . urlencode($universe['slug']));
    ?>
    <article class="my-universe-card" style="<?=e(universe_theme_vars($universe))?>">
        <?php if (!empty($universe['banner_path'])): ?>
            <a class="my-universe-card__media" href="<?=e($viewUrl)?>">
                <img src="<?=e(base_url($universe['banner_path']))?>" alt="">
            </a>
        <?php else: ?>
            <a class="my-universe-card__media my-universe-card__media--empty" href="<?=e($viewUrl)?>">
                <span><?=e($universe['icon'] ?: '✦')?></span>
            </a>
        <?php endif; ?>
        <div class="my-universe-card__body">
            <div class="my-universe-card__badges">
                <span class="status-pill status-pill--<?=e($status)?>"><?=e(ucfirst($status))?></span>
                <?php if ($created): ?><span class="status-pill status-pill--created">Created</span><?php endif; ?>
            </div>
            <h3><?=e($universe['name'])?></h3>
            <p><?=e($universe['short_description'] ?: 'Explore this universe and its community.')?></p>
            <div class="my-universe-card__meta">
                <span><?=number_format((int)$universe['member_count'])?> members</span>
                <span><?=number_format((int)$universe['child_count'])?> worlds</span>
            </div>
            <div class="my-universe-card__actions">
                <?php if ($isPublic || $isAdmin): ?><a class="button ghost small" href="<?=e($viewUrl)?>">View Universe</a><?php endif; ?>
                <?php if ($created && $isAdmin): ?><a class="button primary small" href="<?=e(base_url('admin/universes.php?edit='.(int)$universe['id']))?>">Manage Universe</a><?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

app_header('My Universes');
?>
<section class="dashboard-hero my-universes-hero">
    <div>
        <p class="eyebrow">MY MULTIVERSE</p>
        <h1>My Universes</h1>
        <p>Return to the communities you follow and manage the universes you have created.</p>
    </div>
    <div class="my-universes-hero__actions">
        <a class="button ghost" href="<?=e(base_url('universe/index.php'))?>">Find Universes</a>
        <?php if ($isAdmin): ?><a class="button primary" href="<?=e(base_url('admin/universes.php'))?>">Create Universe</a><?php endif; ?>
    </div>
</section>

<section class="my-universe-stats" aria-label="Universe totals">
    <article><strong><?=number_format(count($joinedUniverses))?></strong><span>Joined</span></article>
    <article><strong><?=number_format(count($createdUniverses))?></strong><span>Created</span></article>
    <article><strong><?=number_format($pendingCount)?></strong><span>Pending</span></article>
</section>

<section class="my-universe-section">
    <div class="section-heading">
        <div><p class="eyebrow">COMMUNITIES YOU FOLLOW</p><h2>Joined Universes</h2></div>
        <a href="<?=e(base_url('universe/index.php'))?>">Find more universes →</a>
    </div>
    <?php if ($joinedUniverses): ?>
        <div class="my-universe-grid">
            <?php foreach ($joinedUniverses as $universe) my_universe_card($universe, false, $isAdmin); ?>
        </div>
    <?php else: ?>
        <article class="app-card my-universe-empty">
            <h3>You have not joined a universe yet.</h3>
            <p>Explore fandom communities and join the ones you want to follow.</p>
            <a class="button primary" href="<?=e(base_url('universe/index.php'))?>">Find Universes</a>
        </article>
    <?php endif; ?>
</section>

<section class="my-universe-section">
    <div class="section-heading">
        <div><p class="eyebrow">YOUR WORLDS</p><h2>Created Universes</h2></div>
        <?php if ($isAdmin): ?><a href="<?=e(base_url('admin/universes.php'))?>">Create a universe →</a><?php endif; ?>
    </div>
    <?php if ($createdUniverses): ?>
        <div class="my-universe-grid">
            <?php foreach ($createdUniverses as $universe) my_universe_card($universe, true, $isAdmin); ?>
        </div>
    <?php else: ?>
        <article class="app-card my-universe-empty">
            <h3>You have not created a universe yet.</h3>
            <p>Create a community around a fandom, franchise, world, or original idea.</p>
            <?php if ($isAdmin): ?><a class="button primary" href="<?=e(base_url('admin/universes.php'))?>">Create Universe</a><?php else: ?><p class="muted">Universe creation is currently managed by administrators.</p><?php endif; ?>
        </article>
    <?php endif; ?>
</section>
<?php app_footer(); ?>
