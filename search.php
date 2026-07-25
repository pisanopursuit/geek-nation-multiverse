<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
app_header('Search');
$q = trim((string)($_GET['q'] ?? ''));
?>
<section class="page-hero"><p class="eyebrow">SEARCH THE MULTIVERSE</p><h1>Search</h1></section>
<section class="panel"><form method="get" class="hero-search"><label class="sr-only" for="global-q">Search</label><input id="global-q" name="q" value="<?= e($q) ?>" placeholder="Search universes, booths, artists, events, courses, and collectibles"><button type="submit">Search</button></form><?php if ($q !== ''): ?><p>Global cross-module results for <strong><?= e($q) ?></strong> will be connected during the search integration sprint.</p><?php endif; ?></section>
<?php app_footer(); ?>
