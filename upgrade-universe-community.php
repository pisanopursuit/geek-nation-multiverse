<?php
require __DIR__.'/includes/bootstrap.php';
require_admin();
$error='';
function column_exists_v44(string $table,string $column): bool {
    $s=db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $s->execute([$table,$column]); return (int)$s->fetchColumn()>0;
}
function install_universe_community_v44(): void {
    if(!universe_engine_ready()) throw new RuntimeException('Install Universe Engine V4 first.');
    run_sql_file(__DIR__.'/database/universe-billboards.sql');
    if(!column_exists_v44('universe_posts','link_url')) db()->exec("ALTER TABLE universe_posts ADD COLUMN link_url VARCHAR(2048) NULL AFTER body");
    if(!column_exists_v44('universe_posts','image_path')) db()->exec("ALTER TABLE universe_posts ADD COLUMN image_path VARCHAR(500) NULL AFTER link_url");
    run_sql_file(__DIR__.'/database/universe-community-v4.4.sql');
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{install_universe_community_v44();flash('success','Universe Community V4.4 installed successfully.');redirect('upgrade-universe-community.php?installed=1');}
    catch(Throwable $e){$error=$e->getMessage();}
}
$ready=false;try{db()->query('SELECT link_url,image_path FROM universe_posts LIMIT 1');db()->query('SELECT id FROM universe_chat_messages LIMIT 1');$ready=true;}catch(Throwable $e){}
app_header('Universe Community V4.4');
?><section class="dashboard-hero"><p class="eyebrow">DATABASE UPGRADE</p><h1>Universe Community V4.4</h1><p>Add owner/admin post deletion, billboard links and images, and live pop-out universe chat.</p></section>
<?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?>
<?php if($ready):?><div class="alert success">Universe Community V4.4 is installed.</div><p><a class="button primary" href="universe/index.php">Enter a Universe</a></p><?php endif?>
<section class="app-card"><p>This upgrade is safe to run more than once.</p><form method="post"><?=csrf_field()?><button class="button primary">Install Upgrade</button></form></section><?php app_footer();
