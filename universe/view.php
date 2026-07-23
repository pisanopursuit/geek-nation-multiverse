<?php
require __DIR__.'/../includes/bootstrap.php';
if(!universe_engine_ready()) redirect('upgrade-universes.php');

$slug=trim($_GET['slug']??'');
$universe=universe_by_slug($slug);
if(!$universe){http_response_code(404);exit('Universe not found.');}
$viewer=user();
$canManage=can_manage_universe($universe,$viewer);
if(($universe['status']!=='approved'||!$universe['is_active'])&&!$canManage){http_response_code(404);exit('Universe not found.');}

$billboardReady=false;$communityReady=false;
try{db()->query('SELECT parent_post_id FROM universe_posts LIMIT 1');$billboardReady=true;}catch(Throwable $e){}
try{db()->query('SELECT link_url,image_path FROM universe_posts LIMIT 1');db()->query('SELECT id FROM universe_chat_messages LIMIT 1');$communityReady=true;}catch(Throwable $e){}
$joined=$viewer?user_joined_universe((int)$universe['id'],(int)$viewer['id']):false;

function save_billboard_image(int $userId): ?string {
    if(empty($_FILES['post_image']['name'])) return null;
    $file=$_FILES['post_image'];
    if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('The image could not be uploaded.');
    if(($file['size']??0)>8*1024*1024) throw new RuntimeException('Images are limited to 8 MB.');
    $info=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if(!isset($allowed[$info])) throw new RuntimeException('Use a JPG, PNG, WEBP, or GIF image.');
    $relative='uploads/universe-posts';$dir=dirname(__DIR__).'/'.$relative;
    if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir)) throw new RuntimeException('The post image folder could not be created.');
    $name=$userId.'-'.bin2hex(random_bytes(10)).'.'.$allowed[$info];
    if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('The image could not be saved.');
    return $relative.'/'.$name;
}
function valid_billboard_link(string $url): ?string {
    $url=trim($url);if($url==='')return null;
    if(!preg_match('~^https?://~i',$url))$url='https://'.$url;
    if(!filter_var($url,FILTER_VALIDATE_URL))throw new RuntimeException('Enter a valid link, including a website address.');
    return $url;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_auth();verify_csrf();$action=$_POST['action']??'';
    try{
        if($action==='join'){
            db()->prepare('INSERT IGNORE INTO user_universes(user_id,universe_id) VALUES(?,?)')->execute([(int)user()['id'],(int)$universe['id']]);
            db()->prepare("INSERT INTO universe_activity(universe_id,user_id,action,details) VALUES(?,?,'joined',NULL)")->execute([(int)$universe['id'],(int)user()['id']]);
            flash('success','You joined '.$universe['name'].'.');
        }
        if($action==='leave'){
            db()->prepare('DELETE FROM user_universes WHERE user_id=? AND universe_id=?')->execute([(int)user()['id'],(int)$universe['id']]);
            db()->prepare("INSERT INTO universe_activity(universe_id,user_id,action,details) VALUES(?,?,'left',NULL)")->execute([(int)$universe['id'],(int)user()['id']]);
            flash('success','You left '.$universe['name'].'.');
        }
        if($action==='post'&&$billboardReady){
            if(!$joined&&!$canManage)throw new RuntimeException('Join this universe before posting.');
            $body=trim($_POST['body']??'');$link=$communityReady?valid_billboard_link($_POST['link_url']??''):null;$image=$communityReady?save_billboard_image((int)user()['id']):null;
            if($body===''&&!$link&&!$image)throw new RuntimeException('Add text, a link, or an image before posting.');
            if(mb_strlen($body)>3000)throw new RuntimeException('Posts are limited to 3,000 characters.');
            if($communityReady)db()->prepare('INSERT INTO universe_posts(universe_id,user_id,body,link_url,image_path) VALUES(?,?,?,?,?)')->execute([(int)$universe['id'],(int)user()['id'],$body,$link,$image]);
            else db()->prepare('INSERT INTO universe_posts(universe_id,user_id,body) VALUES(?,?,?)')->execute([(int)$universe['id'],(int)user()['id'],$body]);
            db()->prepare("INSERT INTO universe_activity(universe_id,user_id,action,details) VALUES(?,?,'posted','Added a billboard post')")->execute([(int)$universe['id'],(int)user()['id']]);
            flash('success','Your post is now on the billboard.');
        }
        if($action==='reply'&&$billboardReady){
            if(!$joined&&!$canManage)throw new RuntimeException('Join this universe before replying.');
            $parent=(int)($_POST['parent_post_id']??0);$body=trim($_POST['body']??'');
            if(!$parent||$body==='')throw new RuntimeException('Reply text is required.');
            if(mb_strlen($body)>1500)throw new RuntimeException('Replies are limited to 1,500 characters.');
            $check=db()->prepare("SELECT COUNT(*) FROM universe_posts WHERE id=? AND universe_id=? AND parent_post_id IS NULL AND status='visible'");$check->execute([$parent,(int)$universe['id']]);
            if(!(int)$check->fetchColumn())throw new RuntimeException('That conversation is no longer available.');
            db()->prepare('INSERT INTO universe_posts(universe_id,user_id,parent_post_id,body) VALUES(?,?,?,?)')->execute([(int)$universe['id'],(int)user()['id'],$parent,$body]);
            flash('success','Reply posted.');
        }
        if($action==='delete_post'&&$billboardReady){
            $postId=(int)($_POST['post_id']??0);$s=db()->prepare('SELECT user_id,image_path FROM universe_posts WHERE id=? AND universe_id=?');$s->execute([$postId,(int)$universe['id']]);$post=$s->fetch();
            if(!$post)throw new RuntimeException('Post not found.');
            if((int)$post['user_id']!==(int)user()['id']&&!$canManage)throw new RuntimeException('You cannot delete this post.');
            db()->prepare("UPDATE universe_posts SET status='deleted',body='[deleted]',link_url=NULL,image_path=NULL WHERE id=? AND universe_id=?")->execute([$postId,(int)$universe['id']]);
            if(!empty($post['image_path'])){$path=dirname(__DIR__).'/'.$post['image_path'];if(is_file($path))@unlink($path);}
            flash('success','Post deleted.');
        }
        if($action==='moderate'&&$billboardReady&&$canManage){
            $postId=(int)($_POST['post_id']??0);db()->prepare("UPDATE universe_posts SET status='hidden' WHERE id=? AND universe_id=?")->execute([$postId,(int)$universe['id']]);flash('success','Post hidden.');
        }
    }catch(Throwable $e){flash('error',$e->getMessage());}
    redirect('universe/view.php?slug='.urlencode($slug).'#billboard');
}

$children=universe_children((int)$universe['id']);$breadcrumbs=universe_breadcrumbs($universe);
$members=db()->prepare("SELECT u.username,u.display_name,p.avatar_path FROM user_universes uu JOIN users u ON u.id=uu.user_id LEFT JOIN user_profiles p ON p.user_id=u.id WHERE uu.universe_id=? ORDER BY uu.joined_at DESC LIMIT 12");$members->execute([(int)$universe['id']]);$memberRows=$members->fetchAll();
$threads=[];$replies=[];
if($billboardReady){
    $cols=$communityReady?'p.link_url,p.image_path,':'NULL AS link_url,NULL AS image_path,';
    $s=db()->prepare("SELECT p.*,$cols u.username,u.display_name,up.avatar_path,(SELECT COUNT(*) FROM universe_posts r WHERE r.parent_post_id=p.id AND r.status='visible') reply_count FROM universe_posts p LEFT JOIN users u ON u.id=p.user_id LEFT JOIN user_profiles up ON up.user_id=p.user_id WHERE p.universe_id=? AND p.parent_post_id IS NULL AND p.status='visible' ORDER BY p.created_at DESC LIMIT 30");$s->execute([(int)$universe['id']]);$threads=$s->fetchAll();
    if($threads){$ids=array_column($threads,'id');$marks=implode(',',array_fill(0,count($ids),'?'));$r=db()->prepare("SELECT p.*,u.username,u.display_name,up.avatar_path FROM universe_posts p LEFT JOIN users u ON u.id=p.user_id LEFT JOIN user_profiles up ON up.user_id=p.user_id WHERE p.parent_post_id IN ($marks) AND p.status='visible' ORDER BY p.created_at ASC");$r->execute($ids);foreach($r->fetchAll() as $row)$replies[(int)$row['parent_post_id']][]=$row;}
}
app_header($universe['name']);
?>
<div class="universe-skin" style="<?=e(universe_theme_vars($universe))?>">
<nav class="universe-breadcrumbs"><?php foreach($breadcrumbs as $i=>$b):?><?php if($i):?><span>›</span><?php endif?><a href="view.php?slug=<?=urlencode($b['slug'])?>"><?=e($b['name'])?></a><?php endforeach?></nav>
<section class="universe-hero" <?php if($universe['banner_path']):?>style="background-image:linear-gradient(90deg,var(--universe-bg),transparent),url('<?=e(base_url($universe['banner_path']))?>')"<?php endif?>>
<div class="universe-hero-copy"><span class="universe-hero-icon"><?=e($universe['icon']?:'✦')?></span><p class="eyebrow">UNIVERSE</p><h1><?=e($universe['name'])?></h1><p><?=e($universe['short_description']?:$universe['description']?:'Enter this universe and meet the people who love it.')?></p><div class="universe-actions"><?php if($viewer):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="<?=$joined?'leave':'join'?>"><button class="button primary"><?=$joined?'Leave Universe':'Join Universe'?></button></form><?php else:?><a class="button primary" href="<?=e(base_url('login.php'))?>">Sign in to join</a><?php endif?><?php if($canManage):?><a class="button ghost" href="<?=e(base_url('admin/universes.php?edit='.(int)$universe['id']))?>">Edit Universe</a><a class="button ghost" href="<?=e(base_url('admin/universes.php?parent='.(int)$universe['id']))?>">Add Sub-Universe</a><?php endif?></div></div>
<div class="universe-stats"><strong><?=number_format((int)$universe['member_count'])?></strong><span>Members</span><strong><?=number_format((int)$universe['child_count'])?></strong><span>Worlds inside</span></div></section>
<section class="universe-content-grid"><div>
<article class="app-card universe-panel"><h2>About this universe</h2><p><?=nl2br(e($universe['description']?:$universe['short_description']?:'This universe is waiting for its story.'))?></p></article>
<?php if($children):?><section><div class="section-heading"><h2>Worlds inside <?=e($universe['name'])?></h2><a href="index.php?parent=<?=$universe['id']?>">View all</a></div><div class="universe-grid compact"><?php foreach($children as $child):?><a class="universe-card" style="<?=e(universe_theme_vars($child))?>" href="view.php?slug=<?=urlencode($child['slug'])?>"><div class="universe-card-overlay"></div><div class="universe-card-content"><span class="universe-icon"><?=e($child['icon']?:'✦')?></span><div><h2><?=e($child['name'])?></h2><p><?=e($child['short_description']?:'Explore this world.')?></p></div></div></a><?php endforeach?></div></section><?php elseif($canManage):?><article class="app-card universe-panel empty-worlds"><h2>Build worlds inside <?=e($universe['name'])?></h2><p>Add comic series, characters, games, films, storylines, or any focused community your members want to explore.</p><a class="button primary" href="<?=e(base_url('admin/universes.php?parent='.(int)$universe['id']))?>">Create the first sub-universe</a></article><?php endif?>
<section id="billboard" class="universe-billboard"><div class="section-heading"><div><p class="eyebrow">COMMUNITY CONVERSATIONS</p><h2><?=e($universe['name'])?> Billboard</h2></div></div>
<?php if(!$billboardReady):?><div class="alert error">The billboard upgrade has not been installed yet.</div><?php elseif(!$viewer):?><article class="app-card billboard-compose"><p>Sign in and join this universe to post and reply.</p><a class="button primary" href="<?=e(base_url('login.php'))?>">Sign In</a></article><?php elseif(!$joined&&!$canManage):?><article class="app-card billboard-compose"><p>Join this universe to take part in its conversations.</p></article><?php else:?><form class="app-card billboard-compose" method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="post"><label for="billboard-body">Start a conversation</label><textarea id="billboard-body" name="body" maxlength="3000" rows="4" placeholder="What do you want to talk about in <?=e($universe['name'])?>?"></textarea><?php if($communityReady):?><div class="billboard-media-fields"><label>Link<input type="url" name="link_url" placeholder="https://example.com"></label><label>Image<input type="file" name="post_image" accept="image/jpeg,image/png,image/webp,image/gif"></label></div><small class="muted">Attach a link, JPG, PNG, WEBP, or GIF up to 8 MB.</small><?php endif?><button class="button primary">Post to Billboard</button></form><?php endif?>
<div class="billboard-feed"><?php foreach($threads as $post):?><article class="app-card billboard-thread"><header><a class="billboard-author" href="<?=e($post['username']?base_url('profile.php?u='.urlencode($post['username'])):'#')?>"><?php if($post['avatar_path']):?><img src="<?=e(base_url($post['avatar_path']))?>" alt=""><?php else:?><span><?=e(strtoupper(substr($post['display_name']?:'?',0,1)))?></span><?php endif?><b><?=e($post['display_name']?:'Former member')?></b></a><time datetime="<?=e($post['created_at'])?>"><?=e(date('M j, Y g:i a',strtotime($post['created_at'])))?></time></header><?php if($post['body']!==''):?><div class="billboard-body"><?=nl2br(e($post['body']))?></div><?php endif?><?php if(!empty($post['image_path'])):?><a class="billboard-image" href="<?=e(base_url($post['image_path']))?>" target="_blank" rel="noopener"><img src="<?=e(base_url($post['image_path']))?>" alt="Image posted by <?=e($post['display_name']?:'a member')?>"></a><?php endif?><?php if(!empty($post['link_url'])):?><a class="billboard-link-card" href="<?=e($post['link_url'])?>" target="_blank" rel="noopener noreferrer"><span>🔗</span><div><b><?=e(parse_url($post['link_url'],PHP_URL_HOST)?:'Open link')?></b><small><?=e($post['link_url'])?></small></div></a><?php endif?>
<div class="billboard-post-actions"><?php if($viewer&&((int)$post['user_id']===(int)$viewer['id']||$canManage)):?><form method="post" onsubmit="return confirm('Delete this post and remove it from the billboard?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_post"><input type="hidden" name="post_id" value="<?=$post['id']?>"><button class="text-link danger-link" type="submit">Delete post</button></form><?php endif?><?php if($canManage&&(int)$post['user_id']!==(int)($viewer['id']??0)):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="moderate"><input type="hidden" name="post_id" value="<?=$post['id']?>"><button class="text-link" type="submit">Hide post</button></form><?php endif?></div>
<?php if(!empty($replies[(int)$post['id']])):?><div class="billboard-replies"><?php foreach($replies[(int)$post['id']] as $reply):?><div class="billboard-reply"><div class="billboard-reply-head"><a href="<?=e($reply['username']?base_url('profile.php?u='.urlencode($reply['username'])):'#')?>"><b><?=e($reply['display_name']?:'Former member')?></b></a><time><?=e(date('M j, g:i a',strtotime($reply['created_at'])))?></time></div><p><?=nl2br(e($reply['body']))?></p><?php if($viewer&&((int)$reply['user_id']===(int)$viewer['id']||$canManage)):?><form method="post" class="reply-delete" onsubmit="return confirm('Delete this reply?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_post"><input type="hidden" name="post_id" value="<?=$reply['id']?>"><button class="text-link danger-link" type="submit">Delete</button></form><?php endif?></div><?php endforeach?></div><?php endif?>
<?php if($viewer&&($joined||$canManage)):?><form class="billboard-reply-form" method="post"><?=csrf_field()?><input type="hidden" name="action" value="reply"><input type="hidden" name="parent_post_id" value="<?=$post['id']?>"><input name="body" maxlength="1500" placeholder="Reply to this conversation…" required><button class="button ghost small">Reply</button></form><?php endif?></article><?php endforeach?><?php if($billboardReady&&!$threads):?><article class="app-card universe-panel"><p class="muted">No conversations yet. Start the first one.</p></article><?php endif?></div></section></div>
<aside><article class="app-card universe-panel"><h2>Universe Skin</h2><div class="theme-swatches"><span style="background:var(--universe-primary)"></span><span style="background:var(--universe-secondary)"></span><span style="background:var(--universe-accent)"></span></div><dl><dt>Display style</dt><dd><?=e($universe['display_font']?:'Shell default')?></dd><dt>Texture</dt><dd><?=e($universe['texture_style']?:'None')?></dd><dt>Imagery</dt><dd><?=e($universe['imagery_treatment']?:'Standard')?></dd></dl></article><article class="app-card universe-panel"><h2>Newest members</h2><div class="member-stack"><?php foreach($memberRows as $m):?><a href="<?=e(base_url('profile.php?u='.urlencode($m['username'])))?>"><?php if($m['avatar_path']):?><img src="<?=e(base_url($m['avatar_path']))?>" alt=""><?php else:?><span><?=e(strtoupper(substr($m['display_name'],0,1)))?></span><?php endif?><b><?=e($m['display_name'])?></b></a><?php endforeach?><?php if(!$memberRows):?><p class="muted">Be the first member.</p><?php endif?></div></article>
<?php if($communityReady):?><article class="app-card universe-panel universe-chat-card" id="universe-chat" data-universe-id="<?=(int)$universe['id']?>" data-endpoint="<?=e(base_url('universe/chat.php'))?>" data-csrf="<?=e(csrf_token())?>"><div class="chat-card-heading"><div><p class="eyebrow">LIVE CHAT</p><h2><?=e($universe['name'])?> Chat</h2></div><button class="chat-pop-button" type="button" aria-label="Pop out chat" title="Pop out chat">↗</button></div><div class="chat-messages" aria-live="polite"><p class="muted chat-loading">Loading chat…</p></div><?php if($viewer&&($joined||$canManage)):?><form class="chat-form"><input name="message" maxlength="1000" autocomplete="off" placeholder="Message this universe…" required><button class="button primary small" type="submit">Send</button></form><?php elseif(!$viewer):?><p class="muted">Sign in and join to chat.</p><?php else:?><p class="muted">Join this universe to chat.</p><?php endif?></article><?php else:?><article class="app-card universe-panel"><h2>Live Chat</h2><p class="muted">Run the Version 4.4 upgrade to enable universe chat.</p></article><?php endif?></aside></section></div>
<?php if($communityReady):?><script>
(()=>{const box=document.getElementById('universe-chat');if(!box)return;const list=box.querySelector('.chat-messages'),form=box.querySelector('.chat-form'),pop=box.querySelector('.chat-pop-button');let last=0,busy=false;
const esc=s=>String(s??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
function messageHTML(m){const avatar=m.avatar_url?`<img src="${esc(m.avatar_url)}" alt="">`:`<span>${esc((m.display_name||'?').charAt(0).toUpperCase())}</span>`;return `<div class="chat-message" data-id="${m.id}"><div class="chat-avatar">${avatar}</div><div class="chat-bubble"><div><b>${esc(m.display_name||'Former member')}</b><time>${new Date(m.created_at.replace(' ','T')).toLocaleTimeString([], {hour:'numeric',minute:'2-digit'})}</time></div><p>${esc(m.message).replace(/\n/g,'<br>')}</p>${m.can_delete?`<button class="chat-delete" data-delete="${m.id}" type="button">Delete</button>`:''}</div></div>`}
async function load(){if(busy)return;busy=true;try{const r=await fetch(`${box.dataset.endpoint}?universe_id=${box.dataset.universeId}&after=${last}`,{credentials:'same-origin'}),d=await r.json();if(d.ok){const loading=list.querySelector('.chat-loading');if(loading)loading.remove();d.messages.forEach(m=>{list.insertAdjacentHTML('beforeend',messageHTML(m));last=Math.max(last,Number(m.id));});if(d.messages.length)list.scrollTop=list.scrollHeight;if(!last&&!list.children.length)list.innerHTML='<p class="muted">No messages yet. Say hello.</p>';}}catch(e){}finally{busy=false}}
async function send(data){const r=await fetch(box.dataset.endpoint,{method:'POST',body:data,credentials:'same-origin'}),d=await r.json();if(!d.ok)alert(d.error||'Chat action failed.');return d.ok}
form?.addEventListener('submit',async e=>{e.preventDefault();const input=form.elements.message,fd=new FormData();fd.set('csrf',box.dataset.csrf);fd.set('universe_id',box.dataset.universeId);fd.set('action','send');fd.set('message',input.value);if(await send(fd)){input.value='';await load();}});
list.addEventListener('click',async e=>{const id=e.target.dataset.delete;if(!id)return;if(!confirm('Delete this chat message?'))return;const fd=new FormData();fd.set('csrf',box.dataset.csrf);fd.set('universe_id',box.dataset.universeId);fd.set('action','delete');fd.set('message_id',id);if(await send(fd))e.target.closest('.chat-message')?.remove();});
pop.addEventListener('click',()=>{box.classList.toggle('chat-popped');pop.textContent=box.classList.contains('chat-popped')?'×':'↗';pop.title=box.classList.contains('chat-popped')?'Close pop-out':'Pop out chat';if(box.classList.contains('chat-popped'))list.scrollTop=list.scrollHeight;});
load();setInterval(load,4000);})();
</script><?php endif?>
<?php app_footer();
