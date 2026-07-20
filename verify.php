<?php
require __DIR__.'/includes/bootstrap.php';
$token=$_GET['token']??'';$s=db()->prepare('SELECT user_id FROM email_verifications WHERE token_hash=? AND expires_at>NOW() LIMIT 1');$s->execute([hash('sha256',$token)]);$row=$s->fetch();
if($row){db()->beginTransaction();db()->prepare("UPDATE users SET status='active',email_verified_at=NOW() WHERE id=?")->execute([$row['user_id']]);db()->prepare('DELETE FROM email_verifications WHERE user_id=?')->execute([$row['user_id']]);db()->commit();flash('success','Email verified. Sign in to create your Multiverse identity.');}else flash('error','That verification link is invalid or expired.');redirect('login.php');
