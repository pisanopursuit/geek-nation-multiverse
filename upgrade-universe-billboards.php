<?php
require __DIR__.'/includes/bootstrap.php';
require_admin();
$error='';
function install_universe_billboards(): void {
    if(!universe_engine_ready()) throw new RuntimeException('Install Universe Engine V4 first.');
    run_sql_file(__DIR__.'/database/universe-billboards.sql');
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{install_universe_billboards();flash('success','Universe Engine V4.3 installed successfully.');redirect('upgrade-universe-billboards.php?installed=1');}
    catch(Throwable $e){$error=$e->getMessage();}
}
$ready=false;try{db()->query('SELECT parent_post_id FROM universe_posts LIMIT 1');$ready=true;}catch(Throwable $e){}
app_header('Universe Engine V4.3');
?><section class="dashboard-hero"><p class="eyebrow">DATABASE UPGRADE</p><h1>Universe Engine V4.3</h1><p>Add clearer sub-universe creation and a conversation billboard inside every universe.</p></section>
<?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?>
<?php if($ready):?><div class="alert success">Sub-universe tools and Universe Billboards are installed.</div><p><a class="button primary" href="universe/index.php">Enter a Universe</a> <a class="button ghost" href="admin/universes.php">Manage Universes</a></p><?php endif?>
<section class="app-card"><p>This upgrade is safe to run more than once.</p><form method="post"><?=csrf_field()?><button class="button primary">Install Upgrade</button></form></section><?php app_footer();
