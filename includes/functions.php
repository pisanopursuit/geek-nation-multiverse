<?php
declare(strict_types=1);

function config(string $key, mixed $default = null): mixed {
    global $config;
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) return $default;
        $value = $value[$part];
    }
    return $value;
}
function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', config('database.host'), config('database.port',3306), config('database.name'), config('database.charset','utf8mb4'));
    $pdo = new PDO($dsn, config('database.user'), config('database.password'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function base_url(string $path = ''): string {
    $base = rtrim((string)config('app.url'), '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}
function route_url(string $path, array $query = []): string {
    if (preg_match('#^https?://#i', $path)) {
        $url = $path;
    } else {
        // Application routes are always rooted at app.url. Reject parent-directory
        // traversal so a file in /booth or /admin cannot accidentally redirect to
        // a non-existent root-level page.
        $path = preg_replace('#^(?:\.\./)+#', '', trim($path));
        $url = base_url($path);
    }
    if ($query) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
    }
    return $url;
}
function redirect(string $path): never { header('Location: ' . route_url($path)); exit; }
function redirect_route(string $path, array $query = []): never { header('Location: ' . route_url($path, $query)); exit; }
function booth_manage_url(int $boothId, string $tab = 'overview'): string {
    return route_url('booth/manage.php', ['id' => $boothId, 'tab' => $tab]);
}
function redirect_booth_manage(int $boothId, string $tab = 'overview'): never {
    header('Location: ' . booth_manage_url($boothId, $tab));
    exit;
}
function booth_public_url(string $slug): string { return route_url('booth/view.php', ['slug' => $slug]); }
function product_public_url(string $slug): string { return route_url('booth/product.php', ['slug' => $slug]); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Your session expired. Please go back and try again.'); } }
function flash(string $type, string $message): void { $_SESSION['flash'][] = compact('type','message'); }
function flashes(): array { $items = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $items; }
function user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    static $cached;
    if ($cached && (int)$cached['id'] === (int)$_SESSION['user_id']) return $cached;
    $s = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1'); $s->execute([$_SESSION['user_id']]);
    return $cached = ($s->fetch() ?: null);
}
function require_auth(): void { if (!user()) { flash('error','Please sign in to continue.'); redirect('login.php'); } }
function require_admin(): void { require_auth(); if ((user()['role'] ?? '') !== 'admin') { http_response_code(403); exit('Administrator access required.'); } }
function app_header(string $title): void {
    $u=user(); $full=e($title.' | '.config('app.name')); echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$full.'</title><link rel="stylesheet" href="'.e(base_url('styles.css')).'"></head><body><div class="space-bg"></div><header class="site-header"><a class="brand" href="'.e(base_url()).'"><img src="'.e(base_url('assets/geek-nation-multiverse-logo.png')).'" alt="Geek Nation Multiverse"></a><nav class="main-nav"><a href="'.e(base_url()).'">Home</a>';
    if ($u) { echo '<a href="'.e(base_url('dashboard.php')).'">Dashboard</a><a href="'.e(base_url('company/index.php')).'">Companies</a><a href="'.e(base_url('brand/index.php')).'">Brands</a><a href="'.e(base_url('universe/index.php')).'">Universes</a><a href="'.e(base_url('booth/index.php')).'">Booths</a><a href="'.e(base_url('cart.php')).'">Cart ('.cart_count().')</a><a href="'.e(base_url('profile.php?u='.urlencode($u['username']))).'">Profile</a>'; if ($u['role']==='admin') echo '<a href="'.e(base_url('admin/users.php')).'">Admin</a><a href="'.e(base_url('admin/brands.php')).'">Brand Approvals</a><a href="'.e(base_url('admin/imports.php')).'">Import Center</a><a href="'.e(base_url('admin/universes.php')).'">Universe Admin</a><a href="'.e(base_url('admin/booths.php')).'">Booth Admin</a><a href="'.e(base_url('admin/developer-center.php')).'">Developer Center</a><a href="'.e(base_url('admin/invitations.php')).'">Invitations</a>'; }
    echo '</nav><div class="header-actions">';
    if ($u) echo '<span class="user-chip">'.e($u['display_name']).'</span><a class="button ghost" href="'.e(base_url('logout.php')).'">Sign Out</a>'; else echo '<a class="button ghost" href="'.e(base_url('login.php')).'">Sign In</a><a class="button primary" href="'.e(base_url('register.php')).'">Join</a>';
    echo '</div></header><main class="app-shell">';
    foreach (flashes() as $f) echo '<div class="alert '.e($f['type']).'">'.e($f['message']).'</div>';
}
function app_footer(): void { echo '</main><footer class="site-footer app-footer"><div><strong>Geek Nation Multiverse</strong><p>Created by Marc Delsoin, Abdoul Ba, Trevor Rukwava, &amp; Sean Pisano.</p></div><div><p>Authors: Marc Delsoin, Abdoul Ba, Trevor Rukwava, &amp; Sean Pisano.</p></div></footer></body></html>'; }

function run_sql_file(string $file): void {
    $sql = file_get_contents($file);
    if ($sql === false) throw new RuntimeException('Could not read database upgrade file.');
    foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $query) {
        db()->exec($query);
    }
}
function identity_schema_ready(): bool {
    try { db()->query('SELECT 1 FROM user_profiles LIMIT 1'); return true; }
    catch (Throwable $e) { return false; }
}
function ensure_profile(int $userId): void {
    db()->prepare('INSERT IGNORE INTO user_profiles(user_id) VALUES(?)')->execute([$userId]);
    db()->prepare('INSERT IGNORE INTO user_preferences(user_id) VALUES(?)')->execute([$userId]);
}
function profile_for_user(int $userId): array {
    ensure_profile($userId);
    $s=db()->prepare('SELECT u.*,p.bio,p.location,p.website,p.avatar_path,p.banner_path,p.visibility,p.onboarding_step,p.onboarding_completed_at FROM users u JOIN user_profiles p ON p.user_id=u.id WHERE u.id=?');
    $s->execute([$userId]); return $s->fetch() ?: [];
}
function selections(string $table, string $joinTable, string $fk, int $userId): array {
    $sql="SELECT t.* FROM {$table} t JOIN {$joinTable} j ON j.{$fk}=t.id WHERE j.user_id=? ORDER BY t.sort_order,t.name";
    $s=db()->prepare($sql);$s->execute([$userId]);return $s->fetchAll();
}
function replace_selections(string $joinTable, string $fk, int $userId, array $ids): void {
    db()->prepare("DELETE FROM {$joinTable} WHERE user_id=?")->execute([$userId]);
    if (!$ids) return;
    $stmt=db()->prepare("INSERT IGNORE INTO {$joinTable}(user_id,{$fk}) VALUES(?,?)");
    foreach(array_unique(array_map('intval',$ids)) as $id) if($id>0) $stmt->execute([$userId,$id]);
}
function taxonomy(string $table): array {
    return db()->query("SELECT * FROM {$table} WHERE is_active=1 ORDER BY sort_order,name")->fetchAll();
}
function save_uploaded_image(string $field, string $folder, int $userId): ?string {
    if (empty($_FILES[$field]['name'])) return null;
    $file=$_FILES[$field];
    if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed.');
    if (($file['size']??0)>5*1024*1024) throw new RuntimeException('Images must be 5 MB or smaller.');
    $info=@getimagesize($file['tmp_name']);
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if(!$info || !isset($allowed[$info['mime']])) throw new RuntimeException('Upload a JPG, PNG, WEBP, or GIF image.');
    $dir=GNM_ROOT.'/uploads/'.$folder;
    if(!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('Could not create upload directory.');
    $name=$userId.'-'.bin2hex(random_bytes(8)).'.'.$allowed[$info['mime']];
    if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('Could not save image.');
    return 'uploads/'.$folder.'/'.$name;
}
function profile_completion(array $p): array {
    $checks=[
      'Display name'=>!empty($p['display_name']), 'Avatar'=>!empty($p['avatar_path']), 'Bio'=>!empty($p['bio']),
      'Identity'=>count(selections('identity_types','user_identity_types','identity_type_id',(int)$p['id']))>0,
      'Interests'=>count(selections('interests','user_interests','interest_id',(int)$p['id']))>0,
      'Favorite universes'=>count(selections('universes','user_universes','universe_id',(int)$p['id']))>0,
      'Banner'=>!empty($p['banner_path']), 'Website or social link'=>!empty($p['website']) || (int)db()->query('SELECT COUNT(*) FROM user_social_links WHERE user_id='.(int)$p['id'])->fetchColumn()>0,
    ];
    $done=count(array_filter($checks)); return ['percent'=>(int)round($done/count($checks)*100),'checks'=>$checks];
}
function onboarding_required(?array $u=null): bool {
    $u=$u?:user(); if(!$u || !identity_schema_ready()) return false;
    $p=profile_for_user((int)$u['id']); return empty($p['onboarding_completed_at']);
}

function invitations_schema_ready(): bool {
    try { db()->query('SELECT 1 FROM invitations LIMIT 1'); return true; }
    catch (Throwable $e) { return false; }
}
function expire_invitations(): void {
    if (!invitations_schema_ready()) return;
    db()->exec("UPDATE invitations SET status='expired' WHERE status='pending' AND expires_at < NOW()");
}
function invitation_by_token(string $token): ?array {
    if ($token === '' || !invitations_schema_ready()) return null;
    expire_invitations();
    $s=db()->prepare("SELECT i.*,u.display_name AS inviter_name FROM invitations i JOIN users u ON u.id=i.invited_by WHERE i.token_hash=? LIMIT 1");
    $s->execute([hash('sha256',$token)]);
    return $s->fetch() ?: null;
}
function invitation_email_html(array $invitation, string $rawToken): string {
    $role=$invitation['assigned_role']==='admin' ? 'Administrator' : 'Member';
    $link=base_url('accept-invitation.php?token='.urlencode($rawToken));
    $name=trim((string)($invitation['recipient_name']??''));
    $greeting=$name!=='' ? 'Hi '.e($name).',' : 'Hello,';
    $message=trim((string)($invitation['personal_message']??''));
    return '<div style="font-family:Arial,sans-serif;max-width:640px;margin:auto;background:#10111e;color:#f7f7fb;padding:32px;border-radius:18px">'
      .'<h1 style="margin-top:0">You’re invited to Geek Nation Multiverse</h1><p>'.$greeting.'</p>'
      .'<p>'.e($invitation['inviter_name']??'A Geek Nation Multiverse administrator').' invited you to join as a <strong>'.e($role).'</strong>.</p>'
      .($message!==''?'<blockquote style="border-left:3px solid #27d7ff;margin:20px 0;padding:10px 16px;color:#d7d9e5">'.nl2br(e($message)).'</blockquote>':'')
      .'<p><a href="'.e($link).'" style="display:inline-block;background:#6f4cff;color:white;text-decoration:none;padding:13px 20px;border-radius:10px;font-weight:bold">Accept invitation</a></p>'
      .'<p style="color:#aaaec2">This single-use invitation expires in 7 days. If you were not expecting it, you can ignore this email.</p></div>';
}

function companies_schema_ready(): bool {
    try { db()->query('SELECT 1 FROM companies LIMIT 1'); return true; }
    catch (Throwable $e) { return false; }
}
function company_slug(string $name): string {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    if ($slug === '') $slug = 'company';
    $base=$slug; $i=2;
    $s=db()->prepare('SELECT COUNT(*) FROM companies WHERE slug=?');
    while(true){$s->execute([$slug]); if((int)$s->fetchColumn()===0)return $slug; $slug=$base.'-'.$i++;}
}
function company_by_slug(string $slug): ?array {
    $s=db()->prepare('SELECT c.*,u.display_name AS submitter_name,u.username AS submitter_username FROM companies c JOIN users u ON u.id=c.submitted_by WHERE c.slug=? LIMIT 1');
    $s->execute([$slug]); return $s->fetch() ?: null;
}
function can_manage_company(array $company, ?array $u=null): bool {
    $u=$u?:user(); if(!$u)return false; if(($u['role']??'')==='admin')return true;
    $s=db()->prepare("SELECT COUNT(*) FROM company_members WHERE company_id=? AND user_id=? AND status='active' AND company_role IN ('owner','company_admin')");
    $s->execute([(int)$company['id'],(int)$u['id']]); return (int)$s->fetchColumn()>0;
}
function company_member_for_user(int $companyId,int $userId): ?array {
    $s=db()->prepare('SELECT * FROM company_members WHERE company_id=? AND user_id=? LIMIT 1');$s->execute([$companyId,$userId]);return $s->fetch()?:null;
}

function brands_schema_ready(): bool {
    try { db()->query('SELECT 1 FROM brands LIMIT 1'); db()->query('SELECT 1 FROM import_batches LIMIT 1'); return true; }
    catch (Throwable $e) { return false; }
}
function brand_slug(string $name): string {
    $slug=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name),'-')) ?: 'brand';$base=$slug;$i=2;$s=db()->prepare('SELECT COUNT(*) FROM brands WHERE slug=?');
    while(true){$s->execute([$slug]);if((int)$s->fetchColumn()===0)return $slug;$slug=$base.'-'.$i++;}
}
function brand_by_slug(string $slug): ?array {
    $s=db()->prepare('SELECT b.*,c.name AS company_name,c.slug AS company_slug,u.display_name AS submitter_name FROM brands b JOIN companies c ON c.id=b.company_id JOIN users u ON u.id=b.submitted_by WHERE b.slug=? LIMIT 1');$s->execute([$slug]);return $s->fetch()?:null;
}
function can_manage_brand(array $brand, ?array $u=null): bool {
    $u=$u?:user();if(!$u)return false;if(($u['role']??'')==='admin')return true;
    $s=db()->prepare("SELECT COUNT(*) FROM brand_members WHERE brand_id=? AND user_id=? AND status='active' AND brand_role='manager'");$s->execute([(int)$brand['id'],(int)$u['id']]);if((int)$s->fetchColumn()>0)return true;
    $cs=db()->prepare('SELECT * FROM companies WHERE id=?');$cs->execute([(int)$brand['company_id']]);$company=$cs->fetch();return $company?can_manage_company($company,$u):false;
}
function manageable_companies(int $userId): array {
    if((user()['role']??'')==='admin')return db()->query("SELECT * FROM companies WHERE status='approved' ORDER BY name")->fetchAll();
    $s=db()->prepare("SELECT c.* FROM companies c JOIN company_members cm ON cm.company_id=c.id WHERE cm.user_id=? AND cm.status='active' AND cm.company_role IN ('owner','company_admin') AND c.status='approved' ORDER BY c.name");$s->execute([$userId]);return $s->fetchAll();
}
function csv_rows(string $path): array {
    $h=fopen($path,'rb');if(!$h)throw new RuntimeException('Could not read uploaded CSV.');$first=fgets($h);if($first===false){fclose($h);return [];}$delimiter=substr_count($first,"\t")>substr_count($first,',')?"\t":',';rewind($h);$headers=fgetcsv($h,0,$delimiter);if(!$headers){fclose($h);return [];}$headers=array_map(fn($v)=>strtolower(trim(preg_replace('/[^a-z0-9]+/i','_',preg_replace('/^\xEF\xBB\xBF/','',(string)$v)),'_')),$headers);$rows=[];while(($values=fgetcsv($h,0,$delimiter))!==false){if(count(array_filter($values,fn($v)=>trim((string)$v)!==''))===0)continue;$values=array_pad($values,count($headers),'');$rows[]=array_combine($headers,array_slice($values,0,count($headers)));}fclose($h);return $rows;
}


function universe_engine_ready(): bool {
    try { db()->query('SELECT parent_id,primary_color FROM universes LIMIT 1'); db()->query('SELECT 1 FROM universe_activity LIMIT 1'); return true; }
    catch (Throwable $e) { return false; }
}
function universe_slug(string $name, ?int $ignoreId=null): string {
    $slug=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name),'-')) ?: 'universe';
    $base=$slug;$i=2;
    while(true){
        $sql='SELECT COUNT(*) FROM universes WHERE slug=?'.($ignoreId?' AND id<>?':'');
        $st=db()->prepare($sql);$args=[$slug];if($ignoreId)$args[]=$ignoreId;$st->execute($args);
        if((int)$st->fetchColumn()===0)return $slug;$slug=$base.'-'.$i++;
    }
}
function universe_by_slug(string $slug): ?array {
    $s=db()->prepare("SELECT u.*,p.name AS parent_name,p.slug AS parent_slug,(SELECT COUNT(*) FROM user_universes uu WHERE uu.universe_id=u.id) AS member_count,(SELECT COUNT(*) FROM universes c WHERE c.parent_id=u.id AND c.status='approved' AND c.is_active=1) AS child_count FROM universes u LEFT JOIN universes p ON p.id=u.parent_id WHERE u.slug=? LIMIT 1");
    $s->execute([$slug]);return $s->fetch()?:null;
}
function universe_children(int $parentId,bool $publicOnly=true): array {
    $sql='SELECT u.*,(SELECT COUNT(*) FROM user_universes uu WHERE uu.universe_id=u.id) AS member_count FROM universes u WHERE u.parent_id=?';
    if($publicOnly)$sql.=" AND u.status='approved' AND u.is_active=1";
    $sql.=' ORDER BY u.sort_order,u.name';$s=db()->prepare($sql);$s->execute([$parentId]);return $s->fetchAll();
}
function universe_breadcrumbs(array $universe): array {
    $trail=[];$current=$universe;$guard=0;
    while($current && $guard++<20){array_unshift($trail,$current);if(empty($current['parent_id']))break;$s=db()->prepare('SELECT * FROM universes WHERE id=?');$s->execute([(int)$current['parent_id']]);$current=$s->fetch()?:null;}
    return $trail;
}
function user_joined_universe(int $universeId,int $userId): bool {
    $s=db()->prepare('SELECT COUNT(*) FROM user_universes WHERE universe_id=? AND user_id=?');$s->execute([$universeId,$userId]);return (int)$s->fetchColumn()>0;
}
function can_manage_universe(array $universe,?array $u=null): bool {
    $u=$u?:user();if(!$u)return false;if(($u['role']??'')==='admin')return true;
    $s=db()->prepare("SELECT COUNT(*) FROM universe_moderators WHERE universe_id=? AND user_id=? AND role IN ('owner','moderator')");$s->execute([(int)$universe['id'],(int)$u['id']]);return (int)$s->fetchColumn()>0;
}
function universe_theme_vars(array $u): string {
    $safe=function($v,$fallback){$v=trim((string)$v);return preg_match('/^#[0-9a-fA-F]{3,8}$/',$v)?$v:$fallback;};
    return '--universe-primary:'.$safe($u['primary_color']??'','#6f4cff').';--universe-secondary:'.$safe($u['secondary_color']??'','#15172a').';--universe-accent:'.$safe($u['accent_color']??'','#27d7ff').';--universe-bg:'.$safe($u['background_color']??'','#070812').';--universe-surface:'.$safe($u['surface_color']??'','#111321').';--universe-text:'.$safe($u['text_color']??'','#f7f7fb').';';
}
function universe_image_upload(string $field,string $folder,int $universeId): ?string {
    return save_uploaded_image($field,'universes/'.$folder,$universeId);
}


function booths_schema_ready(): bool { try { db()->query('SELECT 1 FROM booths LIMIT 1'); db()->query('SELECT 1 FROM booth_products LIMIT 1'); return true; } catch(Throwable $e){ return false; } }
function booth_slug(string $name,?int $ignoreId=null): string { $base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name),'-'))?:'booth';$slug=$base;$i=2;while(true){$sql='SELECT COUNT(*) FROM booths WHERE slug=?'.($ignoreId?' AND id<>?':'');$st=db()->prepare($sql);$args=[$slug];if($ignoreId)$args[]=$ignoreId;$st->execute($args);if(!(int)$st->fetchColumn())return $slug;$slug=$base.'-'.$i++;} }
function booth_by_slug(string $slug): ?array { $s=db()->prepare("SELECT b.*,u.display_name AS owner_name,c.name AS company_name,c.slug AS company_slug,br.name AS brand_name,br.slug AS brand_slug FROM booths b JOIN users u ON u.id=b.owner_user_id LEFT JOIN companies c ON c.id=b.company_id LEFT JOIN brands br ON br.id=b.brand_id WHERE b.slug=? LIMIT 1");$s->execute([$slug]);return $s->fetch()?:null; }
function can_manage_booth(array $booth,?array $u=null): bool { $u=$u?:user();if(!$u)return false;if(($u['role']??'')==='admin'||(int)$booth['owner_user_id']===(int)$u['id'])return true;if(!booth_management_ready())return false;$role=booth_team_role((int)$booth['id'],(int)$u['id']);return in_array($role,['manager'],true); }
function product_slug(string $name,int $boothId,?int $ignoreId=null): string { $base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name),'-'))?:'product';$slug=$base;$i=2;while(true){$sql='SELECT COUNT(*) FROM booth_products WHERE booth_id=? AND slug=?'.($ignoreId?' AND id<>?':'');$st=db()->prepare($sql);$args=[$boothId,$slug];if($ignoreId)$args[]=$ignoreId;$st->execute($args);if(!(int)$st->fetchColumn())return $slug;$slug=$base.'-'.$i++;} }
function cart_items(): array { return $_SESSION['booth_cart']??[]; }
function cart_count(): int { return array_sum(array_map('intval',cart_items())); }
function cart_add(int $productId,int $qty=1): void { $qty=max(1,min(99,$qty));$_SESSION['booth_cart'][$productId]=min(99,(int)($_SESSION['booth_cart'][$productId]??0)+$qty); }
function cart_remove(int $productId): void { unset($_SESSION['booth_cart'][$productId]); }
function cart_details(): array { $cart=cart_items();if(!$cart)return ['items'=>[],'subtotal'=>0,'booth_id'=>null];$ids=array_keys($cart);$ph=implode(',',array_fill(0,count($ids),'?'));$s=db()->prepare("SELECT p.*,b.name AS booth_name,b.slug AS booth_slug,b.status AS booth_status FROM booth_products p JOIN booths b ON b.id=p.booth_id WHERE p.id IN ($ph) AND p.status='active' AND b.status='approved'");$s->execute($ids);$rows=$s->fetchAll();$items=[];$subtotal=0;$boothId=null;foreach($rows as $r){if($boothId===null)$boothId=(int)$r['booth_id'];if((int)$r['booth_id']!==$boothId)continue;$qty=max(1,(int)($cart[$r['id']]??1));if($r['inventory_quantity']!==null)$qty=min($qty,max(0,(int)$r['inventory_quantity']));if($qty<1)continue;$r['quantity']=$qty;$r['line_total']=$qty*(float)$r['price'];$subtotal+=$r['line_total'];$items[]=$r;}return ['items'=>$items,'subtotal'=>$subtotal,'booth_id'=>$boothId]; }
function save_booth_image(string $field,string $folder,int $id): ?string { return save_uploaded_image($field,'booths/'.$folder,$id); }

function booth_management_ready(): bool { try { db()->query('SELECT booth_presence FROM booths LIMIT 1'); db()->query('SELECT 1 FROM booth_team_members LIMIT 1'); return true; } catch(Throwable $e){ return false; } }
function booth_team_role(int $boothId,int $userId): ?string { $s=db()->prepare("SELECT role FROM booth_team_members WHERE booth_id=? AND user_id=? AND status='active' LIMIT 1");$s->execute([$boothId,$userId]);return $s->fetchColumn()?:null; }
function save_booth_file(string $field,string $folder,int $boothId,array $extensions=['pdf','doc','docx','zip','txt']): ?string {
 if(empty($_FILES[$field]['name']))return null;$f=$_FILES[$field];if(($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('File upload failed.');if(($f['size']??0)>10*1024*1024)throw new RuntimeException('Files must be 10 MB or smaller.');$ext=strtolower(pathinfo((string)$f['name'],PATHINFO_EXTENSION));if(!in_array($ext,$extensions,true))throw new RuntimeException('Unsupported file type.');$dir=GNM_ROOT.'/uploads/booths/'.$folder;if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Could not create upload directory.');$name=$boothId.'-'.bin2hex(random_bytes(8)).'.'.$ext;if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Could not save file.');return 'uploads/booths/'.$folder.'/'.$name;
}
function record_booth_view(int $boothId): void { if(!booth_management_ready())return;$key=session_id()?:($_SERVER['REMOTE_ADDR']??'guest');$uid=user()['id']??null;try{$s=db()->prepare('INSERT IGNORE INTO booth_views(booth_id,viewer_user_id,session_key,viewed_on) VALUES(?,?,?,CURDATE())');$s->execute([$boothId,$uid,hash('sha256',$key)]);}catch(Throwable $e){} }
