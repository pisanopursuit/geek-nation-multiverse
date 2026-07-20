<?php
require __DIR__.'/includes/bootstrap.php';
if(user()) redirect('dashboard.php');
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $name=trim($_POST['display_name']??''); $username=strtolower(trim($_POST['username']??'')); $email=strtolower(trim($_POST['email']??'')); $password=$_POST['password']??'';
 if($name===''||!preg_match('/^[a-z0-9_]{3,30}$/',$username)||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12)$errors[]='Use a valid name, email, username, and a password of at least 12 characters.';
 if(!$errors){
  try{
   db()->beginTransaction();
   $s=db()->prepare("INSERT INTO users(username,email,password_hash,display_name) VALUES(?,?,?,?)");
   $s->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT),$name]); $uid=(int)db()->lastInsertId();
   $token=bin2hex(random_bytes(32)); $h=hash('sha256',$token);
   db()->prepare("INSERT INTO email_verifications(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR))")->execute([$uid,$h]);
   db()->commit();
   $link=base_url('verify.php?token='.urlencode($token));
   smtp_send($email,'Verify your Geek Nation Multiverse account','<h1>Welcome to Geek Nation Multiverse</h1><p>Confirm your email to activate your account.</p><p><a href="'.e($link).'">Verify my email</a></p><p>This link expires in 24 hours.</p>');
   flash('success','Account created. Check your email for the verification link.'); redirect('login.php');
  }catch(PDOException $e){if(db()->inTransaction())db()->rollBack();$errors[]=$e->getCode()==='23000'?'That username or email is already registered.':'Registration failed.';}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$errors[]='Your account could not be completed because the verification email failed. Please contact an administrator.';}
 }
}
app_header('Join');
?><section class="auth-page"><form class="auth-card" method="post"><?=csrf_field()?><p class="eyebrow">JOIN THE MULTIVERSE</p><h1>Create an account</h1><?php foreach($errors as $x):?><div class="alert error"><?=e($x)?></div><?php endforeach?><label>Display name<input name="display_name" required value="<?=e($_POST['display_name']??'')?>"></label><label>Username<input name="username" required pattern="[a-zA-Z0-9_]{3,30}" value="<?=e($_POST['username']??'')?>"></label><label>Email<input type="email" name="email" required value="<?=e($_POST['email']??'')?>"></label><label>Password<input type="password" name="password" minlength="12" required></label><button class="button primary" type="submit">Create Account</button><p>Already registered? <a href="login.php">Sign in</a></p></form></section><?php app_footer();
