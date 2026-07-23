<?php
require __DIR__.'/includes/bootstrap.php';
require_admin();
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        run_sql_file(__DIR__.'/database/developer-center-v5.2.sql');
        flash('success','Version 5.2 Developer Center installed successfully.');
        redirect('admin/developer-center.php');
    }catch(Throwable $e){$error=$e->getMessage();}
}
$ready=false;
try{db()->query('SELECT 1 FROM developer_demo_batches LIMIT 1');$ready=true;}catch(Throwable $e){}
app_header('Developer Center V5.2');
?>
<section class="dashboard-hero"><p class="eyebrow">DATABASE UPGRADE</p><h1>Developer Center V5.2</h1><p>Install test labs, diagnostics, demo batch tracking, and safe cleanup tools for every completed module.</p></section>
<?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?>
<?php if($ready):?><div class="alert success">The Developer Center is installed and ready.</div><p><a class="button primary" href="admin/developer-center.php">Open Developer Center</a></p><?php endif?>
<section class="app-card"><p>This upgrade is safe to run more than once. It does not create demo content until you choose a Test Lab action.</p><form method="post"><?=csrf_field()?><button class="button primary" type="submit">Install Version 5.2</button></form></section>
<?php app_footer();
