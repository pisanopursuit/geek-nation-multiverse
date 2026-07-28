<?php
require __DIR__.'/../includes/bootstrap.php';require_admin();

function mod_table_exists(string $table): bool {try{db()->query("SELECT 1 FROM {$table} LIMIT 1");return true;}catch(Throwable $e){return false;}}
function mod_column_exists(string $table,string $column): bool {try{$s=db()->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");$s->execute([$column]);return (bool)$s->fetch();}catch(Throwable $e){return false;}}
function mod_config(): array {return [
 'booth'=>['label'=>'Booth','table'=>'booths','title'=>'name','owner_sql'=>'LEFT JOIN users u ON u.id=t.owner_user_id','owner'=>'u.display_name','preview'=>'booth/view.php?slug={slug}','manage'=>'booth/manage.php?id={id}','statuses'=>['pending','approved','rejected','suspended','archived'],'notes'=>'admin_notes'],
 'artist'=>['label'=>'Artist','table'=>'artist_profiles','title'=>'artist_name','owner_sql'=>'LEFT JOIN users u ON u.id=t.user_id','owner'=>'u.display_name','preview'=>'artist-alley/view.php?slug={slug}','manage'=>'artist-alley/manage.php?id={id}','statuses'=>['draft','pending','approved','rejected'],'featured'=>'is_featured'],
 'event'=>['label'=>'Event','table'=>'events','title'=>'title','owner_sql'=>'LEFT JOIN users u ON u.id=t.owner_user_id','owner'=>'u.display_name','preview'=>'events/view.php?slug={slug}','manage'=>'events/manage.php?id={id}','statuses'=>['draft','pending','approved','rejected','cancelled','completed','archived'],'featured'=>'is_featured'],
 'course'=>['label'=>'Course','table'=>'academy_courses','title'=>'title','owner_sql'=>'LEFT JOIN users u ON u.id=t.owner_user_id','owner'=>'u.display_name','preview'=>'academy/course.php?slug={slug}','manage'=>'academy/manage.php?id={id}','statuses'=>['draft','pending','approved','rejected','archived'],'featured'=>'is_featured'],
 'collector'=>['label'=>'Collector Profile','table'=>'collector_profiles','title'=>'shop_name','owner_sql'=>'LEFT JOIN users u ON u.id=t.user_id','owner'=>'u.display_name','preview'=>'collectors/view.php?slug={slug}','manage'=>'collectors/dashboard.php','statuses'=>['pending','approved','suspended'],'featured'=>'is_featured'],
 'collectible'=>['label'=>'Collectible','table'=>'collector_items','title'=>'title','owner_sql'=>'LEFT JOIN collector_profiles cp ON cp.id=t.collector_id LEFT JOIN users u ON u.id=cp.user_id','owner'=>'u.display_name','preview'=>'collectors/item.php?id={id}','manage'=>'collectors/item-edit.php?id={id}','statuses'=>['draft','pending','approved','sold','archived']],
 'company'=>['label'=>'Company','table'=>'companies','title'=>'name','owner_sql'=>'LEFT JOIN users u ON u.id=t.submitted_by','owner'=>'u.display_name','preview'=>'company/view.php?slug={slug}','manage'=>'company/dashboard.php?id={id}','statuses'=>['draft','pending','approved','rejected','suspended'],'notes'=>'review_notes'],
 'brand'=>['label'=>'Brand','table'=>'brands','title'=>'name','owner_sql'=>'LEFT JOIN users u ON u.id=t.submitted_by','owner'=>'u.display_name','preview'=>'brand/view.php?slug={slug}','manage'=>'brand/dashboard.php?id={id}','statuses'=>['draft','pending','approved','rejected','suspended'],'notes'=>'review_notes'],
 'universe'=>['label'=>'Universe','table'=>'universes','title'=>'name','owner_sql'=>'LEFT JOIN users u ON u.id=t.created_by','owner'=>'u.display_name','preview'=>'universe/view.php?slug={slug}','manage'=>'admin/universes.php?edit={id}','statuses'=>['draft','pending','approved','suspended'],'featured'=>'is_featured']
];}
function mod_url(string $pattern,array $row): string {$path=str_replace(['{id}','{slug}'],[(string)$row['id'],urlencode((string)($row['slug']??''))],$pattern);return base_url($path);}
$config=mod_config();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$type=$_POST['type']??'';$id=(int)($_POST['id']??0);$status=$_POST['status']??'';$notes=trim($_POST['notes']??'');
 if(!isset($config[$type])||!in_array($status,$config[$type]['statuses'],true)){flash('error','Invalid moderation action.');redirect('admin/approvals.php');}
 $c=$config[$type];
 try{
  db()->beginTransaction();$sets=['status=?'];$params=[$status];
  if(!empty($c['notes'])&&mod_column_exists($c['table'],$c['notes'])){$sets[]='`'.$c['notes'].'`=?';$params[]=$notes?:null;}
  if(mod_column_exists($c['table'],'reviewed_by')){$sets[]='reviewed_by=?';$params[]=(int)user()['id'];}
  if(mod_column_exists($c['table'],'reviewed_at')){$sets[]='reviewed_at=NOW()';}
  if(!empty($c['featured'])&&mod_column_exists($c['table'],$c['featured'])){$sets[]='`'.$c['featured'].'`=?';$params[]=isset($_POST['featured'])?1:0;}
  $params[]=$id;db()->prepare('UPDATE `'.$c['table'].'` SET '.implode(',',$sets).' WHERE id=?')->execute($params);
  if($type==='company'&&$status==='approved'&&mod_table_exists('company_members'))db()->prepare("UPDATE company_members SET status='active' WHERE company_id=? AND status='pending'")->execute([$id]);
  if($type==='brand'&&$status==='approved'&&mod_table_exists('brand_members'))db()->prepare("UPDATE brand_members SET brand_role=CASE WHEN brand_role='pending_manager' THEN 'manager' ELSE brand_role END,status='active' WHERE brand_id=?")->execute([$id]);
  if(mod_table_exists('moderation_history'))db()->prepare('INSERT INTO moderation_history(content_type,content_id,new_status,notes,acted_by) VALUES(?,?,?,?,?)')->execute([$type,$id,$status,$notes?:null,(int)user()['id']]);
  db()->commit();flash('success',$c['label'].' updated to '.ucfirst($status).'.');
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());}
 $return='admin/approvals.php?type='.urlencode($_GET['type']??$type).'&status='.urlencode($_GET['status']??'pending');redirect($return);
}
$type=$_GET['type']??'all';$status=$_GET['status']??'pending';$search=trim($_GET['q']??'');
$items=[];$counts=[];
foreach($config as $key=>$c){if(!mod_table_exists($c['table']))continue;try{$counts[$key]=(int)db()->query("SELECT COUNT(*) FROM `{$c['table']}` WHERE status='pending'")->fetchColumn();}catch(Throwable $e){$counts[$key]=0;}if($type!=='all'&&$type!==$key)continue;
 try{$sql="SELECT t.*, {$c['owner']} owner_name FROM `{$c['table']}` t {$c['owner_sql']}";$where=[];$params=[];if($status!=='all'){$where[]='t.status=?';$params[]=$status;}if($search!==''){$where[]='t.`'.$c['title'].'` LIKE ?';$params[]='%'.$search.'%';}if($where)$sql.=' WHERE '.implode(' AND ',$where);$sql.=' ORDER BY t.created_at DESC LIMIT 200';$s=db()->prepare($sql);$s->execute($params);foreach($s->fetchAll() as $row){$row['_type']=$key;$items[]=$row;}}catch(Throwable $e){}
}
usort($items,fn($a,$b)=>strcmp((string)($b['created_at']??''),(string)($a['created_at']??'')));
app_header('Approval Queue');
gnm_page_hero(['eyebrow'=>'ADMINISTRATION','title'=>'Approval Queue','description'=>'Approve, reject, suspend, archive, or feature submissions from every platform module.','actions'=>[['label'=>'Administration Home','href'=>base_url('admin/index.php'),'variant'=>'ghost']]]);
?>
<section class="gnm-section"><form class="gnm-filter-bar" method="get"><label><span>Content type</span><select name="type"><option value="all">All content</option><?php foreach($config as $key=>$c):if(!mod_table_exists($c['table']))continue;?><option value="<?=e($key)?>" <?=$type===$key?'selected':''?>><?=e($c['label'])?> (<?=(int)($counts[$key]??0)?>)</option><?php endforeach?></select></label><label><span>Status</span><select name="status"><?php foreach(['pending','approved','rejected','draft','suspended','archived','all'] as $x):?><option value="<?=$x?>" <?=$status===$x?'selected':''?>><?=ucfirst($x)?></option><?php endforeach?></select></label><label><span>Search</span><input name="q" value="<?=e($search)?>" placeholder="Search titles or names"></label><button class="gnm-button gnm-button--primary">Apply Filters</button></form></section>
<section class="gnm-section"><div class="gnm-admin-queue"><?php foreach($items as $item):$c=$config[$item['_type']];?>
<article class="gnm-admin-review-card"><div class="gnm-admin-review-main"><div class="gnm-admin-review-meta"><?=gnm_badge($c['label'],'info')?> <?=gnm_badge(ucfirst((string)$item['status']),$item['status']==='approved'?'success':($item['status']==='pending'?'warning':($item['status']==='rejected'?'danger':'neutral')))?></div><h2><?=e($item[$c['title']])?></h2><p>Submitted by <strong><?=e($item['owner_name']?:'Unknown user')?></strong><?php if(!empty($item['created_at'])):?> · <?=e(date('M j, Y',strtotime($item['created_at'])))?><?php endif?></p><div class="gnm-actions"><a class="gnm-button gnm-button--ghost" href="<?=e(mod_url($c['preview'],$item))?>">Preview</a><a class="gnm-button gnm-button--ghost" href="<?=e(mod_url($c['manage'],$item))?>">Manage</a></div></div>
<form method="post" class="gnm-admin-review-form"><?=csrf_field()?><input type="hidden" name="type" value="<?=e($item['_type'])?>"><input type="hidden" name="id" value="<?=(int)$item['id']?>"><label><span>Status</span><select name="status"><?php foreach($c['statuses'] as $x):?><option value="<?=e($x)?>" <?=$item['status']===$x?'selected':''?>><?=e(ucfirst($x))?></option><?php endforeach?></select></label><?php if(!empty($c['featured'])&&mod_column_exists($c['table'],$c['featured'])):?><label class="check-row"><input type="checkbox" name="featured" <?=!empty($item[$c['featured']])?'checked':''?>> Featured</label><?php endif?><label><span>Administrator notes</span><textarea name="notes" rows="3"><?=e((string)($item[$c['notes']??'']??''))?></textarea></label><button class="gnm-button gnm-button--primary" type="submit">Save Decision</button></form></article>
<?php endforeach;if(!$items):?><?php gnm_empty_state('No matching submissions','There is nothing in this queue for the selected filters.','View All Pending','approvals.php','spark');?><?php endif?></div></section>
<?php app_footer();
