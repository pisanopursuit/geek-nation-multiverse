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

function seed_deep_worlds(int $ownerId): array {
    $parents = [];
    foreach (db()->query('SELECT id,name FROM universes WHERE parent_id IS NOT NULL') as $r) $parents[$r['name']] = (int)$r['id'];

    // [parentName, [ [childName, icon, short, primary, secondary, accent, boothName, boothTagline, boothDesc, [product1, product2], eventTitle, eventSubtitle, eventDesc, eventType, eventFormat, startsIn, capacity ], ... ] ]
    $tree = [
        'Anime' => [
            ['Attack on Titan','⚔️','Titans, walls, and the fight for freedom.','#3e2723','#1a0f0d','#c0a062', 'Scout Regiment Supply','Gear and pins for scouts','Cloaks, patches, and gear inspired by the Scout Regiment.',[['Wings of Freedom Patch','Embroidered cloak patch.',12.00,'SRS-PATCH-01',80],['ODM Gear Keychain','Metal keychain replica.',9.00,'SRS-KEY-01',90]], 'Titan Lore Deep Dive','Walls, secrets, and theories','A panel unpacking the deeper lore and theories.', 'panel','virtual','+6 days 18:00',150],
            ['Demon Slayer','🗡️','Breathing styles and the demon slayer corps.','#1a237e','#0d1240','#e91e63', 'Nichirin Blade Works','Replica blades and patches','Display blade replicas and corps-insignia patches.',[['Nichirin Blade Replica','Display-only blade replica.',48.00,'NBW-BLADE-01',20],['Corps Insignia Pin','Enamel corps pin.',10.00,'NBW-PIN-01',75]], 'Breathing Style Workshop','Choreography and form','A workshop covering choreographed breathing-style forms.', 'workshop','physical','+8 days 15:00',40],
            ['One Piece','🏴‍☠️','Pirates, the Grand Line, and one legendary treasure.','#01579b','#012a4a','#ffca28', 'Grand Line Traders','Flags, figures, and crew gear','Jolly Roger flags, figures, and crew-themed accessories.',[['Crew Jolly Roger Flag','Full-size fabric flag.',20.00,'GLT-FLAG-01',40],['Straw Hat Replica','Wearable straw hat replica.',25.00,'GLT-HAT-01',35]], 'One Piece Trivia Crew Battle','Form a crew, test your knowledge','A team trivia competition testing One Piece knowledge.', 'competition','virtual','+5 days 19:00',200],
        ],
        'DC' => [
            ['Batman','🦇','Gotham, gadgets, and the world\'s greatest detective.','#0d0d0d','#1a1a2e','#4fc3f7', 'Gotham Gear Co.','Gadgets and prop replicas','Utility-belt gadgets and Gotham-themed prop replicas.',[['Utility Belt Prop Set','Display belt with pouches.',65.00,'GGC-BELT-01',15],['Bat-Signal Desk Lamp','Miniature functioning lamp.',34.00,'GGC-LAMP-01',30]], 'Dark Knight Cosplay Panel','Building the cowl and cape','A panel covering armor, cowl, and cape construction.', 'panel','virtual','+7 days 18:00',180],
            ['Superman','🦸','Krypton, Metropolis, and the Man of Steel.','#0d47a1','#082860','#e53935', 'Daily Planet Press','Prints and collector pins','Metropolis-themed art prints and collector pin sets.',[['Daily Planet Front Page Print','Framed replica front page.',22.00,'DPP-PRINT-01',50],['House Crest Pin Set','Enamel crest pin set.',12.00,'DPP-PIN-01',70]], 'Man of Steel Trivia Night','Krypton to Metropolis','A trivia night spanning Superman comics and film.', 'competition','virtual','+9 days 19:00',150],
            ['Wonder Woman','🛡️','Themyscira, the Amazons, and the Lasso of Truth.','#8e0038','#3e0018','#ffd700', 'Themyscira Armory','Prop shields and tiaras','Amazonian-inspired prop shields, cuffs, and tiaras.',[['Amazonian Cuff Set','Wearable prop cuffs, pair.',30.00,'TA-CUFF-01',25],['Tiara Prop Replica','Display tiara replica.',28.00,'TA-TIARA-01',20]], 'Amazonian Warrior Workshop','Prop and armor building','A workshop on building Amazonian-style prop armor.', 'workshop','physical','+10 days 14:00',35],
        ],
        'Doctor Who' => [
            ['Daleks','🛸','Extermination, Skaro, and the deadliest force in the universe.','#4a0000','#1a0000','#ffb300', 'Skaro Salvage','Prop replicas and parts','Prop casings and detail parts for Dalek-inspired builds.',[['Dalek Eyestalk Prop','Display prop replica.',22.00,'SS-EYE-01',30],['Skaro Casing Panel Set','Detail panel set for builds.',35.00,'SS-PANEL-01',20]], 'Exterminate! Prop Building Panel','Casings, domes, and detail work','A panel on building Dalek-inspired prop casings.', 'workshop','virtual','+6 days 17:00',80],
            ['Cybermen','🤖','Conversion, upgrade, and the march of the Cybermen.','#37474f','#151b1f','#b0bec5', 'Mondas Machine Works','Costume parts and plating','Chestplates and helmet shells for Cyberman-inspired builds.',[['Chestplate Shell Kit','Pre-formed costume shell.',55.00,'MMW-CHEST-01',18],['Helmet Handle Set','3D-printed handle set.',18.00,'MMW-HANDLE-01',30]], 'Cyber-Conversion Makeup Demo','Metallic FX techniques','A live demo of metallic conversion-style makeup FX.', 'demo','virtual','+8 days 18:00',100],
            ['The Time Lords','⏳','Gallifrey, regeneration, and the last of the Time Lords.','#1a237e','#0a0f40','#ffd54f', 'Gallifrey Archives','Sonic screwdriver replicas','Sonic screwdriver replicas and Gallifreyan-script prints.',[['Sonic Screwdriver Replica','Light-up display replica.',38.00,'GA-SONIC-01',25],['Gallifreyan Script Print','Circular Gallifreyan art print.',16.00,'GA-PRINT-01',45]], 'Regeneration Lore Panel','Thirteen faces, one hero','A panel discussing the lore of regeneration across eras.', 'panel','virtual','+11 days 19:00',160],
        ],
        'Dungeons & Dragons' => [
            ['Forgotten Realms','🗺️','Faerûn, the Sword Coast, and a thousand campaigns.','#3e2723','#1a0f0d','#8bc34a', 'Candlekeep Curiosities','Dice, maps, and campaign gear','Hand-drawn campaign maps and Faerûn-themed dice sets.',[['Sword Coast Campaign Map','Hand-drawn fold-out map.',18.00,'CC-MAP-01',40],['Faerûn Resin Dice Set','7-piece themed dice set.',28.00,'CC-DICE-01',35]], 'Faerûn Worldbuilding Panel','Building campaigns that last','A panel on running long-form Forgotten Realms campaigns.', 'panel','virtual','+5 days 18:00',140],
            ['Ravenloft','🕯️','Gothic horror, mists, and the domains of dread.','#2e0f0f','#140505','#8b1a1a', 'Barovia Trading Post','Gothic minis and terrain','Hand-painted gothic-themed miniatures and terrain pieces.',[['Gothic Manor Terrain Piece','Painted resin terrain.',40.00,'BTP-TERRAIN-01',15],['Vampire Lord Miniature','Hand-painted mini.',32.00,'BTP-MINI-01',20]], 'Curse of Strahd One-Shot','A single night in Barovia','A livestreamed one-shot set in the mists of Ravenloft.', 'livestream','virtual','+9 days 20:00',250],
            ['Eberron','⚙️','Airships, dragonmarks, and a world after war.','#4e342e','#241512','#ff8f00', 'Sharn Skyworks','Steampunk props and gear','Steampunk-inspired goggles, gear, and dragonmark tattoos.',[['Skyship Goggles Prop','Steampunk-style prop goggles.',26.00,'SS-GOGGLE-01',30],['Dragonmark Temp Tattoo Set','Set of 6 temporary tattoos.',8.00,'SS-MARK-01',100]], 'Dragonmarked Houses Panel','Politics, power, and airships','A panel exploring Eberron\'s dragonmarked houses.', 'panel','virtual','+12 days 18:00',120],
        ],
        'Indie Comics' => [
            ['Saga','🌌','Star-crossed love across a galaxy at war.','#4a148c','#1a052e','#ff6f91', 'Wreath & Landfall Prints','Fine art prints','Giclee art prints inspired by independent space-fantasy comics.',[['Star-Crossed Print Set','Set of 3 giclee prints.',26.00,'WLP-SET-01',35],['Wing Motif Enamel Pin','Collector enamel pin.',10.00,'WLP-PIN-01',60]], 'Saga Fan Art Showcase','Community art, live judged','A showcase of fan art inspired by independent space-fantasy comics.', 'showcase','virtual','+7 days 19:00',130],
            ['Invincible','💥','Super-powered legacy, brutal stakes.','#1565c0','#0a2f5c','#ffca28', 'Global Defense Merch','Hero-themed apparel','Apparel and accessories inspired by independent superhero comics.',[['Hero Logo Tee','Screen-printed cotton tee.',24.00,'GDM-TEE-01',55],['Defense Agency Cap','Embroidered cap.',18.00,'GDM-CAP-01',45]], 'Invincible Watch Party','Season one, together','A community watch party for the animated adaptation.', 'watch_party','virtual','+10 days 20:00',220],
            ['Spawn','🔥','Hellspawn, vengeance, and the dark corners of comics.','#212121','#0a0a0a','#c62828', 'Hellspawn Collectibles','Dark comics figures','Figures and collectibles inspired by dark independent comics.',[['Cursed Cape Figure','6in collectible figure.',30.00,'HC-FIG-01',25],['Dark Comics Poster Set','Set of 2 art posters.',20.00,'HC-POSTER-01',40]], 'Dark Comics Art Panel','Shadows, ink, and independent voices','A panel on the art and legacy of dark independent comics.', 'panel','virtual','+13 days 18:00',110],
        ],
        'Marvel' => [
            ['Spider-Man','🕸️','Friendly neighborhood hero, web-slinging through the city.','#b71c1c','#4a0000','#1565c0', 'Web-Slinger Supply Co.','Masks and web shooters','Wearable masks and prop web-shooters.',[['Wearable Spider Mask','Breathable fabric mask.',32.00,'WSC-MASK-01',30],['Prop Web Shooter Set','Wrist-mounted prop pair.',26.00,'WSC-SHOOTER-01',40]], 'Spidey Suit-Building Workshop','From sketch to spandex','A workshop covering suit pattern-making and paint.', 'workshop','physical','+6 days 15:00',35],
            ['X-Men','🧬','Mutants, found family, and the fight for a better world.','#212121','#0a0a0a','#ffd600', 'Mutant Underground Merch','Pins and apparel','Team-themed pins and apparel for mutant fans.',[['Team Roster Pin Set','Set of 6 collector pins.',18.00,'MUM-PIN-01',50],['Academy Crest Hoodie','Embroidered pullover hoodie.',50.00,'MUM-HOOD-01',30]], 'X-Men Costume Panel','Uniforms across the decades','A panel tracing X-Men costume design through the eras.', 'panel','virtual','+8 days 18:00',170],
            ['Avengers','🛡️','Earth\'s mightiest heroes, assembled.','#8e0000','#3a0000','#c0c0c0', 'Stark Industries Outpost','Prop gauntlets and gear','Display gauntlets and hero-themed prop gear.',[['Display Gauntlet Prop','Light-up display gauntlet.',75.00,'SIO-GAUNT-01',12],['Team Emblem Keychain Set','Set of 6 metal keychains.',15.00,'SIO-KEY-01',65]], 'Assemble! Trivia Night','Earth\'s mightiest questions','A trivia night covering decades of team history.', 'competition','virtual','+11 days 19:00',200],
        ],
        'Pokémon' => [
            ['Kanto Region','🔴','Where it all began — Pallet Town to the Indigo Plateau.','#c62828','#5c0000','#ffcb05', 'Kanto Trainer Supply','Plush and trading cards','Starter-themed plush and trading card singles.',[['Starter Trio Plush Set','Set of 3 mini plush.',36.00,'KTS-PLUSH-01',30],['Booster Pack Bundle','5-pack card bundle.',20.00,'KTS-CARDS-01',60]], 'Kanto Speedrun Night','Pallet Town to the Elite Four','A livestreamed speedrun through the original region.', 'livestream','virtual','+5 days 20:00',180],
            ['Johto Region','🟡','New Bark Town, day and night, and a second journey.','#f9a825','#5c3d00','#43a047', 'Johto Berry Traders','Plush and pin sets','Region-themed plush and enamel pin sets.',[['Region Starter Pin Set','Set of 3 enamel pins.',14.00,'JBT-PIN-01',55],['Berry Pouch Plush Charm','Collectible plush charm.',10.00,'JBT-CHARM-01',70]], 'Johto Nostalgia Panel','A second generation, revisited','A panel celebrating the Johto region and its legacy.', 'panel','virtual','+9 days 18:00',140],
            ['Galar Region','⚪','Stadiums, Dynamax, and the United Kingdom-inspired region.','#37474f','#151b1f','#e91e63', 'Galar Gym Outfitters','Gym-themed apparel','Stadium and gym-badge inspired apparel.',[['Gym Badge Pin Set','Set of 8 collector badges.',22.00,'GGO-BADGE-01',45],['Stadium Scarf','Knit stadium scarf.',24.00,'GGO-SCARF-01',35]], 'Dynamax Battle Night','Gigantamax showdowns','A community battle tournament night.', 'tournament','virtual','+7 days 19:00',150],
        ],
        'Star Trek' => [
            ['The Next Generation','🖖','A new crew, a new century, boldly going.','#0d47a1','#062350','#c0c0c0', 'Enterprise-D Commissary','Comm badges and pips','Wearable comm badge and rank pip replicas.',[['Comm Badge Replica','Magnetic-back badge replica.',20.00,'EDC-BADGE-01',50],['Rank Pip Set','Set of 5 rank pips.',12.00,'EDC-PIP-01',60]], 'TNG Rewatch Panel','Picard, Data, and the Prime Directive','A panel revisiting favorite episodes and themes.', 'panel','virtual','+6 days 18:00',160],
            ['Deep Space Nine','🌌','A station on the frontier, and a war that changes everything.','#4a148c','#1c0533','#ffab00', 'Quark\'s Trading Post','Station-themed props','Bar and station-themed prop replicas and accessories.',[['Station Ops Padd Prop','Display prop replica.',28.00,'QTP-PADD-01',25],['Bajoran Earring Replica','Wearable prop earring.',15.00,'QTP-EARRING-01',35]], 'DS9 Deep Dive Panel','War, faith, and the frontier','A panel exploring the station\'s serialized storytelling.', 'panel','virtual','+10 days 19:00',130],
            ['Voyager','🚀','Lost in the Delta Quadrant, finding a way home.','#00695c','#00332c','#ffd54f', 'Delta Quadrant Outfitters','Patches and pins','Mission patches and Delta Quadrant-themed pins.',[['Mission Patch Set','Set of 4 embroidered patches.',16.00,'DQO-PATCH-01',45],['Delta Flyer Pin','Collector enamel pin.',9.00,'DQO-PIN-01',65]], 'Voyager Watch Party','Seven years, one long way home','A community watch party for fan-favorite episodes.', 'watch_party','virtual','+12 days 20:00',190],
        ],
        'Star Wars' => [
            ['The Rebellion','✊','Hope against the Empire, one squadron at a time.','#8e0000','#3a0000','#ffd700', 'Alliance Supply Depot','Patches and pilot gear','Squadron patches and pilot-themed prop gear.',[['Squadron Patch Set','Set of 4 embroidered patches.',16.00,'ASD-PATCH-01',50],['Pilot Helmet Prop','Display helmet replica.',95.00,'ASD-HELM-01',10]], 'Rebel Pilot Prop Workshop','Helmets, flight suits, and detail work','A workshop on building rebel pilot prop gear.', 'workshop','physical','+8 days 15:00',30],
            ['The Mandalorian','🪖','This is the way — bounty hunters and beskar steel.','#37474f','#151b1f','#ffb300', 'Beskar Forge','Armor pieces and props','Armor plating and prop weathering supplies.',[['Beskar Chestplate Prop','Display armor piece.',85.00,'BF-CHEST-01',12],['Weathering Powder Kit','Prop-aging powder set.',14.00,'BF-WEATHER-01',40]], 'This Is the Way: Armor Panel','Building beskar from scratch','A panel covering Mandalorian armor fabrication.', 'panel','virtual','+6 days 18:00',175],
            ['The Clone Wars','⚔️','Clones, Jedi generals, and a galaxy at war.','#1a237e','#0a0f40','#4fc3f7', 'Republic Arsenal Replicas','Trooper armor and props','Clone trooper armor panels and prop blasters.',[['Trooper Armor Panel Set','Detail panel set for builds.',48.00,'RAR-PANEL-01',18],['Prop Blaster Replica','Display blaster replica.',60.00,'RAR-BLASTER-01',15]], 'Clone Trooper Armor Build','From flat kit to full armor','A hands-on workshop building clone trooper armor pieces.', 'workshop','physical','+9 days 14:00',25],
        ],
        'The Lord of the Rings' => [
            ['The Shire','🌳','Rolling hills, round doors, and second breakfast.','#33691e','#1a3608','#ffb300', 'Bag End Provisions','Hobbit-themed props and gear','Second-breakfast-themed props and hobbit-inspired accessories.',[['Round Door Prop Sign','Painted wood door sign.',22.00,'BEP-SIGN-01',30],['Traveler\'s Pipe Prop','Display prop pipe.',14.00,'BEP-PIPE-01',45]], 'Hobbit Feast Meetup','Second breakfast, together','A community meetup celebrating hobbit hospitality and food.', 'meetup','physical','+5 days 12:00',60],
            ['Rohan','🐎','Horse-lords, golden halls, and the plains of Rohan.','#5d4037','#2b2018','#ffc107', 'Edoras Smithy','Prop weapons and shields','Prop spears, shields, and horse-lord accessories.',[['Rohirrim Shield Replica','Painted display shield.',58.00,'ES-SHIELD-01',15],['Horse-Lord Cloak Pin','Collector cloak pin.',11.00,'ES-PIN-01',50]], 'Riders of Rohan Panel','Horses, halls, and heroism','A panel on the culture and craft behind Rohan.', 'panel','virtual','+10 days 18:00',120],
            ['Gondor','🏰','The White City, the White Tree, and the return of the king.','#455a64','#1c262b','#eceff1', 'White Tree Armory','Banners and prop armor','Banners, prop swords, and Gondorian-themed decor.',[['White Tree Banner','Full-size fabric banner.',30.00,'WTA-BANNER-01',25],['Guard Helm Display Prop','Display helm replica.',68.00,'WTA-HELM-01',10]], 'Return of the King Watch Party','The steward, the king, the city','A community watch party for the trilogy\'s finale.', 'watch_party','virtual','+13 days 20:00',210],
        ],
        'Warhammer' => [
            ['Warhammer 40,000','☠️','War without end, in the grim darkness of the far future.','#212121','#0a0a0a','#c62828', 'Imperium Forge','Painted miniatures','Hand-painted 40K miniatures and army accessories.',[['Space Marine Squad Set','5 hand-painted minis.',65.00,'IF-SQUAD-01',15],['Army Transfer Sheet','Decal transfer sheet.',10.00,'IF-DECAL-01',60]], '40K Battle Night','Bring your army, roll for war','A community battle night for 40K armies of all sizes.', 'tournament','physical','+7 days 17:00',40],
            ['Age of Sigmar','⚡','Realms of magic, order, and endless war.','#4a148c','#1c0533','#ffd54f', 'Mortal Realms Miniatures','Fantasy battle minis','Hand-painted Age of Sigmar miniatures.',[['Realm Champion Miniature','Single hero mini, painted.',30.00,'MRM-MINI-01',20],['Battle Roster Notepad','Army-list notepad.',8.00,'MRM-PAD-01',50]], 'Age of Sigmar Painting Class','Basecoat to battle-ready','A guided class on painting fantasy battle miniatures.', 'class','physical','+11 days 13:00',25],
            ['Warhammer Fantasy','🏹','The Old World, before the end times.','#2e7d32','#123016','#8d6e63', 'Old World Traders','Minis and terrain pieces','Classic-era miniatures and modular terrain pieces.',[['Old World Terrain Set','Modular resin terrain, 3pc.',45.00,'OWT-TERRAIN-01',12],['Classic Army Booklet','Reprinted army reference.',18.00,'OWT-BOOK-01',30]], 'Old World Lore Panel','Before the end times','A panel on the history and lore of the Old World.', 'panel','virtual','+9 days 18:00',110],
        ],
        'Silent Hill' => [
            ['Silent Hill 2','🌁','A letter, a town, and a truth James isn\'t ready for.','#37474f','#151b1f','#b0bec5', 'Foggy Town Curiosities','Prop decor and oddities','Fog-town-inspired prop decor and eerie collectibles.',[['Rusted Radio Prop','Static-effect display prop.',32.00,'FTC-RADIO-01',20],['Foggy Postcard Set','Set of 4 art postcards.',10.00,'FTC-CARD-01',50]], 'Silent Hill 2 Retrospective Panel','Guilt, grief, and the fog','A panel examining the game\'s psychological themes.', 'panel','virtual','+8 days 20:00',130],
            ['Silent Hill 3','🎭','A daughter, a cult, and a town that won\'t let go.','#4a148c','#1c0533','#c62828', 'Otherworld Oddities','Masks and prop decor','Screen-inspired masks and unsettling prop decor.',[['Nurse Mask Replica','Display mask replica.',36.00,'OO-MASK-01',15],['Cult Symbol Pendant','Prop pendant on cord.',14.00,'OO-PENDANT-01',35]], 'Silent Hill 3 Screening','A late-night descent','A community watch party for the game\'s cutscenes and lore.', 'screening','virtual','+12 days 23:00',140],
            ['The Otherworld','🌫️','Rust, static, and a reality gone wrong.','#212121','#0a0a0a','#8b1a1a', 'Rusted Relics','Prop decor and set pieces','Rust-and-static-themed prop decor for horror sets.',[['Rusted Chain-Link Prop','Wall-mount decor prop.',26.00,'RR-CHAIN-01',20],['Static TV Prop Sticker Set','Vinyl decor sticker set.',12.00,'RR-STICKER-01',45]], 'Psychological Horror Panel','Fear without a monster','A panel on horror that comes from dread, not jumpscares.', 'panel','virtual','+14 days 19:00',150],
        ],
    ];

    $childCount = 0; $boothCount = 0; $eventCount = 0;
    foreach ($tree as $parentName => $children) {
        if (!isset($parents[$parentName])) continue;
        $parentId = $parents[$parentName];
        foreach ($children as [$name,$icon,$short,$primary,$secondary,$accent,$boothName,$boothTagline,$boothDesc,$products,$eventTitle,$eventSubtitle,$eventDesc,$eventType,$eventFormat,$startsIn,$capacity]) {
            $childId = seed_universe($name,$parentId,$icon,$short,$primary,$secondary,$accent,$ownerId);
            $childCount++;
            seed_booth($ownerId,$boothName,$boothTagline,$boothDesc,$childId,$products);
            $boothCount++;
            seed_event($ownerId,$eventTitle,$eventSubtitle,$eventDesc,$eventType,$eventFormat,$startsIn,$capacity,$childId);
            $eventCount++;
        }
    }
    return ['worlds'=>$childCount,'booths'=>$boothCount,'events'=>$eventCount];
}

$summary = null; $deep = null; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        db()->beginTransaction();
        $summary = seed_showcase_content((int)user()['id']);
        $deep = seed_deep_worlds((int)user()['id']);
        db()->commit();
        flash('success', 'Showcase content added: '.$summary['universes'].' worlds, '.$summary['booths'].' booths, '.$summary['events'].' events (plus '.$deep['worlds'].' deeper worlds under previously-empty universes).');
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
