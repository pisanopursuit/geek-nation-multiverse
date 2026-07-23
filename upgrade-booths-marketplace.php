<?php
require __DIR__.'/includes/bootstrap.php';require_admin();
$error=null;$done=false;
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();try{run_sql_file(__DIR__.'/database/booths-marketplace.sql');$done=true;}catch(Throwable $e){$error=$e->getMessage();}}
app_header('Install Booths & Marketplace');?>
<section class="form-card narrow"><p class="eyebrow">VERSION 5</p><h1>Booths & Marketplace Foundation</h1>
<?php if($done):?><div class="alert success">Upgrade completed successfully.</div><p>Booths, products, carts, demo checkout, orders, and approval tools are ready.</p><p><a class="button primary" href="booth/index.php">Browse Booths</a> <a class="button ghost" href="admin/booths.php">Manage Booths</a></p>
<?php else:?><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?><p>This repeatable upgrade adds storefront-ready booths without enabling live payment processing.</p><form method="post"><?=csrf_field()?><button class="button primary">Install Version 5</button></form><?php endif?></section><?php app_footer();