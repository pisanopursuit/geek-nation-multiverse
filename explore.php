<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
app_header('Explore');
?>
<section class="page-hero"><p class="eyebrow">DISCOVER THE MULTIVERSE</p><h1>Explore</h1><p>Find universes, booths, panels, artists, courses, and collectibles across Geek Nation Multiverse.</p></section>
<section class="dashboard-grid">
  <article class="panel"><h2>Universes</h2><p>Join fandom-centered communities.</p><a class="button primary" href="<?= e(base_url('universe/index.php')) ?>">Explore Universes</a></article>
  <article class="panel"><h2>Booths</h2><p>Discover vendors, creators, and exhibitors.</p><a class="button primary" href="<?= e(base_url('booth/index.php')) ?>">Explore Booths</a></article>
  <article class="panel"><h2>Panels &amp; Events</h2><p>See what is live, upcoming, and happening soon.</p><a class="button primary" href="<?= e(base_url('events/index.php')) ?>">Explore Events</a></article>
  <article class="panel"><h2>Artist Alley</h2><p>Meet artists and browse their work.</p><a class="button primary" href="<?= e(base_url('artist-alley/index.php')) ?>">Explore Artists</a></article>
  <article class="panel"><h2>Multiverse Academy</h2><p>Learn from creators and educators.</p><a class="button primary" href="<?= e(base_url('academy/index.php')) ?>">Explore Academy</a></article>
  <article class="panel"><h2>Collectors Marketplace</h2><p>Browse collections, trades, and listings.</p><a class="button primary" href="<?= e(base_url('collectors/index.php')) ?>">Explore Collectibles</a></article>
</section>
<?php app_footer(); ?>
