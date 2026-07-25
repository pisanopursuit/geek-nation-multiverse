<?php
require __DIR__.'/includes/bootstrap.php';
require_admin();
$error=null;$done=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  try{run_sql_file(GNM_ROOT.'/database/artist-alley-v7.sql');$done=true;flash('success','Artist Alley was installed successfully.');redirect('artist-alley/index.php');}
  catch(Throwable $e){$error=$e->getMessage();}
}
app_header('Install Artist Alley');
?>
<section class="form-card"><p class="eyebrow">VERSION 7 UPGRADE</p><h1>Install Artist Alley</h1><p>Add artist profiles, portfolios, commission services, commission requests, followers, and approval tools.</p><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?><form method="post"><?=csrf_field()?><button class="button primary" type="submit">Install Artist Alley</button></form></section>
<?php app_footer();
