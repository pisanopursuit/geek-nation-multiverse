<?php
require __DIR__.'/includes/bootstrap.php';
$token=trim($_GET['token']??$_POST['token']??'');
$invite=invitation_by_token($token);
$errors=[];
if(user()){
 app_header('Invitation');
 ?><section class="auth-page"><div class="auth-card"><p class="eyebrow">INVITATION</p><h1>You are already signed in</h1><p>Sign out before accepting an invitation for a new account.</p><a class="button primary" href="logout.php">Sign Out</a></div></section><?php app_footer();exit;
}
if(!$invite || $invite['status']!=='pending' || strtotime($invite['expires_at'])<time()){
 app_header('Invitation Unavailable');
 ?><section class="auth-page"><div class="auth-card"><p class="eyebrow">INVITATION</p><h1>This invitation is unavailable</h1><p>It may have expired, been revoked, already been accepted, or the link may be invalid.</p><a class="button primary" href="register.php">Create a Member Account</a></div></section><?php app_footer();exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $name=trim($_POST['display_name']??'');$username=strtolower(trim($_POST['username']??''));$password=$_POST['password']??'';$confirm=$_POST['password_confirm']??'';
 if($name==='')$errors[]='Enter your display name.';
 if(!preg_match('/^[a-z0-9_]{3,30}$/',$username))$errors[]='Username must be 3–30 characters using letters, numbers, or underscores.';
 if(strlen($password)<12)$errors[]='Password must be at least 12 characters.';
 if($password!==$confirm)$errors[]='Passwords do not match.';
 $s=db()->prepare('SELECT id FROM users WHERE username=? OR email=? LIMIT 1');$s->execute([$username,$invite['email']]);if($s->fetch())$errors[]='That username or email is already registered.';
 if(!$errors){
  try{
   db()->beginTransaction();
   $s=db()->prepare("SELECT * FROM invitations WHERE id=? AND status='pending' AND token_hash=? AND expires_at>NOW() FOR UPDATE");$s->execute([$invite['id'],hash('sha256',$token)]);$locked=$s->fetch();
   if(!$locked)throw new RuntimeException('This invitation is no longer available.');
   $s=db()->prepare("INSERT INTO users(username,email,password_hash,display_name,role,status,email_verified_at) VALUES(?,?,?,?,?,'active',NOW())");
   $s->execute([$username,$invite['email'],password_hash($password,PASSWORD_DEFAULT),$name,$invite['assigned_role']]);$uid=(int)db()->lastInsertId();
   ensure_profile($uid);
   db()->prepare("UPDATE invitations SET status='accepted',accepted_by=?,accepted_at=NOW() WHERE id=?")->execute([$uid,$invite['id']]);
   db()->commit();
   session_regenerate_id(true);$_SESSION['user_id']=$uid;
   flash('success','Welcome to Geek Nation Multiverse. Complete your profile to enter the Multiverse.');redirect('onboarding.php');
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$errors[]=$e->getMessage();}
 }
}
app_header('Accept Invitation');
?><section class="auth-page"><form class="auth-card" method="post"><?=csrf_field()?><input type="hidden" name="token" value="<?=e($token)?>"><p class="eyebrow">YOU’RE INVITED</p><h1>Join the Multiverse</h1><p><strong><?=e($invite['inviter_name'])?></strong> invited you to join as a <strong><?=e($invite['assigned_role']==='admin'?'Geek Nation Multiverse Administrator':'Geek Nation Multiverse Member')?></strong>.</p><?php if($invite['personal_message']):?><blockquote class="invite-message"><?=nl2br(e($invite['personal_message']))?></blockquote><?php endif?><?php foreach($errors as $x):?><div class="alert error"><?=e($x)?></div><?php endforeach?><label>Email<input type="email" value="<?=e($invite['email'])?>" disabled></label><label>Display name<input name="display_name" required value="<?=e($_POST['display_name']??$invite['recipient_name']??'')?>"></label><label>Username<input name="username" required pattern="[a-zA-Z0-9_]{3,30}" value="<?=e($_POST['username']??'')?>"></label><label>Password<input type="password" name="password" minlength="12" required></label><label>Confirm password<input type="password" name="password_confirm" minlength="12" required></label><?php if($invite['assigned_role']==='admin'):?><div class="invite-warning"><strong>Administrator access</strong><br>This account will be able to manage users, invitations, identity options, and approvals.</div><?php endif?><button class="button primary" type="submit">Accept Invitation & Create Account</button><p class="muted">This invitation expires <?=e(date('F j, Y \a\t g:i A',strtotime($invite['expires_at'])))?>.</p></form></section><?php app_footer();
