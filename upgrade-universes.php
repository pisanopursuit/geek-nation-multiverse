<?php
require __DIR__.'/includes/bootstrap.php';
require_admin();
$error='';$installed=false;

function gn_column_exists(string $table,string $column): bool {
    $s=db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $s->execute([$table,$column]);return (int)$s->fetchColumn()>0;
}
function gn_index_exists(string $table,string $index): bool {
    $s=db()->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?");
    $s->execute([$table,$index]);return (int)$s->fetchColumn()>0;
}
function install_universe_engine(): void {
    if(!identity_schema_ready()) throw new RuntimeException('Install the User Identity upgrade first.');
    $columns=[
      'parent_id'=>"BIGINT UNSIGNED NULL AFTER id",
      'description'=>"TEXT NULL AFTER icon",
      'short_description'=>"VARCHAR(255) NULL AFTER description",
      'logo_path'=>"VARCHAR(255) NULL AFTER short_description",
      'banner_path'=>"VARCHAR(255) NULL AFTER logo_path",
      'visibility'=>"ENUM('public','members','private') NOT NULL DEFAULT 'public' AFTER banner_path",
      'status'=>"ENUM('draft','pending','approved','suspended') NOT NULL DEFAULT 'approved' AFTER visibility",
      'is_featured'=>"TINYINT(1) NOT NULL DEFAULT 0 AFTER status",
      'primary_color'=>"VARCHAR(20) NOT NULL DEFAULT '#6f4cff' AFTER is_featured",
      'secondary_color'=>"VARCHAR(20) NOT NULL DEFAULT '#15172a' AFTER primary_color",
      'accent_color'=>"VARCHAR(20) NOT NULL DEFAULT '#27d7ff' AFTER secondary_color",
      'background_color'=>"VARCHAR(20) NOT NULL DEFAULT '#070812' AFTER accent_color",
      'surface_color'=>"VARCHAR(20) NOT NULL DEFAULT '#111321' AFTER background_color",
      'text_color'=>"VARCHAR(20) NOT NULL DEFAULT '#f7f7fb' AFTER surface_color",
      'display_font'=>"VARCHAR(120) NULL AFTER text_color",
      'body_font'=>"VARCHAR(120) NULL AFTER display_font",
      'texture_style'=>"VARCHAR(80) NULL AFTER body_font",
      'icon_style'=>"VARCHAR(80) NULL AFTER texture_style",
      'imagery_treatment'=>"VARCHAR(120) NULL AFTER icon_style",
      'created_by'=>"BIGINT UNSIGNED NULL AFTER sort_order",
      'created_at'=>"DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_by",
      'updated_at'=>"DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
    ];
    foreach($columns as $name=>$definition) if(!gn_column_exists('universes',$name)) db()->exec("ALTER TABLE universes ADD COLUMN {$name} {$definition}");
    if(!gn_index_exists('universes','idx_universe_parent')) db()->exec('ALTER TABLE universes ADD INDEX idx_universe_parent(parent_id)');
    if(!gn_index_exists('universes','idx_universe_status')) db()->exec('ALTER TABLE universes ADD INDEX idx_universe_status(status,is_active,sort_order)');
    run_sql_file(__DIR__.'/database/universe-engine.sql');
    if(!gn_column_exists('user_universes','joined_at')) db()->exec('ALTER TABLE user_universes ADD COLUMN joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

    $roots=[
      ['Comics','comics','💥','Heroes, indies, and graphic novels','#7c21f3','#ef3b46','#ffcf36'],
      ['Fantasy','fantasy','🗡️','Magic, quests, myths, and worlds','#117b59','#43a65d','#efc75e'],
      ['Sci-Fi','sci-fi','🚀','Space, cyberpunk, robots, and futures','#1264b7','#08bde0','#ff5f56'],
      ['Gaming','gaming','👾','Console, PC, retro, and indie','#4322a6','#8246f4','#43e3ff'],
      ['Anime & Manga','anime-manga','🌸','Series, studios, art, and cosplay','#bd2365','#ef6d9d','#ffe26f'],
      ['Tabletop','tabletop','🎲','RPGs, cards, minis, and board games','#ec5b00','#ffb000','#713eff'],
      ['Horror','horror','🧟','Monsters, paranormal, and slashers','#650505','#bb1616','#f1d5d5'],
      ['Cosplay','cosplay','🦸','Armor, props, wigs, and performance','#075d55','#2ca89d','#f6bf4b']
    ];
    $insert=db()->prepare("INSERT INTO universes(name,slug,icon,short_description,status,is_active,is_featured,primary_color,secondary_color,accent_color,sort_order) VALUES(?,?,?,?, 'approved',1,1,?,?,?,?) ON DUPLICATE KEY UPDATE short_description=VALUES(short_description),is_featured=1");
    foreach($roots as $i=>$r)$insert->execute([$r[0],$r[1],$r[2],$r[3],$r[4],$r[5],$r[6],($i+1)*10]);
    $parentMap=['Marvel'=>'comics','DC'=>'comics','Indie Comics'=>'comics','Star Wars'=>'sci-fi','Star Trek'=>'sci-fi','Doctor Who'=>'sci-fi','Dungeons & Dragons'=>'tabletop','The Lord of the Rings'=>'fantasy','Warhammer'=>'tabletop','Pokémon'=>'gaming','Anime'=>'anime-manga'];
    $findRoot=db()->prepare('SELECT id FROM universes WHERE slug=? LIMIT 1');$setParent=db()->prepare('UPDATE universes SET parent_id=? WHERE name=? AND parent_id IS NULL');
    foreach($parentMap as $child=>$rootSlug){$findRoot->execute([$rootSlug]);$rootId=$findRoot->fetchColumn();if($rootId)$setParent->execute([(int)$rootId,$child]);}
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{install_universe_engine();$installed=true;flash('success','Version 4 Universe Engine installed successfully.');redirect('upgrade-universes.php?installed=1');}catch(Throwable $e){$error=$e->getMessage();}
}
$ready=universe_engine_ready();$installed=$installed||isset($_GET['installed']);app_header('Universe Engine V4');
?><section class="dashboard-hero"><p class="eyebrow">DATABASE UPGRADE</p><h1>Universe Engine V4</h1><p>Install hierarchical universes, user memberships, public profiles, and the Shell + Skin theme engine.</p></section><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?><?php if($ready):?><div class="alert success">The Universe Engine is installed and ready.</div><p><a class="button primary" href="universe/index.php">Enter a Universe</a> <a class="button ghost" href="admin/universes.php">Manage Universes</a></p><?php endif?><section class="app-card"><p>This upgrade is safe to run more than once.</p><form method="post"><?=csrf_field()?><button class="button primary">Install Upgrade</button></form></section><?php app_footer();
