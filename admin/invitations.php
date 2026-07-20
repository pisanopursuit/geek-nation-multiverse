<?php
require __DIR__.'/../includes/bootstrap.php';
require_admin();
if(!invitations_schema_ready()){flash('error','Install the Invitations database upgrade first.');redirect('upgrade.php');}
expire_invitations();
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $action=$_POST['action']??'send';
 if($action==='send'){
  $email=strtolower(trim($_POST['email']??''));
  $name=trim($_POST['recipient_name']??'');
  $type=($_POST['invitation_type']??'member')==='admin'?'admin':'member';
  $role=$type==='admin'?'admin':'fan';
  $message=trim($_POST['personal_message']??'');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='Enter a valid email address.';
  if(strlen($name)>150)$errors[]='Recipient name is too long.';
  if(strlen($message)>2000)$errors[]='Personal message must be 2,000 characters or fewer.';
  $s=db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');$s->execute([$email]);
  if($s->fetch())$errors[]='That email already belongs to a registered user.';
  $s=db()->prepare("SELECT id FROM invitations WHERE email=? AND status='pending' AND expires_at>NOW() LIMIT 1");$s->execute([$email]);
  if($s->fetch())$errors[]='A pending invitation already exists for that email. Resend or revoke it below.';
  if(!$errors){
   try{
    $raw=bin2hex(random_bytes(32));
    $hash=hash('sha256',$raw);
    $stmt=db()->prepare("INSERT INTO invitations(email,recipient_name,invitation_type,assigned_role,personal_message,token_hash,invited_by,expires_at) VALUES(?,?,?,?,?,?,?,DATE_ADD(NOW(),INTERVAL 7 DAY))");
    $stmt->execute([$email,$name?:null,$type,$role,$message?:null,$hash,(int)user()['id']]);
    $inviteId=(int)db()->lastInsertId();
    $s=db()->prepare("SELECT i.*,u.display_name AS inviter_name FROM invitations i JOIN users u ON u.id=i.invited_by WHERE i.id=?");$s->execute([$inviteId]);$invite=$s->fetch();
    smtp_send($email,'You are invited to Geek Nation Multiverse',invitation_email_html($invite,$raw));
    flash('success','Invitation sent to '.$email.'.');redirect('admin/invitations.php');
   }catch(Throwable $e){$errors[]='The invitation could not be sent. '.$e->getMessage();}
  }
 } elseif(in_array($action,['revoke','resend'],true)){
  $id=(int)($_POST['invitation_id']??0);
  $s=db()->prepare('SELECT i.*,u.display_name AS inviter_name FROM invitations i JOIN users u ON u.id=i.invited_by WHERE i.id=? LIMIT 1');$s->execute([$id]);$invite=$s->fetch();
  if(!$invite){flash('error','Invitation not found.');redirect('admin/invitations.php');}
  if($action==='revoke'){
   if($invite['status']!=='pending'){flash('error','Only pending invitations can be revoked.');}
   else{db()->prepare("UPDATE invitations SET status='revoked' WHERE id=?")->execute([$id]);flash('success','Invitation revoked.');}
   redirect('admin/invitations.php');
  }
  if(!in_array($invite['status'],['pending','expired'],true)){flash('error','That invitation cannot be resent.');redirect('admin/invitations.php');}
  try{
   $raw=bin2hex(random_bytes(32));$hash=hash('sha256',$raw);
   db()->prepare("UPDATE invitations SET token_hash=?,status='pending',expires_at=DATE_ADD(NOW(),INTERVAL 7 DAY),accepted_by=NULL,accepted_at=NULL WHERE id=?")->execute([$hash,$id]);
   $invite['status']='pending';
   smtp_send($invite['email'],'Your Geek Nation Multiverse invitation',invitation_email_html($invite,$raw));
   flash('success','Invitation resent to '.$invite['email'].'.');
  }catch(Throwable $e){flash('error','Invitation could not be resent. '.$e->getMessage());}
  redirect('admin/invitations.php');
 }
}
$filter=$_GET['status']??'all';
$allowed=['all','pending','accepted','expired','revoked'];if(!in_array($filter,$allowed,true))$filter='all';
$sql="SELECT i.*,sender.display_name AS inviter_name,accepted.display_name AS accepted_name FROM invitations i JOIN users sender ON sender.id=i.invited_by LEFT JOIN users accepted ON accepted.id=i.accepted_by";
$params=[];if($filter!=='all'){$sql.=' WHERE i.status=?';$params[]=$filter;}$sql.=' ORDER BY i.created_at DESC';
$s=db()->prepare($sql);$s->execute($params);$invites=$s->fetchAll();
app_header('Invitations');
?><section class="dashboard-hero"><p class="eyebrow">ADMINISTRATION</p><h1>Invite Users & Admins</h1><p class="muted">Send secure, single-use invitations that expire after seven days.</p></section>
<div class="dashboard-grid invite-layout">
 <form class="app-card invite-form" method="post"><?=csrf_field()?><input type="hidden" name="action" value="send"><h2>Send an Invitation</h2><?php foreach($errors as $x):?><div class="alert error"><?=e($x)?></div><?php endforeach?>
  <label>Recipient name <span class="muted">(optional)</span><input name="recipient_name" maxlength="150" value="<?=e($_POST['recipient_name']??'')?>"></label>
  <label>Email address<input type="email" name="email" required value="<?=e($_POST['email']??'')?>"></label>
  <label>Invitation type<select name="invitation_type"><option value="member" <?=($_POST['invitation_type']??'member')==='member'?'selected':''?>>Normal User / Member</option><option value="admin" <?=($_POST['invitation_type']??'')==='admin'?'selected':''?>>Administrator</option></select></label>
  <label>Personal message <span class="muted">(optional)</span><textarea name="personal_message" maxlength="2000" rows="5" placeholder="Add a welcome note..."><?=e($_POST['personal_message']??'')?></textarea></label>
  <div class="invite-warning"><strong>Administrator invitations grant full admin access.</strong><br>Only invite trusted project owners or team members.</div>
  <button class="button primary" type="submit">Send Invitation</button>
 </form>
 <section class="app-card invite-summary"><h2>How invitations work</h2><ol><li>The recipient receives an SMTP email.</li><li>They follow a secure, single-use link.</li><li>They choose a username and password.</li><li>Their email is verified automatically.</li><li>They continue into User Identity onboarding.</li></ol><p><a class="button ghost" href="users.php">View All Users</a></p></section>
</div>
<section class="invitation-list"><div class="section-heading compact"><div><p class="eyebrow">INVITATION HISTORY</p><h2>Sent Invitations</h2></div><div class="filter-links"><?php foreach($allowed as $x):?><a class="<?= $filter===$x?'active':'' ?>" href="?status=<?=e($x)?>"><?=e(ucfirst($x))?></a><?php endforeach?></div></div>
<div class="table-wrap"><table><thead><tr><th>Recipient</th><th>Type</th><th>Status</th><th>Sent / Expires</th><th>Action</th></tr></thead><tbody><?php if(!$invites):?><tr><td colspan="5" class="muted">No invitations found.</td></tr><?php endif?><?php foreach($invites as $i):?><tr><td><strong><?=e($i['recipient_name']?:$i['email'])?></strong><?php if($i['recipient_name']):?><br><span class="muted"><?=e($i['email'])?></span><?php endif?><br><small class="muted">Invited by <?=e($i['inviter_name'])?></small></td><td><span class="role-badge <?=e($i['assigned_role'])?>"><?=e($i['assigned_role']==='admin'?'Administrator':'Member')?></span></td><td><span class="status-badge <?=e($i['status'])?>"><?=e(ucfirst($i['status']))?></span><?php if($i['accepted_name']):?><br><small class="muted">Accepted by <?=e($i['accepted_name'])?></small><?php endif?></td><td><?=e(date('M j, Y',strtotime($i['created_at'])))?><br><span class="muted">Expires <?=e(date('M j, Y',strtotime($i['expires_at'])))?></span></td><td><div class="inline-form"><?php if($i['status']==='pending'):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="revoke"><input type="hidden" name="invitation_id" value="<?=$i['id']?>"><button class="button ghost small" type="submit">Revoke</button></form><?php endif?><?php if(in_array($i['status'],['pending','expired'],true)):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="resend"><input type="hidden" name="invitation_id" value="<?=$i['id']?>"><button class="button primary small" type="submit">Resend</button></form><?php else:?>—<?php endif?></div></td></tr><?php endforeach?></tbody></table></div></section>
<?php app_footer();
