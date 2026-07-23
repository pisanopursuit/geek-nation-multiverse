<?php
require __DIR__.'/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
function chat_json(array $data,int $status=200): never { http_response_code($status); echo json_encode($data,JSON_UNESCAPED_SLASHES); exit; }
$universeId=(int)($_REQUEST['universe_id']??0);
$s=db()->prepare("SELECT * FROM universes WHERE id=? AND status='approved' AND is_active=1");$s->execute([$universeId]);$universe=$s->fetch();
if(!$universe)chat_json(['ok'=>false,'error'=>'Universe not found.'],404);
$viewer=user();$canManage=can_manage_universe($universe,$viewer);
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!$viewer)chat_json(['ok'=>false,'error'=>'Sign in to chat.'],401);
    verify_csrf();
    $action=$_POST['action']??'send';
    if($action==='delete'){
        $id=(int)($_POST['message_id']??0);$q=db()->prepare('SELECT user_id FROM universe_chat_messages WHERE id=? AND universe_id=?');$q->execute([$id,$universeId]);$row=$q->fetch();
        if(!$row)chat_json(['ok'=>false,'error'=>'Message not found.'],404);
        if((int)$row['user_id']!==(int)$viewer['id']&&!$canManage)chat_json(['ok'=>false,'error'=>'You cannot delete this message.'],403);
        db()->prepare("UPDATE universe_chat_messages SET status='deleted' WHERE id=?")->execute([$id]);chat_json(['ok'=>true]);
    }
    if(!user_joined_universe($universeId,(int)$viewer['id'])&&!$canManage)chat_json(['ok'=>false,'error'=>'Join this universe before chatting.'],403);
    $message=trim($_POST['message']??'');if($message==='')chat_json(['ok'=>false,'error'=>'Write a message first.'],422);if(mb_strlen($message)>1000)chat_json(['ok'=>false,'error'=>'Messages are limited to 1,000 characters.'],422);
    db()->prepare('INSERT INTO universe_chat_messages(universe_id,user_id,message) VALUES(?,?,?)')->execute([$universeId,(int)$viewer['id'],$message]);chat_json(['ok'=>true,'id'=>(int)db()->lastInsertId()]);
}
$after=max(0,(int)($_GET['after']??0));
$q=db()->prepare("SELECT m.id,m.user_id,m.message,m.created_at,u.display_name,u.username,p.avatar_path FROM universe_chat_messages m LEFT JOIN users u ON u.id=m.user_id LEFT JOIN user_profiles p ON p.user_id=m.user_id WHERE m.universe_id=? AND m.status='visible' AND m.id>? ORDER BY m.id ASC LIMIT 100");$q->execute([$universeId,$after]);$rows=[];
foreach($q->fetchAll() as $r){$r['can_delete']=$viewer&&((int)$r['user_id']===(int)$viewer['id']||$canManage);$r['profile_url']=$r['username']?base_url('profile.php?u='.urlencode($r['username'])):null;$r['avatar_url']=$r['avatar_path']?base_url($r['avatar_path']):null;$rows[]=$r;}
chat_json(['ok'=>true,'messages'=>$rows,'viewer_id'=>$viewer?(int)$viewer['id']:null,'can_send'=>(bool)($viewer&&(user_joined_universe($universeId,(int)$viewer['id'])||$canManage))]);
