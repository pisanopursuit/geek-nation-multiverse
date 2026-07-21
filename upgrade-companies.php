<?php
require __DIR__.'/includes/bootstrap.php';
require_admin();
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  run_sql_file(__DIR__.'/database/companies.sql');
  flash('success','Companies were installed successfully. Verified members can now submit company profiles for approval.');
  redirect('admin/companies.php');
 }catch(Throwable $e){$error=$e->getMessage();}
}
app_header('Install Companies');
?><section class="auth-page"><form class="auth-card" method="post"><?=csrf_field()?><p class="eyebrow">DATABASE UPGRADE</p><h1>Install Companies</h1><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?><p>This safely adds company profiles, company memberships, declared positions, fan submissions, approval history, and administrator review tools. Existing users and profiles are preserved.</p><button class="button primary">Run Companies Upgrade</button></form></section><?php app_footer();
