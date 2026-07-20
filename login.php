<?php
require __DIR__.'/includes/bootstrap.php';
if(user()) redirect(identity_schema_ready() && onboarding_required() ? 'onboarding.php' : 'dashboard.php'); $error='';
if(isset($_GET['installed'])) flash('success','Installation complete. Sign in with the administrator account.');
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $id=strtolower(trim($_POST['identifier']??'')); $ip=$_SERVER['REMOTE_ADDR']??'unknown';
 $q=db()->prepare("SELECT COUNT(*) FROM login_attempts WHERE identifier=? AND ip_address=? AND successful=0 AND attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)");$q->execute([$id,$ip]);
 if((int)$q->fetchColumn()>=5){$error='Too many failed attempts. Try again in 15 minutes.';}else{
  $s=db()->prepare('SELECT * FROM users WHERE email=? OR username=? LIMIT 1');$s->execute([$id,$id]);$u=$s->fetch();$ok=$u&&password_verify($_POST['password']??'',$u['password_hash']);
  db()->prepare('INSERT INTO login_attempts(identifier,ip_address,successful) VALUES(?,?,?)')->execute([$id,$ip,$ok?1:0]);
  if(!$ok)$error='The email, username, or password is incorrect.'; elseif($u['status']==='pending_email')$error='Verify your email before signing in.'; elseif($u['status']!=='active')$error='This account is not active.'; else{session_regenerate_id(true);$_SESSION['user_id']=$u['id'];db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$u['id']]);redirect(identity_schema_ready() && onboarding_required($u) ? 'onboarding.php' : 'dashboard.php');}
 }
}
app_header('Sign In');?><section class="auth-page"><form class="auth-card" method="post"><?=csrf_field()?><p class="eyebrow">WELCOME BACK</p><h1>Sign in</h1><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?><label>Email or username<input name="identifier" required autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="button primary" type="submit">Enter the Multiverse</button><p><a href="forgot-password.php">Forgot password?</a></p><p>New here? <a href="register.php">Create an account</a></p></form></section><?php app_footer();
