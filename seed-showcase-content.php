<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_admin();

function seed_slug(string $s): string { return strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$s) ?? '', '-')); }

function seed_universe(string $name, int $parentId, string $icon, string $short, string $primary, string $secondary, string $accent, int $ownerId): int {
    $slug = seed_slug($name);
    $s = db()->prepare('SELECT id FROM universes WHERE slug=?'); $s->execute([$slug]);
    if ($id = $s->fetchColumn()) return (int)$id;
    $maxSort = (int)db()->query('SELECT COALESCE(MAX(sort_order),0) FROM universes')->fetchColumn() + 10;
    $ins = db()->prepare("INSERT INTO universes(name,slug,icon,short_description,description,status,is_active,is_featured,primary_color,secondary_color,accent_color,background_color,surface_color,text_color,parent_id,sort_order,created_by) VALUES(?,?,?,?,?, 'approved',1,0,?,?,?, '#070812','#111321','#f7f7fb', ?,?,?)");
    $ins->execute([$name,$slug,$icon,$short,$short,$primary,$secondary,$accent,$parentId,$maxSort,$ownerId]);
    return (int)db()->lastInsertId();
}

function seed_booth(int $ownerId, string $name, string $tagline, string $description, int $universeId, array $products): int {
    $slug = seed_slug($name);
    $s = db()->prepare('SELECT id FROM booths WHERE slug=?'); $s->execute([$slug]);
    if (!($boothId = $s->fetchColumn())) {
        $ins = db()->prepare("INSERT INTO booths(owner_user_id,name,slug,tagline,description,contact_email,commerce_mode,status,booth_presence) VALUES(?,?,?,?,?,?, 'demo','approved','open')");
        $ins->execute([$ownerId,$name,$slug,$tagline,$description,'shop@'.$slug.'.example.com']);
        $boothId = (int)db()->lastInsertId();
    }
    db()->prepare('INSERT IGNORE INTO booth_universes(booth_id,universe_id) VALUES(?,?)')->execute([$boothId,$universeId]);
    foreach ($products as $p) {
        [$pname,$pdesc,$price,$sku,$inventory] = $p;
        $s2 = db()->prepare('SELECT id FROM booth_products WHERE booth_id=? AND name=?'); $s2->execute([$boothId,$pname]);
        if ($s2->fetchColumn()) continue;
        db()->prepare("INSERT INTO booth_products(booth_id,name,slug,description,product_type,price,sku,inventory_quantity,status) VALUES(?,?,?,?, 'physical',?,?,?, 'active')")
            ->execute([$boothId,$pname,seed_slug($pname).'-'.$boothId,$pdesc,$price,$sku,$inventory]);
    }
    return (int)$boothId;
}

function seed_event(int $ownerId, string $title, string $subtitle, string $description, string $type, string $format, string $startsIn, int $capacity, int $universeId): int {
    $slug = seed_slug($title);
    $s = db()->prepare('SELECT id FROM events WHERE slug=?'); $s->execute([$slug]);
    if (!($eventId = $s->fetchColumn())) {
        $start = date('Y-m-d H:i:s', strtotime($startsIn));
        $end = date('Y-m-d H:i:s', strtotime($startsIn.' +90 minutes'));
        $ins = db()->prepare("INSERT INTO events(owner_user_id,title,slug,subtitle,description,event_type,status,visibility,format,starts_at,ends_at,timezone,capacity,registration_mode) VALUES(?,?,?,?,?,?, 'approved','public',?,?,?, 'America/New_York',?, 'open')");
        $ins->execute([$ownerId,$title,$slug,$subtitle,$description,$type,$format,$start,$end,$capacity]);
        $eventId = (int)db()->lastInsertId();
    }
    $exists = db()->prepare("SELECT 1 FROM event_relationships WHERE event_id=? AND entity_type='universe' AND entity_id=?");
    $exists->execute([$eventId,$universeId]);
    if (!$exists->fetchColumn()) db()->prepare("INSERT INTO event_relationships(event_id,entity_type,entity_id) VALUES(?, 'universe', ?)")->execute([$eventId,$universeId]);
    return (int)$eventId;
}

function seed_showcase_content(int $ownerId): array {
    $roots = [];
    foreach (db()->query('SELECT id,name,slug FROM universes WHERE parent_id IS NULL') as $r) $roots[$r['name']] = (int)$r['id'];

    $newSubs = [
        ['Game of Thrones','Fantasy','🐉','Dragons, houses, and the war for the Iron Throne.','#4a0e0e','#1a0505','#c9a227'],
        ['Final Fantasy','Gaming','🔮','Crystals, summons, and turn-based legends.','#0d1b3e','#1a2a5e','#8ecae6'],
        ['Studio Ghibli','Anime & Manga','🌿','Hand-drawn worlds of wonder and quiet magic.','#2f4a2f','#1a2e1a','#a8d5a8'],
        ['Naruto','Anime & Manga','🍥','Ninja villages, jutsu, and the will of fire.','#e65100','#3e1a00','#ffb74d'],
        ['Magic: The Gathering','Tabletop','🃏','Planeswalkers, mana, and deck-building strategy.','#3d1a5c','#1a0a2e','#b388ff'],
        ['Armor & Prop Building','Cosplay','🛠️','EVA foam, thermoplastics, and wearable fabrication.','#37474f','#1c2529','#90a4ae'],
        ['Wig Styling & Makeup','Cosplay','💄','Styling, coloring, and SFX makeup techniques.','#880e4f','#2e0a1c','#f48fb1'],
        ['Competitive Cosplay','Cosplay','🏆','Craftsmanship judging, stage presence, and contests.','#4a148c','#1a052e','#ce93d8'],
        ['Alien','Horror','👽','Xenomorphs, deep space, and cosmic dread.','#0a0a0a','#1a1a1a','#76ff03'],
        ['Silent Hill','Horror','🌫️','Fog, static, and psychological horror.','#2b2b2b','#141414','#b0bec5'],
    ];
    $subIds = [];
    foreach ($newSubs as [$name,$parent,$icon,$short,$p,$s,$a]) $subIds[$name] = seed_universe($name,$roots[$parent],$icon,$short,$p,$s,$a,$ownerId);
    $u = $roots + $subIds;

    $booths = [
        ['Panel Break Press','Independent comics and limited-run variants','Small-press publisher specializing in creator-owned indie comics.',$u['Comics'],[['Signed First Issue Bundle','Three signed indie #1s.',24.00,'PBP-BUNDLE-01',35],['Convention Exclusive Print','11x17 art print, numbered.',15.00,'PBP-PRINT-01',60]]],
        ['Variant Vault','Rare covers and collectible variants','Curated rare and variant comic covers for collectors.',$u['Comics'],[['Foil Variant Cover','Foil-stamped limited variant.',45.00,'VV-FOIL-01',20],['Sketch Cover Original','One-of-one sketch cover.',150.00,'VV-SKETCH-01',1]]],
        ['Ironclad Armory','Fantasy weapon and armor replicas','Foam and resin weapon replicas inspired by epic fantasy.',$u['Fantasy'],[['Display Longsword','Wall-mount foam longsword.',55.00,'IA-SWORD-01',25],['House Sigil Shield','Painted resin shield replica.',65.00,'IA-SHIELD-01',15]]],
        ['Dragonscale Apparel','Fantasy-inspired clothing and cloaks','Handmade cloaks, tunics, and fantasy streetwear.',$u['Game of Thrones'],[['Wool Traveler Cloak','Hooded wool cloak, water-resistant.',89.00,'DA-CLOAK-01',18],['House Crest Pin Set','Enamel pin set, 5 houses.',20.00,'DA-PIN-01',80]]],
        ['Star Freight Traders','Sci-fi model kits and starship replicas','Scale model kits and painted starship replicas.',$u['Sci-Fi'],[['Cruiser Model Kit','1:200 scale snap-fit kit.',42.00,'SFT-KIT-01',30],['Pilot Helmet Replica','Wearable display helmet.',95.00,'SFT-HELM-01',10]]],
        ['Chrono Circuit Electronics','Sci-fi gadgets and light-up props','LED and sound-reactive prop electronics.',$u['Sci-Fi'],[['Wrist Communicator Prop','Light-up wearable prop.',38.00,'CCE-COMM-01',40],['Blaster Sound Module Kit','DIY sound and light kit.',24.00,'CCE-KIT-01',50]]],
        ['Nebula Print Co.','Sci-fi concept art and posters','Giclee prints of original sci-fi concept art.',$u['Sci-Fi'],[['Deep Space Poster Set','Set of 3 giclee prints.',30.00,'NPC-SET-01',45],['Holo-Foil Star Chart','Foil print star chart.',22.00,'NPC-CHART-01',35]]],
        ['Respawn Merch Co.','Gaming apparel and accessories','Streetwear and accessories for gamers.',$u['Gaming'],[['Pixel Logo Hoodie','Embroidered pullover hoodie.',48.00,'RMC-HOOD-01',50],['Enamel Achievement Pins','Set of 4 collectible pins.',16.00,'RMC-PIN-01',70]]],
        ['Pixel Forge Peripherals','Custom controllers and keycaps','Hand-painted controllers and artisan keycaps.',$u['Final Fantasy'],[['Custom Painted Controller','Hand-painted themed controller.',85.00,'PFP-CTRL-01',12],['Artisan Keycap Set','Resin-cast keycap set of 4.',40.00,'PFP-KEY-01',30]]],
        ['Sakura Scanlations','Manga art prints and shikishi boards','Original and licensed manga-style art prints.',$u['Anime & Manga'],[['Shikishi Art Board','Hand-painted signature board.',35.00,'SS-BOARD-01',20],['Manga Panel Print Set','Set of 3 iconic panel prints.',18.00,'SS-PRINT-01',55]]],
        ['Kawaii Corner','Plushies and character accessories','Soft plushies and cute character accessories.',$u['Studio Ghibli'],[['Forest Spirit Plush','12in collector plush.',28.00,'KC-PLUSH-01',60],['Character Charm Set','Acrylic charm set of 5.',15.00,'KC-CHARM-01',80]]],
        ['Cel Shade Studio','Hand-painted animation cel art','Recreated and original animation cel artwork.',$u['Naruto'],[['Framed Cel Recreation','Hand-painted framed cel.',75.00,'CSS-CEL-01',10],['Village Symbol Print','Giclee print, numbered.',20.00,'CSS-PRINT-01',40]]],
        ['Dice Tower Trading Co.','Dice, trays, and tabletop accessories','Handmade resin dice and gaming accessories.',$u['Tabletop'],[['Nebula Resin Dice Set','7-piece polyhedral set.',32.00,'DTT-DICE-01',45],['Folding Dice Tray','Leatherette rolling tray.',18.00,'DTT-TRAY-01',35]]],
        ['Meeple Market','Board games new and used','Curated new and secondhand board games.',$u['Tabletop'],[['Strategy Game Bundle','3-game starter bundle.',60.00,'MM-BUNDLE-01',15],['Sleeved Card Set','Premium card sleeves, 100ct.',9.00,'MM-SLEEVE-01',90]]],
        ['Grimdark Miniatures','Hand-painted tabletop miniatures','Commission and pre-painted wargaming miniatures.',$u['Magic: The Gathering'],[['Painted Hero Miniature','Single hero mini, hand-painted.',35.00,'GM-MINI-01',20],['Planeswalker Playmat','Full-size neoprene playmat.',25.00,'GM-MAT-01',30]]],
        ['Thread & Thermoplastic','Costume fabric and build supplies','Fabric, thermoplastics, and cosplay build supplies.',$u['Armor & Prop Building'],[['EVA Foam Bundle','10-sheet foam bundle.',26.00,'TT-FOAM-01',50],['Worbla Sheet Pack','2-sheet thermoplastic pack.',34.00,'TT-WORBLA-01',30]]],
        ['Wig Wizardry','Cosplay wigs and styling supplies','Pre-styled wigs and styling tool kits.',$u['Wig Styling & Makeup'],[['Styled Character Wig','Heat-resistant styled wig.',55.00,'WW-WIG-01',25],['Wig Styling Tool Kit','Brush, comb, and spray set.',20.00,'WW-KIT-01',40]]],
        ['Con Armor Co.','Ready-to-paint armor kits','Pre-formed EVA foam armor kits, ready to finish.',$u['Competitive Cosplay'],[['Chestplate Armor Kit','Pre-formed foam chestplate.',70.00,'CAC-CHEST-01',18],['Gauntlet Pair Kit','Matched pair, pre-formed.',45.00,'CAC-GAUNT-01',22]]],
        ['Crypt Keeper Curiosities','Horror collectibles and oddities','Horror movie memorabilia and macabre collectibles.',$u['Horror'],[['Vinyl Figure — Classic Slasher','Collectible vinyl figure.',22.00,'CKC-FIG-01',30],['Prop Replica Mask','Screen-accurate display mask.',60.00,'CKC-MASK-01',12]]],
        ['Nightmare Fuel FX','Practical effects and makeup supplies','SFX makeup and prop-effect supplies for horror creators.',$u['Alien'],[['SFX Makeup Kit','Wound and creature FX kit.',38.00,'NFX-KIT-01',35],['Latex Prosthetic Set','Pre-made prosthetic set.',24.00,'NFX-LATEX-01',40]]],
    ];
    $boothIds = [];
    foreach ($booths as [$name,$tagline,$desc,$universeId,$products]) $boothIds[$name] = seed_booth($ownerId,$name,$tagline,$desc,$universeId,$products);

    $events = [
        ['Indie Comics Spotlight','New voices in independent comics','A showcase panel featuring emerging indie comic creators.','panel','virtual','+3 days 18:00',200,$u['Comics']],
        ['Variant Cover Signing','Meet the artists','A live signing session with variant cover artists.','signing','physical','+6 days 14:00',100,$u['Comics']],
        ['Comic Pitch Workshop','Pitch your series to publishers','A hands-on workshop for pitching original comic series.','workshop','virtual','+9 days 17:00',60,$u['Comics']],
        ['Worldbuilding 101','Build a world readers believe in','A workshop covering the fundamentals of fantasy worldbuilding.','workshop','virtual','+4 days 18:00',150,$u['Fantasy']],
        ['Fantasy Author Roundtable','Craft, myth, and magic systems','A panel discussion with fantasy authors on craft and myth.','panel','virtual','+8 days 19:00',180,$u['Game of Thrones']],
        ['First Contact: Sci-Fi Worldbuilding Panel','Building believable futures','A panel on constructing believable science fiction settings.','panel','virtual','+5 days 18:00',200,$u['Sci-Fi']],
        ['Retro Sci-Fi Movie Night','Classics on the big virtual screen','A community watch party for classic science fiction films.','watch_party','virtual','+10 days 20:00',250,$u['Sci-Fi']],
        ['Build a Blaster Prop Workshop','Foam and electronics basics','A hands-on workshop for building light-up prop blasters.','workshop','physical','+7 days 15:00',40,$u['Sci-Fi']],
        ['Indie Game Showcase','Play the next big thing early','A showcase of playable indie game demos.','showcase','virtual','+3 days 19:00',300,$u['Gaming']],
        ['Retro Arcade Tournament','Classic cabinets, high scores','A bracket tournament across classic arcade titles.','tournament','physical','+11 days 12:00',80,$u['Final Fantasy']],
        ['Manga Art Jam','Draw together, live','A collaborative live-drawing session for manga-style art.','workshop','virtual','+4 days 17:00',120,$u['Anime & Manga']],
        ['Anime Opening Trivia Night','Name that opening theme','A trivia competition testing anime opening-theme knowledge.','competition','virtual','+6 days 20:00',150,$u['Naruto']],
        ['Voice Acting Panel','Behind the mic','A panel with voice actors discussing the dubbing process.','panel','virtual','+9 days 18:00',180,$u['Studio Ghibli']],
        ['Learn to Play: Board Game Night','New to tabletop? Start here','A beginner-friendly night learning new board games.','meetup','physical','+5 days 18:00',50,$u['Tabletop']],
        ['D&D One-Shot: Lost Mines','A single-session adventure','A livestreamed one-shot Dungeons & Dragons adventure.','livestream','virtual','+8 days 19:00',300,$u['Magic: The Gathering']],
        ['Miniature Painting Workshop','Brushes, paints, and technique','A guided class on tabletop miniature painting basics.','class','physical','+12 days 13:00',30,$u['Tabletop']],
        ['Foam Armor Building 101','From flat foam to wearable armor','A workshop covering EVA foam armor fabrication basics.','workshop','physical','+6 days 14:00',45,$u['Armor & Prop Building']],
        ['Cosplay Contest','Craftsmanship and stage presence','A judged cosplay contest open to all skill levels.','cosplay_contest','physical','+10 days 16:00',200,$u['Competitive Cosplay']],
        ['Wig Styling Demo','Live styling walkthrough','A live demo covering wig styling and coloring techniques.','demo','virtual','+7 days 17:00',100,$u['Wig Styling & Makeup']],
        ['Midnight Horror Screening','A late-night double feature','A community watch party featuring a horror double feature.','screening','virtual','+9 days 23:00',200,$u['Horror']],
        ['Practical FX Makeup Workshop','Wounds, scars, and creature FX','A hands-on workshop covering practical horror makeup effects.','workshop','physical','+13 days 15:00',35,$u['Silent Hill']],
    ];
    $eventIds = [];
    foreach ($events as [$title,$subtitle,$desc,$type,$format,$startsIn,$capacity,$universeId]) $eventIds[$title] = seed_event($ownerId,$title,$subtitle,$desc,$type,$format,$startsIn,$capacity,$universeId);

    return ['universes'=>count($subIds),'booths'=>count($boothIds),'events'=>count($eventIds)];
}

$summary = null; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        db()->beginTransaction();
        $summary = seed_showcase_content((int)user()['id']);
        db()->commit();
        flash('success', 'Showcase content added: '.$summary['universes'].' worlds, '.$summary['booths'].' booths, '.$summary['events'].' events.');
        redirect('index.php');
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        $error = $e->getMessage();
    }
}
app_header('Seed Showcase Content');
?>
<section class="dashboard-hero"><p class="eyebrow">PRESENTATION PREP</p><h1>Seed Showcase Content</h1><p>Tops up every realm to roughly three worlds, three booths, and three events each — safe to run more than once, existing content is never duplicated.</p></section>
<?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif ?>
<section class="app-card"><form method="post"><?=csrf_field()?><button class="button primary" type="submit">Seed Showcase Content</button></form></section>
<?php app_footer();
