<?php
require __DIR__.'/includes/bootstrap.php';
require_admin();
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  run_sql_file(__DIR__.'/database/identity.sql');
  run_sql_file(__DIR__.'/database/invitations.sql');
  ensure_profile((int)user()['id']);
  flash('success','User Identity and Invitations were installed successfully.');
  redirect('admin/invitations.php');
 } catch(Throwable $e){$error=$e->getMessage();}
}
app_header('Upgrade');
?><section class="auth-page"><form class="auth-card" method="post"><?=csrf_field()?><p class="eyebrow">DATABASE UPGRADE</p><h1>Install Platform Features</h1><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?><p>This safely adds profiles, onboarding, identities, interests, universes, social links, notification preferences, and secure member/admin invitations. Existing users and authentication data are preserved.</p><button class="button primary">Run Upgrade</button></form></section><?php app_footer();
