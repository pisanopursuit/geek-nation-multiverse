<?php
declare(strict_types=1);

function dev_table_exists(string $table): bool {
    $s=db()->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $s->execute([$table]); return (int)$s->fetchColumn()>0;
}
function dev_column_exists(string $table,string $column): bool {
    $s=db()->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $s->execute([$table,$column]); return (int)$s->fetchColumn()>0;
}
function dev_slug(string $value): string {
    $value=strtolower(trim($value)); $value=preg_replace('/[^a-z0-9]+/','-',$value)??''; return trim($value,'-');
}
function dev_track(int $batchId,string $module,string $table,array $key,int $order=100): void {
    $s=db()->prepare('INSERT INTO developer_demo_records(batch_id,module_name,table_name,record_key,cleanup_order) VALUES(?,?,?,?,?)');
    $s->execute([$batchId,$module,$table,json_encode($key,JSON_UNESCAPED_SLASHES),$order]);
}
function dev_insert(int $batchId,string $module,string $table,array $data,int $order=100): int {
    $cols=array_keys($data); $sql='INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')';
    $s=db()->prepare($sql); $s->execute(array_values($data)); $id=(int)db()->lastInsertId();
    dev_track($batchId,$module,$table,['id'=>$id],$order); return $id;
}
function dev_track_composite(int $batchId,string $module,string $table,array $key,int $order=100): void { dev_track($batchId,$module,$table,$key,$order); }
function dev_new_batch(string $scenario,int $adminId): array {
    $key='dev_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
    $label='Developer Test Batch '.date('M j, Y g:i A');
    $s=db()->prepare('INSERT INTO developer_demo_batches(batch_key,label,scenario,status,created_by) VALUES(?,?,?,?,?)');
    $s->execute([$key,$label,$scenario,'building',$adminId]); return ['id'=>(int)db()->lastInsertId(),'key'=>$key,'label'=>$label];
}
function dev_finish_batch(int $batchId,array $summary,string $status='ready'): void {
    $s=db()->prepare('UPDATE developer_demo_batches SET status=?,summary_json=?,completed_at=NOW() WHERE id=?');
    $s->execute([$status,json_encode($summary),$batchId]);
}
function dev_unique(string $base,string $batchKey): string { return dev_slug($base.'-'.$batchKey); }
function dev_batch_tag(string $batchKey): string { return strtoupper(substr(preg_replace('/[^a-z0-9]/i','',$batchKey) ?: 'DEMO', -8)); }
function dev_demo_name(string $base,string $batchKey): string { return $base.' (Demo '.dev_batch_tag($batchKey).')'; }

function dev_generate_users(int $batchId,string $batchKey,int $ownerId): array {
    $names=[['Maya','Chen','creator'],['Jordan','Rivera','vendor'],['Avery','Brooks','fan'],['Riley','Morgan','fan'],['Casey','Quinn','creator'],['Taylor','Reed','vendor']];
    $ids=[]; $accounts=[]; $plainPassword='DemoPass!52';
    foreach($names as $i=>$n){
        $username=dev_unique($n[0].$n[1],$batchKey); $email=$username.'@example.test';
        $id=dev_insert($batchId,'users','users',['username'=>$username,'email'=>$email,'password_hash'=>password_hash($plainPassword,PASSWORD_DEFAULT),'display_name'=>$n[0].' '.$n[1].' (Demo)','role'=>$n[2],'status'=>'active','company_brand_access'=>'approved','email_verified_at'=>date('Y-m-d H:i:s')],900);
        $ids[]=$id;
        $accounts[]=['id'=>$id,'username'=>$username,'email'=>$email,'display_name'=>$n[0].' '.$n[1].' (Demo)','role'=>$n[2]];
        if(dev_table_exists('user_profiles')){db()->prepare('INSERT INTO user_profiles(user_id,bio,location,website,visibility,onboarding_step,onboarding_completed_at) VALUES(?,?,?,?,?,?,NOW())')->execute([$id,'Demo profile created by the Developer Center.','New York, NY','https://example.test','public',5]);dev_track_composite($batchId,'users','user_profiles',['user_id'=>$id],850);}
        if(dev_table_exists('user_preferences')){db()->prepare('INSERT INTO user_preferences(user_id,email_newsletter,email_community_updates,email_product_updates) VALUES(?,?,?,?)')->execute([$id,1,1,1]);dev_track_composite($batchId,'users','user_preferences',['user_id'=>$id],850);}
    }
    
    // Verify every generated account before reporting success.
    $check=db()->prepare('SELECT password_hash FROM users WHERE id=?');
    foreach($ids as $id){
        $check->execute([$id]);
        $hash=(string)$check->fetchColumn();
        if($hash==='' || !password_verify($plainPassword,$hash)){
            throw new RuntimeException('Demo account password verification failed for user ID '.$id);
        }
    }
    return ['ids'=>$ids,'count'=>count($ids),'password'=>$plainPassword,'accounts'=>$accounts];
}

function dev_generate_organizations(int $batchId,string $batchKey,int $ownerId,array $userIds): array {
    if(!dev_table_exists('companies')) return ['company_ids'=>[],'brand_ids'=>[],'count'=>0];
    $companies=[['Nebula Forge Studios','Entertainment Technology'],['Heroic Pages Collective','Publishing'],['Pixel Dragon Works','Gaming']]; $companyIds=[];$brandIds=[];
    foreach($companies as $i=>$c){
        $cid=dev_insert($batchId,'organizations','companies',['name'=>dev_demo_name($c[0],$batchKey),'slug'=>dev_unique($c[0],$batchKey),'short_description'=>'A connected demo company for platform testing.','description'=>'Created by the Geek Nation Multiverse Developer Center. Safe to remove with batch cleanup.','website'=>'https://example.test','public_email'=>'company'.$i.'@example.test','location'=>'New York, NY','category'=>$c[1],'founded_year'=>2024,'status'=>'approved','submitted_by'=>$ownerId,'reviewed_by'=>$ownerId,'review_notes'=>'Developer Center demo record','reviewed_at'=>date('Y-m-d H:i:s')],700); $companyIds[]=$cid;
        $uid=$userIds[$i%max(1,count($userIds))]??$ownerId;
        if(dev_table_exists('company_members')){db()->prepare("INSERT INTO company_members(company_id,user_id,relationship_type,position_title,company_role,status) VALUES(?,?,?,?,?,?)")->execute([$cid,$uid,'owner','Demo Owner','owner','active']);dev_track_composite($batchId,'organizations','company_members',['company_id'=>$cid,'user_id'=>$uid],650);}
        if(dev_table_exists('brands')){
            $bname=dev_demo_name(['Cosmic Crate','Panelverse Press','Critical Pixel'][$i],$batchKey);
            $bid=dev_insert($batchId,'organizations','brands',['company_id'=>$cid,'name'=>$bname,'slug'=>dev_unique($bname,$batchKey),'short_description'=>'A demo brand connected to its parent company.','description'=>'Developer Center demo brand.','website'=>'https://example.test','public_email'=>'brand'.$i.'@example.test','category'=>$c[1],'founded_year'=>2025,'status'=>'approved','submitted_by'=>$ownerId,'reviewed_by'=>$ownerId,'review_notes'=>'Developer Center demo record','reviewed_at'=>date('Y-m-d H:i:s')],680);$brandIds[]=$bid;
            if(dev_table_exists('brand_members')){db()->prepare("INSERT INTO brand_members(brand_id,user_id,position_title,brand_role,status) VALUES(?,?,?,?,?)")->execute([$bid,$uid,'Demo Manager','manager','active']);dev_track_composite($batchId,'organizations','brand_members',['brand_id'=>$bid,'user_id'=>$uid],640);}
        }
    }
    return ['company_ids'=>$companyIds,'brand_ids'=>$brandIds,'count'=>count($companyIds)+count($brandIds)];
}

function dev_generate_universes(int $batchId,string $batchKey,int $ownerId,array $userIds): array {
    if(!dev_table_exists('universes')) return ['ids'=>[],'count'=>0];
    $hasParent=dev_column_exists('universes','parent_id'); $hasTheme=dev_column_exists('universes','primary_color'); $ids=[];
    $rootData=['name'=>dev_demo_name('Demo Dimensions',$batchKey),'slug'=>dev_unique('demo-dimensions',$batchKey),'icon'=>'🧪','is_active'=>1,'sort_order'=>900];
    if(dev_column_exists('universes','short_description'))$rootData['short_description']='A temporary universe category for end-to-end testing.';
    if(dev_column_exists('universes','status'))$rootData['status']='approved'; if(dev_column_exists('universes','is_featured'))$rootData['is_featured']=1; if(dev_column_exists('universes','created_by'))$rootData['created_by']=$ownerId;
    if($hasTheme){$rootData+=['primary_color'=>'#6f4cff','secondary_color'=>'#15172a','accent_color'=>'#27d7ff','background_color'=>'#070812','surface_color'=>'#111321','text_color'=>'#f7f7fb'];}
    $root=dev_insert($batchId,'universes','universes',$rootData,500);$ids[]=$root;
    foreach(['Chrono Rangers','Mecha Harbor','Mystic Arcade'] as $i=>$name){$data=['name'=>dev_demo_name($name,$batchKey),'slug'=>dev_unique($name,$batchKey),'icon'=>['⏳','🤖','🕹️'][$i],'is_active'=>1,'sort_order'=>910+$i]; if($hasParent)$data['parent_id']=$root;if(dev_column_exists('universes','short_description'))$data['short_description']='Demo child universe for navigation and community testing.';if(dev_column_exists('universes','status'))$data['status']='approved';if(dev_column_exists('universes','created_by'))$data['created_by']=$ownerId;if($hasTheme)$data+=['primary_color'=>'#7c21f3','secondary_color'=>'#111321','accent_color'=>'#43e3ff','background_color'=>'#070812','surface_color'=>'#15172a','text_color'=>'#f7f7fb'];$ids[]=dev_insert($batchId,'universes','universes',$data,500);}
    if(dev_table_exists('user_universes')){foreach($userIds as $i=>$uid){$universeId=$ids[$i%count($ids)];$cols=['user_id','universe_id'];$vals=[$uid,$universeId];if(dev_column_exists('user_universes','joined_at')){$cols[]='joined_at';$vals[]=date('Y-m-d H:i:s');}$sql='INSERT IGNORE INTO user_universes('.implode(',',$cols).') VALUES('.implode(',',array_fill(0,count($cols),'?')).')';db()->prepare($sql)->execute($vals);dev_track_composite($batchId,'universes','user_universes',['user_id'=>$uid,'universe_id'=>$universeId],450);}}
    return ['ids'=>$ids,'count'=>count($ids)];
}

function dev_generate_community(int $batchId,array $universeIds,array $userIds): array {
    if(!$universeIds||!$userIds)return ['count'=>0];$count=0;
    if(dev_table_exists('universe_posts')){foreach($universeIds as $i=>$universeId){$uid=$userIds[$i%count($userIds)];$post=dev_insert($batchId,'community','universe_posts',['universe_id'=>$universeId,'user_id'=>$uid,'parent_post_id'=>null,'body'=>'Welcome to this Developer Center test universe. Use this thread to test replies and moderation.','status'=>'visible'],400);$count++;$replyUid=$userIds[($i+1)%count($userIds)];dev_insert($batchId,'community','universe_posts',['universe_id'=>$universeId,'user_id'=>$replyUid,'parent_post_id'=>$post,'body'=>'This is a connected demo reply.','status'=>'visible'],390);$count++;}}
    if(dev_table_exists('universe_chat_messages')){foreach($universeIds as $i=>$universeId){for($j=0;$j<3;$j++){dev_insert($batchId,'community','universe_chat_messages',['universe_id'=>$universeId,'user_id'=>$userIds[($i+$j)%count($userIds)],'message'=>'Demo chat message '.($j+1).' — live chat is working.','status'=>'visible'],380);$count++;}}}
    if(dev_table_exists('universe_activity')){foreach($universeIds as $i=>$universeId){dev_insert($batchId,'community','universe_activity',['universe_id'=>$universeId,'user_id'=>$userIds[$i%count($userIds)],'action'=>'demo_activity','details'=>'Developer Center generated activity.'],370);$count++;}}
    return ['count'=>$count];
}

function dev_generate_booths(int $batchId,string $batchKey,int $ownerId,array $companyIds,array $brandIds,array $universeIds,array $userIds): array {
    if(!dev_table_exists('booths'))return ['ids'=>[],'count'=>0];$ids=[];
    $booths=[['Geek Nation Collectibles','Rare comics, art, and convention exclusives.'],['Artist Alley Lab','Prints, commissions, and digital art.'],['Pixel Dragon Arcade','Indie games, merch, and playable demos.']];
    foreach($booths as $i=>$b){$data=['owner_user_id'=>$i===0?$ownerId:($userIds[$i%max(1,count($userIds))]??$ownerId),'company_id'=>$companyIds[$i%max(1,count($companyIds))]??null,'brand_id'=>$brandIds[$i%max(1,count($brandIds))]??null,'name'=>dev_demo_name($b[0],$batchKey),'slug'=>dev_unique($b[0],$batchKey),'tagline'=>$b[1],'description'=>'A fully connected booth generated for testing management, storefront, orders, gallery, downloads, and analytics.','website'=>'https://example.test','contact_email'=>'booth'.$i.'@example.test','commerce_mode'=>'demo','status'=>'approved','is_featured'=>$i===0?1:0,'admin_notes'=>'Developer Center demo record'];
        foreach(['hours_text'=>'Friday–Sunday, 10:00 AM–8:00 PM ET','booth_location'=>'Virtual Hall A · Booth '.(100+$i),'online_status'=>'open','support_email'=>'support'.$i.'@example.test','shipping_policy'=>'Demo orders ship in 3–5 business days.','return_policy'=>'Demo returns accepted within 30 days.','instagram_url'=>'https://instagram.com/example','youtube_url'=>'https://youtube.com/@example','discord_url'=>'https://discord.gg/example'] as $col=>$val)if(dev_column_exists('booths',$col))$data[$col]=$val;
        $bid=dev_insert($batchId,'booths','booths',$data,300);$ids[]=$bid;
        if(dev_table_exists('booth_universes')&&$universeIds){foreach(array_slice($universeIds,0,2) as $uid){db()->prepare('INSERT IGNORE INTO booth_universes(booth_id,universe_id) VALUES(?,?)')->execute([$bid,$uid]);dev_track_composite($batchId,'booths','booth_universes',['booth_id'=>$bid,'universe_id'=>$uid],290);}}
        if(dev_table_exists('booth_team_members')&&$userIds){foreach(array_slice($userIds,0,3) as $j=>$uid){db()->prepare('INSERT IGNORE INTO booth_team_members(booth_id,user_id,role,status) VALUES(?,?,?,?)')->execute([$bid,$uid,['manager','artist','staff'][$j],'active']);dev_track_composite($batchId,'booths','booth_team_members',['booth_id'=>$bid,'user_id'=>$uid],280);}}
        if(dev_table_exists('booth_gallery')){for($g=1;$g<=3;$g++)dev_insert($batchId,'booths','booth_gallery',['booth_id'=>$bid,'image_path'=>'assets/geek-nation-multiverse-logo.png','caption'=>'Demo booth gallery image '.$g,'sort_order'=>$g],270);}
        if(dev_table_exists('booth_downloads')){dev_insert($batchId,'booths','booth_downloads',['booth_id'=>$bid,'title'=>'Demo Convention Catalog','description'=>'External demo resource for testing downloads.','file_path'=>null,'external_url'=>'https://example.test/catalog.pdf','is_public'=>1],260);}
        if(dev_table_exists('booth_views')){for($d=0;$d<14;$d++){for($v=0;$v<min(8,2+$d);$v++){dev_insert($batchId,'booths','booth_views',['booth_id'=>$bid,'viewer_user_id'=>$userIds[$v%max(1,count($userIds))]??null,'session_key'=>$batchKey.'-'.$bid.'-'.$d.'-'.$v,'viewed_on'=>date('Y-m-d',strtotime('-'.$d.' days'))],250);}}}
    }
    return ['ids'=>$ids,'count'=>count($ids)];
}

function dev_generate_marketplace(int $batchId,string $batchKey,array $boothIds,array $userIds): array {
    if(!$boothIds||!dev_table_exists('booth_products'))return ['product_ids'=>[],'order_ids'=>[],'count'=>0];$products=[];$orders=[];$productNames=[['Signed Cosmic Issue #1','physical',49.99,12],['Convention T-Shirt','physical',29.00,40],['Digital Wallpaper Pack','digital',8.00,null],['Custom Character Commission','service',125.00,6],['Limited Edition Print','physical',35.00,0],['Indie Game Download','digital',14.99,null]];
    foreach($boothIds as $b=>$bid){foreach($productNames as $i=>$p){$status=$p[3]===0?'sold_out':($i===5?'draft':'active');$pid=dev_insert($batchId,'marketplace','booth_products',['booth_id'=>$bid,'name'=>dev_demo_name($p[0],$batchKey),'slug'=>dev_unique($p[0].'-'.$bid,$batchKey),'description'=>'Demo product generated to test storefront, cart, inventory, and order workflows.','image_path'=>'assets/geek-nation-multiverse-logo.png','product_type'=>$p[1],'price'=>$p[2],'compare_at_price'=>$i%2===0?$p[2]+10:null,'sku'=>'DEMO-'.$bid.'-'.str_pad((string)$i,3,'0',STR_PAD_LEFT),'inventory_quantity'=>$p[3],'shipping_note'=>$p[1]==='physical'?'Ships from the demo booth.':null,'download_note'=>$p[1]==='digital'?'Demo download delivered after payment integration.':null,'convention_exclusive'=>$i===0?1:0,'signed_item'=>$i===0?1:0,'preorder'=>$i===3?1:0,'is_featured'=>$i<2?1:0,'status'=>$status],200);$products[]=$pid;}}
    if(dev_table_exists('booth_orders')&&dev_table_exists('booth_order_items')){$statuses=['pending','confirmed','processing','shipped','completed','cancelled'];foreach($statuses as $i=>$status){$bid=$boothIds[$i%count($boothIds)];$pid=$products[$i%count($products)];$price=(float)db()->query('SELECT price FROM booth_products WHERE id='.(int)$pid)->fetchColumn();$customer=$userIds[$i%max(1,count($userIds))]??null;$oid=dev_insert($batchId,'marketplace','booth_orders',['order_number'=>'DEMO-'.strtoupper(substr($batchKey,-6)).'-'.str_pad((string)($i+1),3,'0',STR_PAD_LEFT),'booth_id'=>$bid,'customer_user_id'=>$customer,'customer_name'=>'Demo Customer '.($i+1),'customer_email'=>'customer'.($i+1).'@example.test','shipping_address1'=>'123 Multiverse Way','shipping_city'=>'Brooklyn','shipping_state'=>'NY','shipping_postal'=>'11201','shipping_country'=>'USA','customer_note'=>'Developer Center test order.','subtotal'=>$price,'total'=>$price,'currency'=>'USD','order_status'=>$status,'payment_status'=>$status==='cancelled'?'failed':($status==='pending'?'pending':'paid'),'payment_provider'=>'demo'],180);$orders[]=$oid;dev_insert($batchId,'marketplace','booth_order_items',['order_id'=>$oid,'product_id'=>$pid,'product_name'=>'Demo Product','sku'=>'DEMO-SKU','unit_price'=>$price,'quantity'=>1,'line_total'=>$price],170);}}
    return ['product_ids'=>$products,'order_ids'=>$orders,'count'=>count($products)+count($orders)];
}



function dev_batch_user_ids(int $batchId): array {
    if(!dev_table_exists('developer_demo_records')) return [];
    $s=db()->prepare("SELECT record_key FROM developer_demo_records WHERE batch_id=? AND table_name='users' ORDER BY id");
    $s->execute([$batchId]);
    $ids=[];
    foreach($s->fetchAll(PDO::FETCH_COLUMN) as $json){
        $key=json_decode((string)$json,true);
        if(is_array($key) && !empty($key['id'])) $ids[]=(int)$key['id'];
    }
    return array_values(array_unique(array_filter($ids)));
}

function dev_reset_batch_passwords(int $batchId,string $plainPassword='DemoPass!52'): array {
    $ids=dev_batch_user_ids($batchId);
    if(!$ids) return ['updated'=>0,'verified'=>0,'password'=>$plainPassword,'accounts'=>[]];
    $update=db()->prepare('UPDATE users SET password_hash=?, status=\'active\', email_verified_at=COALESCE(email_verified_at,NOW()) WHERE id=?');
    $read=db()->prepare('SELECT id,username,email,display_name,role,password_hash FROM users WHERE id=?');
    $accounts=[];$verified=0;
    foreach($ids as $id){
        $hash=password_hash($plainPassword,PASSWORD_DEFAULT);
        $update->execute([$hash,$id]);
        $read->execute([$id]);$row=$read->fetch();
        if($row && password_verify($plainPassword,(string)$row['password_hash'])){
            $verified++;
            unset($row['password_hash']);
            $accounts[]=$row;
        }
    }
    if($verified!==count($ids)) throw new RuntimeException('One or more demo passwords could not be verified after reset.');
    return ['updated'=>count($ids),'verified'=>$verified,'password'=>$plainPassword,'accounts'=>$accounts];
}

function dev_generate_complete(int $adminId,string $scenario='complete'): array {
    $batch=dev_new_batch($scenario,$adminId);$id=$batch['id'];$summary=[];
    try{db()->beginTransaction();$users=dev_generate_users($id,$batch['key'],$adminId);$summary['users']=$users['count'];$org=dev_generate_organizations($id,$batch['key'],$adminId,$users['ids']);$summary['organizations']=$org['count'];$uni=dev_generate_universes($id,$batch['key'],$adminId,$users['ids']);$summary['universes']=$uni['count'];$community=dev_generate_community($id,$uni['ids'],$users['ids']);$summary['community']=$community['count'];$booths=dev_generate_booths($id,$batch['key'],$adminId,$org['company_ids'],$org['brand_ids'],$uni['ids'],$users['ids']);$summary['booths']=$booths['count'];$market=dev_generate_marketplace($id,$batch['key'],$booths['ids'],$users['ids']);$summary['marketplace']=$market['count'];dev_finish_batch($id,$summary);db()->commit();return ['batch'=>$batch,'summary'=>$summary,'password'=>$users['password'],'accounts'=>$users['accounts']];}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();db()->prepare("UPDATE developer_demo_batches SET status='failed',summary_json=? WHERE id=?")->execute([json_encode(['error'=>$e->getMessage()]),$id]);throw $e;}
}

function dev_cleanup_batch(int $batchId): array {
    $batch=db()->prepare('SELECT * FROM developer_demo_batches WHERE id=?');$batch->execute([$batchId]);$b=$batch->fetch();if(!$b)throw new RuntimeException('Demo batch not found.');
    $records=db()->prepare('SELECT * FROM developer_demo_records WHERE batch_id=? ORDER BY cleanup_order ASC,id DESC');$records->execute([$batchId]);$deleted=0;$errors=[];
    db()->beginTransaction();
    try{db()->exec('SET FOREIGN_KEY_CHECKS=0');foreach($records as $r){$key=json_decode($r['record_key'],true);if(!is_array($key)||!$key)continue;$where=[];$vals=[];foreach($key as $col=>$val){$where[]='`'.$col.'`=?';$vals[]=$val;}try{$s=db()->prepare('DELETE FROM `'.$r['table_name'].'` WHERE '.implode(' AND ',$where));$s->execute($vals);$deleted+=$s->rowCount();}catch(Throwable $e){$errors[]=$r['table_name'].': '.$e->getMessage();}}db()->exec('SET FOREIGN_KEY_CHECKS=1');db()->prepare("UPDATE developer_demo_batches SET status='cleaned',cleaned_at=NOW() WHERE id=?")->execute([$batchId]);db()->commit();}catch(Throwable $e){try{db()->exec('SET FOREIGN_KEY_CHECKS=1');}catch(Throwable $ignore){}if(db()->inTransaction())db()->rollBack();throw $e;}
    return ['deleted'=>$deleted,'errors'=>$errors];
}

function dev_diagnostics(): array {
    $checks=[
      'Users'=>['users','Core account table'], 'Profiles'=>['user_profiles','User identity/profile upgrade'], 'Companies'=>['companies','Company module'], 'Brands'=>['brands','Brand module'],
      'Universes'=>['universes','Universe hierarchy'], 'Universe memberships'=>['user_universes','Universe joins'], 'Billboards'=>['universe_posts','Universe posts and replies'], 'Universe chat'=>['universe_chat_messages','Live universe chat'],
      'Booths'=>['booths','Booth profiles'], 'Booth management'=>['booth_team_members','Teams, gallery, and management'], 'Products'=>['booth_products','Marketplace catalog'], 'Orders'=>['booth_orders','Demo checkout orders'],
      'Developer Center'=>['developer_demo_batches','Test labs and cleanup']
    ];$out=[];foreach($checks as $name=>$c)$out[]=['name'=>$name,'ok'=>dev_table_exists($c[0]),'detail'=>$c[1],'table'=>$c[0]];
    $upload=GNM_ROOT.'/uploads';$out[]=['name'=>'Uploads writable','ok'=>is_dir($upload)&&is_writable($upload),'detail'=>$upload,'table'=>'filesystem'];return $out;
}
