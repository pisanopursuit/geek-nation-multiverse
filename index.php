<?php require __DIR__.'/includes/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Geek Nation Multiverse — a permanent online convention for fandoms, creators, collectors, brands, panels, education, and virtual booths." />
  <title>Geek Nation Multiverse</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="space-bg" aria-hidden="true"></div>

  <header class="site-header">
    <a class="brand" href="#top" aria-label="Geek Nation Multiverse home">
      <img src="assets/geek-nation-multiverse-logo.png" alt="Geek Nation Multiverse" />
    </a>
    <button class="menu-toggle" aria-expanded="false" aria-controls="main-nav">☰</button>
    <nav id="main-nav" class="main-nav" aria-label="Primary navigation">
      <a href="#universes">Universes</a>
      <a href="#booths">Booths</a>
      <a href="#artist-alley">Artist Alley</a>
      <a href="#panels">Panels</a>
      <a href="#academy">Academy</a>
      <a href="#marketplace">Marketplace</a>
    </nav>
    <div class="header-actions">
      <?php if(user()): ?><a class="button ghost" href="dashboard.php">Dashboard</a><?php else: ?><a class="button ghost" href="login.php">Sign In</a><?php endif; ?>
      <?php if(user()): ?><a class="button primary" href="dashboard.php">Open a Booth</a><?php else: ?><a class="button primary" href="register.php">Join the Multiverse</a><?php endif; ?>
    </div>
  </header>

  <main id="top">
    <section class="hero">
      <div class="hero-orbit orbit-one"></div>
      <div class="hero-orbit orbit-two"></div>
      <div class="hero-content">
        <p class="eyebrow">THE PERMANENT ONLINE CONVENTION</p>
        <h1>Every story. Every fan. One place.</h1>
        <p class="hero-copy">Explore fandom universes, discover creator booths, shop collectibles, attend panels, learn geek crafts, and connect with the people building the culture.</p>
        <form class="hero-search" id="hero-search">
          <label class="sr-only" for="search-input">Search the multiverse</label>
          <input id="search-input" type="search" placeholder="Search universes, brands, characters, booths, or products" />
          <button type="submit">Search</button>
        </form>
        <div class="quick-links" aria-label="Popular searches">
          <button data-search="anime">Anime</button>
          <button data-search="cosplay">Cosplay</button>
          <button data-search="comics">Comics</button>
          <button data-search="collectibles">Collectibles</button>
          <button data-search="tabletop">Tabletop</button>
        </div>
      </div>
      <div class="hero-logo-card">
        <img src="assets/geek-nation-multiverse-logo.png" alt="Geek Nation Multiverse logo" />
      </div>
    </section>

    <section class="stats-strip" aria-label="Platform highlights">
      <div><strong>24/7</strong><span>Convention Access</span></div>
      <div><strong>100+</strong><span>Fandom Universes</span></div>
      <div><strong>500+</strong><span>Creator Booths</span></div>
      <div><strong>Live</strong><span>Panels & Workshops</span></div>
    </section>

    <section id="universes" class="section">
      <div class="section-heading">
        <div><p class="eyebrow">EXPLORE</p><h2>Enter a Universe</h2></div>
        <button class="text-link" data-view-all="universes">View all universes →</button>
      </div>
      <div class="universe-grid" id="universe-grid"></div>
    </section>

    <section id="booths" class="section panel-section">
      <div class="section-heading">
        <div><p class="eyebrow">VIRTUAL EXHIBIT HALL</p><h2>Featured Booths</h2></div>
        <div class="filter-row" id="booth-filters"></div>
      </div>
      <div class="booth-grid" id="booth-grid"></div>
    </section>

    <section id="panels" class="section">
      <div class="section-heading">
        <div><p class="eyebrow">LIVE NOW & COMING UP</p><h2>Panels and Events</h2></div>
        <button class="text-link">Build My Schedule →</button>
      </div>
      <div class="event-layout">
        <article class="featured-event">
          <div class="live-badge">LIVE</div>
          <div class="event-art portal-art"></div>
          <div class="event-copy">
            <p class="event-meta">Main Stage • 7:00 PM ET</p>
            <h3>Building Worlds: The Future of Independent Fandom</h3>
            <p>Creators, artists, makers, and community leaders discuss how independent fandoms become thriving universes.</p>
            <button class="button primary">Join Panel</button>
          </div>
        </article>
        <div class="event-list" id="event-list"></div>
      </div>
    </section>

    <section id="artist-alley" class="section artist-section">
      <div class="artist-copy">
        <p class="eyebrow">CREATOR DISTRICT</p>
        <h2>Artist Alley</h2>
        <p>Meet illustrators, prop builders, comic creators, sculptors, cosplay designers, and independent makers. Commission custom work or discover your next favorite artist.</p>
        <button class="button secondary">Explore Artist Alley</button>
      </div>
      <div class="artist-cards" id="artist-cards"></div>
    </section>

    <section id="academy" class="section">
      <div class="section-heading">
        <div><p class="eyebrow">LEARN FROM THE MAKERS</p><h2>Multiverse Academy</h2></div>
        <button class="text-link">Browse All Classes →</button>
      </div>
      <div class="academy-grid" id="academy-grid"></div>
    </section>

    <section id="marketplace" class="section marketplace-section">
      <div class="section-heading">
        <div><p class="eyebrow">BUY • SELL • TRADE</p><h2>Collector Marketplace</h2></div>
      </div>
      <div class="market-tabs" role="tablist">
        <button class="active" data-market-tab="trending">Trending</button>
        <button data-market-tab="new">New Arrivals</button>
        <button data-market-tab="rare">Rare Finds</button>
        <button data-market-tab="trade">Open to Trade</button>
      </div>
      <div class="product-grid" id="product-grid"></div>
    </section>

    <section class="cta-section">
      <div>
        <p class="eyebrow">BUILD YOUR PLACE IN THE MULTIVERSE</p>
        <h2>Your booth never has to close.</h2>
        <p>Sell products, host livestreams, publish tutorials, schedule panels, grow followers, and connect your store to the fandoms that matter most.</p>
      </div>
      <button class="button primary large" data-modal="booth">Open Your Booth</button>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-brand">
      <img src="assets/geek-nation-multiverse-logo.png" alt="Geek Nation Multiverse" />
      <p>A permanent online convention for every fandom.</p>
    </div>
    <div><h3>Explore</h3><a href="#universes">Universes</a><a href="#booths">Booths</a><a href="#panels">Panels</a></div>
    <div><h3>Create</h3><a href="#artist-alley">Artist Alley</a><a href="#academy">Teach a Class</a><a href="#marketplace">Sell or Trade</a></div>
    <div><h3>Company</h3><a href="#">About</a><a href="#">Community Guidelines</a><a href="#">Contact</a></div>
    <div class="footer-authors"><strong>Authors &amp; Created by:</strong> Marc Delsoin, Abdoul Ba, Trevor Rukwava, &amp; Sean Pisano</div>
  </footer>

  <div class="modal" id="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <button class="modal-close" data-close-modal aria-label="Close">×</button>
      <div id="modal-content"></div>
    </div>
  </div>

  <script src="app.js"></script>
</body>
</html>
