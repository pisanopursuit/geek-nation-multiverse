<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_admin();
app_header('Design System');

gnm_page_hero([
    'eyebrow' => 'PLATFORM INTEGRATION · VERSION 10.4',
    'title' => 'Geek Nation Design System',
    'description' => 'The shared visual and structural components used to make every part of Geek Nation Multiverse feel like one online convention.',
    'actions' => [
        ['label' => 'Return to Dashboard', 'href' => base_url('dashboard.php'), 'variant' => 'primary'],
        ['label' => 'Explore the Platform', 'href' => base_url('explore.php'), 'variant' => 'ghost'],
    ],
]);
?>
<div class="gnm-component-preview">
  <section class="gnm-preview-block">
    <?php gnm_section_header('Color System', null, null, 'The core platform palette. Module content may add color without replacing these shared foundations.'); ?>
    <div class="gnm-color-grid">
      <div class="gnm-color-swatch"><span style="background:#38d8ff"></span><strong>Cyan · #38D8FF</strong></div>
      <div class="gnm-color-swatch"><span style="background:#5f7cff"></span><strong>Blue · #5F7CFF</strong></div>
      <div class="gnm-color-swatch"><span style="background:#8b46ff"></span><strong>Purple · #8B46FF</strong></div>
      <div class="gnm-color-swatch"><span style="background:#f03fb6"></span><strong>Pink · #F03FB6</strong></div>
      <div class="gnm-color-swatch"><span style="background:#35d39a"></span><strong>Green · #35D39A</strong></div>
      <div class="gnm-color-swatch"><span style="background:#ffcf4a"></span><strong>Yellow · #FFCF4A</strong></div>
    </div>
  </section>

  <section class="gnm-preview-block">
    <?php gnm_section_header('Buttons & Status', 'Explore the platform →', base_url('explore.php')); ?>
    <div class="gnm-actions">
      <?= gnm_button('Primary Action', '#', 'primary') ?>
      <?= gnm_button('Secondary Action', '#', 'secondary') ?>
      <?= gnm_button('Ghost Action', '#', 'ghost') ?>
      <?= gnm_button('Text Action', '#', 'text') ?>
      <?= gnm_badge('Featured', 'accent') ?>
      <?= gnm_badge('Live Now', 'danger') ?>
      <?= gnm_badge('Open', 'success') ?>
      <?= gnm_badge('Starting Soon', 'warning') ?>
    </div>
  </section>

  <section class="gnm-preview-block">
    <?php gnm_section_header('Content Cards', 'Explore all content →', base_url('explore.php'), 'Reusable cards for directories, the homepage, related content, and member dashboards.'); ?>
    <div class="gnm-grid gnm-grid--3">
      <?php
      gnm_card([
          'eyebrow' => 'Featured Universe',
          'title' => 'Heroes Beyond Worlds',
          'description' => 'A community for capes, cosmic adventures, independent comics, and heroic storytelling.',
          'href' => base_url('universe/index.php'),
          'icon' => 'spark',
          'badges' => [['label' => 'Trending', 'variant' => 'accent']],
          'meta' => ['1,248 members', '32 active today'],
      ]);
      gnm_card([
          'eyebrow' => 'Panel Starting Soon',
          'title' => 'Building Worlds That Last',
          'description' => 'Creators discuss lore, continuity, character design, and building communities around original stories.',
          'href' => base_url('events/index.php'),
          'icon' => 'calendar',
          'badges' => [['label' => 'Starts in 20 min', 'variant' => 'warning']],
          'meta' => ['Virtual', '88 registered'],
      ]);
      gnm_card([
          'eyebrow' => 'Course Spotlight',
          'title' => 'Sequential Art Foundations',
          'description' => 'Learn visual storytelling, panel composition, pacing, and page design from working artists.',
          'href' => base_url('academy/index.php'),
          'icon' => 'book',
          'badges' => [['label' => 'Beginner', 'variant' => 'info']],
          'meta' => ['8 lessons', 'Self-paced'],
      ]);
      ?>
    </div>
  </section>

  <section class="gnm-preview-block">
    <?php gnm_section_header('Dashboard Stats'); ?>
    <div class="gnm-stat-grid">
      <?php gnm_stat_card('My Universes', 8, '2 active today', 'spark'); ?>
      <?php gnm_stat_card('Upcoming Events', 4, 'Next event tomorrow', 'calendar'); ?>
      <?php gnm_stat_card('Followers', 214, '12 new this week', 'users'); ?>
      <?php gnm_stat_card('Active Listings', 16, '3 offers waiting', 'shop'); ?>
    </div>
  </section>

  <section class="gnm-preview-block">
    <?php gnm_section_header('Quick Actions'); ?>
    <div class="gnm-grid gnm-grid--2">
      <?php gnm_action_card('Open a Booth', 'Create a convention storefront, publish products, and connect your booth to events and universes.', 'Create Booth', base_url('booth/create.php'), 'shop'); ?>
      <?php gnm_action_card('Create a Panel', 'Publish a panel, workshop, signing, livestream, tournament, or meetup.', 'Create Event', base_url('events/create.php'), 'calendar'); ?>
    </div>
  </section>

  <section class="gnm-preview-block">
    <?php gnm_section_header('Filter Bar'); ?>
    <?php gnm_filter_bar([
        ['type' => 'search', 'name' => 'q', 'label' => 'Search', 'placeholder' => 'Search the Multiverse'],
        ['type' => 'select', 'name' => 'type', 'label' => 'Content Type', 'options' => ['' => 'Everything', 'universe' => 'Universes', 'artist' => 'Artists', 'booth' => 'Booths']],
        ['type' => 'select', 'name' => 'sort', 'label' => 'Sort', 'options' => ['trending' => 'Trending', 'newest' => 'Newest', 'active' => 'Most Active']],
    ], base_url('search.php')); ?>
  </section>

  <section class="gnm-preview-block">
    <?php gnm_empty_state('Nothing here yet', 'Empty states should always explain what happened and provide a meaningful next action instead of leaving the user at a dead end.', 'Explore Geek Nation', base_url('explore.php'), 'star'); ?>
  </section>
</div>
<?php app_footer(); ?>
