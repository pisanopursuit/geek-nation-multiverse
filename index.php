<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

function home_rows(string $sql, array $params = [], int $limit = 4): array {
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return array_slice($stmt->fetchAll() ?: [], 0, $limit);
    } catch (Throwable $e) {
        return [];
    }
}

function home_count(string $table): int {
    if (!preg_match('/^[a-z0-9_]+$/i', $table)) return 0;
    try { return (int)db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn(); }
    catch (Throwable $e) { return 0; }
}

function home_media_url(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('#^https?://#i', $path)) return $path;
    return base_url(ltrim($path, '/'));
}

function home_media_card(array $item, string $href, string $eyebrow, string $icon = 'spark'): void {
    $title = (string)($item['name'] ?? 'Discover more');
    $description = trim((string)($item['description'] ?? 'Discover more across Geek Nation Multiverse.'));
    $image = home_media_url((string)($item['image_path'] ?? ''));
    $meta = [];
    if (!empty($item['starts_at'])) $meta[] = date('M j, Y · g:i A', strtotime((string)$item['starts_at']));
    if (array_key_exists('price', $item) && $item['price'] !== null) $meta[] = '$' . number_format((float)$item['price'], 2);
    ?>
    <a class="gnm-home-card" href="<?= e($href) ?>">
      <div class="gnm-home-card__media<?= $image === '' ? ' gnm-home-card__media--fallback' : '' ?>">
        <?php if ($image !== ''): ?>
          <img src="<?= e($image) ?>" alt="<?= e($title) ?>" loading="lazy">
        <?php else: ?>
          <span class="gnm-home-card__fallback-icon"><?= gnm_icon($icon) ?></span>
        <?php endif; ?>
        <span class="gnm-home-card__eyebrow"><?= e($eyebrow) ?></span>
      </div>
      <div class="gnm-home-card__body">
        <h3><?= e($title) ?></h3>
        <?php if ($meta): ?><p class="gnm-home-card__meta"><?= e(implode(' · ', $meta)) ?></p><?php endif; ?>
        <p><?= e($description) ?></p>
        <span class="gnm-home-card__link">View <?= gnm_icon('arrow') ?></span>
      </div>
    </a>
    <?php
}

$universes = home_rows("SELECT id, name, slug, COALESCE(NULLIF(description,''), 'Explore this universe.') AS description FROM universes WHERE is_active = 1 ORDER BY is_featured DESC, updated_at DESC", [], 8);
if (!$universes) {
    $universes = [
        ['name'=>'Comics','slug'=>'comics','description'=>'Heroes, indie books, graphic novels, and sequential art.'],
        ['name'=>'Fantasy','slug'=>'fantasy','description'=>'Epic quests, mythology, magic, and worldbuilding.'],
        ['name'=>'Science Fiction','slug'=>'science-fiction','description'=>'Space, technology, futures, and impossible ideas.'],
        ['name'=>'Gaming','slug'=>'gaming','description'=>'Video games, streaming, esports, and game culture.'],
        ['name'=>'Anime & Manga','slug'=>'anime-manga','description'=>'Series, studios, artists, cosplay, and fandom.'],
        ['name'=>'Tabletop','slug'=>'tabletop','description'=>'Roleplaying games, miniatures, cards, and board games.'],
        ['name'=>'Horror','slug'=>'horror','description'=>'Monsters, slashers, dark fiction, and practical effects.'],
        ['name'=>'Cosplay','slug'=>'cosplay','description'=>'Costuming, fabrication, performance, and community.'],
    ];
}

$artists = home_rows("SELECT id, artist_name AS name, slug, COALESCE(NULLIF(headline,''), NULLIF(bio,''), 'Meet this creator in Artist Alley.') AS description, COALESCE(NULLIF(banner_path,''), avatar_path) AS image_path FROM artist_profiles WHERE status = 'approved' ORDER BY is_featured DESC, updated_at DESC", [], 4);
$booths = home_rows("SELECT id, name, slug, COALESCE(NULLIF(tagline,''), NULLIF(description,''), 'Visit this booth in the virtual exhibit hall.') AS description, COALESCE(NULLIF(banner_path,''), logo_path) AS image_path FROM booths WHERE status = 'approved' ORDER BY is_featured DESC, updated_at DESC", [], 4);
$events = home_rows("SELECT id, title AS name, slug, COALESCE(NULLIF(subtitle,''), NULLIF(description,''), 'Join this upcoming Geek Nation event.') AS description, starts_at, COALESCE(NULLIF(thumbnail_path,''), banner_path) AS image_path FROM events WHERE status = 'approved' AND starts_at >= NOW() ORDER BY starts_at ASC", [], 4);
$courses = home_rows("SELECT id, title AS name, slug, COALESCE(NULLIF(subtitle,''), NULLIF(description,''), 'Learn something new in the Multiverse Academy.') AS description, price, COALESCE(NULLIF(thumbnail_path,''), banner_path) AS image_path FROM academy_courses WHERE status = 'approved' ORDER BY is_featured DESC, updated_at DESC", [], 4);
$collectibles = home_rows("SELECT id, title AS name, slug, COALESCE(NULLIF(description,''), 'Explore this collector listing.') AS description, price, image_path FROM collector_items WHERE status = 'active' ORDER BY is_featured DESC, updated_at DESC", [], 4);
$companies = home_rows("SELECT id, name, slug, COALESCE(NULLIF(short_description,''), NULLIF(description,''), 'Explore this company.') AS description, COALESCE(NULLIF(banner_path,''), logo_path) AS image_path FROM companies WHERE status = 'approved' ORDER BY updated_at DESC", [], 4);
$brands = home_rows("SELECT id, name, slug, COALESCE(NULLIF(short_description,''), NULLIF(description,''), 'Explore this brand.') AS description, COALESCE(NULLIF(banner_path,''), logo_path) AS image_path FROM brands WHERE status = 'approved' ORDER BY updated_at DESC", [], 4);

$fallbacks = [
    'artists' => [
        ['name'=>'Independent Illustrators','slug'=>'','description'=>'Discover original art, commissions, prints, and visual storytelling.'],
        ['name'=>'Comic Creators','slug'=>'','description'=>'Meet writers, pencilers, inkers, colorists, and letterers.'],
        ['name'=>'Prop & Costume Makers','slug'=>'','description'=>'Explore handcrafted armor, props, costumes, and fabrication.'],
        ['name'=>'Digital Creators','slug'=>'','description'=>'Find concept artists, animators, designers, and new media makers.'],
    ],
    'booths' => [
        ['name'=>'Creator Booths','slug'=>'','description'=>'Shop directly from independent creators and small studios.'],
        ['name'=>'Convention Exclusives','slug'=>'','description'=>'Find limited releases, special editions, and event-only items.'],
        ['name'=>'Fan Shops','slug'=>'','description'=>'Browse apparel, accessories, art, and handmade fandom goods.'],
        ['name'=>'Publishers & Studios','slug'=>'','description'=>'Visit companies building the next generation of geek culture.'],
    ],
    'events' => [
        ['name'=>'Creator Spotlight','slug'=>'','description'=>'Live conversations with artists, writers, makers, and founders.'],
        ['name'=>'Worldbuilding Workshop','slug'=>'','description'=>'A practical session for building memorable fictional worlds.'],
        ['name'=>'Collector Roundtable','slug'=>'','description'=>'Collectors discuss preservation, grading, display, and discovery.'],
        ['name'=>'Cosplay Build Lab','slug'=>'','description'=>'Learn materials, techniques, and planning from experienced makers.'],
    ],
    'courses' => [
        ['name'=>'Drawing for Comics','slug'=>'','description'=>'Build stronger characters, panels, movement, and visual storytelling.'],
        ['name'=>'Practical Prop Making','slug'=>'','description'=>'Learn accessible fabrication methods from concept through finish.'],
        ['name'=>'Launch Your Creative Brand','slug'=>'','description'=>'Turn your work into a clear identity, audience, and offering.'],
        ['name'=>'Tabletop Story Design','slug'=>'','description'=>'Create campaigns, encounters, worlds, and player-driven stories.'],
    ],
    'collectibles' => [
        ['name'=>'Rare Finds','slug'=>'','description'=>'Discover unusual, limited, and hard-to-find collectibles.'],
        ['name'=>'Recently Listed','slug'=>'','description'=>'See the newest items added by collectors across the Multiverse.'],
        ['name'=>'Trades Wanted','slug'=>'','description'=>'Connect with collectors searching for fair community trades.'],
        ['name'=>'Showcase Collections','slug'=>'','description'=>'Explore curated collections and the stories behind them.'],
    ],
    'companies' => [
        ['name'=>'Independent Studios','slug'=>'','description'=>'Meet teams creating comics, games, media, and experiences.'],
        ['name'=>'Publishers','slug'=>'','description'=>'Discover publishers supporting new voices and bold worlds.'],
        ['name'=>'Event Producers','slug'=>'','description'=>'Find organizations creating conventions, panels, and meetups.'],
        ['name'=>'Creative Technology','slug'=>'','description'=>'Explore companies building tools for fans and creators.'],
    ],
    'brands' => [
        ['name'=>'Featured Brands','slug'=>'','description'=>'Discover brands shaping fandom culture and creative commerce.'],
        ['name'=>'Emerging Brands','slug'=>'','description'=>'Meet new names with original products, ideas, and communities.'],
        ['name'=>'Creator-Owned Brands','slug'=>'','description'=>'Support businesses built and operated by working creators.'],
        ['name'=>'Community Partners','slug'=>'','description'=>'Explore organizations supporting the Geek Nation ecosystem.'],
    ],
];
$artists = $artists ?: $fallbacks['artists'];
$booths = $booths ?: $fallbacks['booths'];
$events = $events ?: $fallbacks['events'];
$courses = $courses ?: $fallbacks['courses'];
$collectibles = $collectibles ?: $fallbacks['collectibles'];
$companies = $companies ?: $fallbacks['companies'];
$brands = $brands ?: $fallbacks['brands'];

app_header('Home');
$homeAnnouncement = trim((string)site_setting('homepage_announcement', ''));
$homeAnnouncementUrl = trim((string)site_setting('homepage_announcement_url', ''));
?>
<div class="gnm-home">
  <section class="gnm-feature-hero">
    <div class="gnm-feature-hero__content">
      <p class="gnm-eyebrow">THE PERMANENT ONLINE CONVENTION</p>
      <h1>Every story.<br>Every fan.<br><span>One Multiverse.</span></h1>
      <p class="gnm-feature-hero__copy">Explore fandom universes, discover creator booths, shop collectibles, attend panels, learn geek crafts, and connect with the people building the culture.</p>
      <div class="gnm-feature-hero__actions">
        <?= gnm_button('Explore the Multiverse', base_url('explore.php'), 'primary') ?>
        <?= gnm_button(user() ? 'Open My Dashboard' : 'Join Geek Nation', user() ? base_url('dashboard.php') : base_url('register.php'), 'secondary') ?>
      </div>
      <form class="gnm-feature-search" action="<?= e(base_url('search.php')) ?>" method="get">
        <label class="sr-only" for="home-search">Search Geek Nation Multiverse</label>
        <span class="gnm-feature-search__icon"><?= gnm_icon('search') ?></span>
        <input id="home-search" name="q" type="search" placeholder="Search universes, artists, booths, events, courses, and collectibles">
        <button type="submit">Search the Multiverse</button>
      </form>
      <div class="gnm-feature-hero__tags" aria-label="Popular searches">
        <span>Popular:</span>
        <a href="<?= e(base_url('search.php?q=anime')) ?>">Anime</a>
        <a href="<?= e(base_url('search.php?q=cosplay')) ?>">Cosplay</a>
        <a href="<?= e(base_url('search.php?q=comics')) ?>">Comics</a>
        <a href="<?= e(base_url('search.php?q=collectibles')) ?>">Collectibles</a>
        <a href="<?= e(base_url('search.php?q=tabletop')) ?>">Tabletop</a>
      </div>
    </div>
    <div class="gnm-feature-hero__visual" aria-hidden="true">
      <div class="gnm-feature-hero__orbit"></div>
      <img src="<?= e(base_url('assets/geek-nation-multiverse-logo.png')) ?>" alt="">
    </div>
  </section>

  <section class="gnm-home-stats" aria-label="Geek Nation Multiverse highlights">
    <div><strong>24/7</strong><span>Convention Access</span></div>
    <div><strong><?= max(100, home_count('universes')) ?>+</strong><span>Fandom Universes</span></div>
    <div><strong><?= max(500, home_count('booths')) ?>+</strong><span>Creator Booths</span></div>
    <div><strong>LIVE</strong><span>Panels & Workshops</span></div>
  </section>

  <section class="gnm-home-section gnm-home-section--universes">
    <?php gnm_section_header('Enter a Universe', 'Explore Universes', base_url('universe/index.php'), 'Find the fandom communities, worlds, and interests that feel like home.'); ?>
    <div class="gnm-universe-grid">
      <?php foreach ($universes as $i => $item): ?>
        <a class="gnm-universe-tile gnm-universe-tile--<?= ($i % 8) + 1 ?>" href="<?= e(base_url('universe/' . (!empty($item['slug']) ? 'view.php?slug=' . urlencode((string)$item['slug']) : 'index.php'))) ?>">
          <span class="gnm-universe-tile__number"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div><h3><?= e((string)$item['name']) ?></h3><p><?= e((string)($item['description'] ?? 'Explore this universe.')) ?></p></div>
          <span class="gnm-universe-tile__arrow"><?= gnm_icon('arrow') ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php
  $sections = [
    ['Meet the Artists','Explore Artists','artist-alley/index.php',$artists,'Artist Alley','artist-alley/view.php','accent'],
    ['Visit the Booths','Explore Booths','booth/index.php',$booths,'Virtual Exhibit Hall','booth/view.php','shop'],
    ['Panels & Events','Explore Panels & Events','events/index.php',$events,'Live & Upcoming','events/view.php','calendar'],
    ['Multiverse Academy','Explore the Academy','academy/index.php',$courses,'Learn Something New','academy/view.php','book'],
    ['Collectors Marketplace','Explore Collectibles','collectors/index.php',$collectibles,'Rare Finds & Community Trades','collectors/item.php','star'],
  ];
  foreach ($sections as [$title,$explore,$indexPath,$items,$eyebrow,$viewPath,$icon]): ?>
    <section class="gnm-home-section">
      <?php gnm_section_header($title, $explore, base_url($indexPath)); ?>
      <div class="gnm-home-card-grid">
        <?php foreach (array_slice($items,0,4) as $item):
          $href = base_url($indexPath);
          if (!empty($item['slug'])) $href = base_url($viewPath . '?slug=' . urlencode((string)$item['slug']));
          home_media_card($item, $href, $eyebrow, $icon);
        endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>

  <section class="gnm-home-section gnm-home-community">
    <?php gnm_section_header('Community Activity', 'Explore Community', base_url('explore.php'), 'The Multiverse is always moving. Find something new every time you visit.'); ?>
    <div class="gnm-community-grid">
      <?php gnm_action_card('New Creators Arriving', 'Meet artists, makers, teachers, vendors, and storytellers joining the community.', 'Meet the Community', base_url('artist-alley/index.php'), 'users'); ?>
      <?php gnm_action_card('Events Happening Soon', 'Build your convention schedule with panels, workshops, streams, and meetups.', 'See Upcoming Events', base_url('events/index.php'), 'calendar'); ?>
      <?php gnm_action_card('Fresh Finds & Listings', 'Browse newly added products, collectibles, courses, and creator releases.', 'Start Exploring', base_url('explore.php'), 'spark'); ?>
    </div>
  </section>

  <section class="gnm-home-section">
    <?php gnm_section_header('Featured Companies', 'Explore Companies', base_url('company/index.php')); ?>
    <div class="gnm-home-card-grid">
      <?php foreach (array_slice($companies,0,4) as $item):
        $href = !empty($item['slug']) ? base_url('company/view.php?slug=' . urlencode((string)$item['slug'])) : base_url('company/index.php');
        home_media_card($item, $href, 'Company', 'users');
      endforeach; ?>
    </div>
  </section>

  <section class="gnm-home-section">
    <?php gnm_section_header('Featured Brands', 'Explore Brands', base_url('brand/index.php')); ?>
    <div class="gnm-home-card-grid">
      <?php foreach (array_slice($brands,0,4) as $item):
        $href = !empty($item['slug']) ? base_url('brand/view.php?slug=' . urlencode((string)$item['slug'])) : base_url('brand/index.php');
        home_media_card($item, $href, 'Brand', 'star');
      endforeach; ?>
    </div>
  </section>

  <section class="gnm-home-newsletter">
    <div><p class="gnm-eyebrow">STAY CONNECTED</p><h2>Never miss what is happening in the Multiverse.</h2><p>Get featured creators, new universes, upcoming panels, courses, collectibles, and convention discoveries delivered to you.</p></div>
    <form action="<?= e(base_url('register.php')) ?>" method="get"><input type="email" name="email" placeholder="Enter your email address" aria-label="Email address"><button type="submit">Join Geek Nation</button></form>
  </section>
</div>
<?php app_footer(); ?>
