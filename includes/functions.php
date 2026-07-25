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
    $u = user();
    $full = e($title . ' | ' . config('app.name'));
    $cartCount = cart_count();

    echo '<!doctype html><html lang="en"><head>';
    echo '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . $full . '</title>';
    echo '<link rel="stylesheet" href="' . e(base_url('styles.css')) . '">';
    echo '<link rel="stylesheet" href="' . e(base_url('assets/navigation-v10.css?v=10.5')) . '">';
    echo '<link rel="stylesheet" href="' . e(base_url('assets/design-system-v10.css?v=10.5')) . '">';
    echo '<link rel="stylesheet" href="' . e(base_url('assets/homepage-v10.5.css?v=10.5')) . '">';
    echo '</head><body><div class="space-bg"></div>';

    echo '<header class="gn-header" data-gn-header>';

    // Tier 1: brand, public destinations, utilities/account.
    echo '<div class="gn-header__primary">';
    echo '<a class="gn-brand" href="' . e(base_url()) . '" aria-label="Geek Nation Multiverse home">';
    echo '<img src="' . e(base_url('assets/geek-nation-multiverse-logo.png')) . '" alt="Geek Nation Multiverse">';
    echo '</a>';

    echo '<button class="gn-menu-button" type="button" aria-expanded="false" aria-controls="gn-public-nav" data-gn-menu-button>';
    echo '<span class="gn-menu-button__icon" aria-hidden="true"></span><span>Menu</span></button>';

    echo '<nav class="gn-public-nav" id="gn-public-nav" aria-label="Primary navigation" data-gn-public-nav>';
    $publicLinks = [
        ['Home', ''],
        ['Explore', 'explore.php'],
        ['Universes', 'universe/index.php'],
        ['Booths', 'booth/index.php'],
        ['Panels & Events', 'events/index.php'],
        ['Artist Alley', 'artist-alley/index.php'],
        ['Multiverse Academy', 'academy/index.php'],
        ['Collectors Marketplace', 'collectors/index.php'],
        ['About', 'about.php'],
    ];
    foreach ($publicLinks as [$label, $path]) {
        echo '<a href="' . e(base_url($path)) . '">' . e($label) . '</a>';
    }
    echo '</nav>';

    echo '<div class="gn-header-tools">';
    echo '<a class="gn-tool-link" href="' . e(base_url('search.php')) . '">Search</a>';
    echo '<a class="gn-tool-link" href="' . e(base_url('cart.php')) . '">Cart <span class="gn-cart-count">' . (int)$cartCount . '</span></a>';

    if ($u) {
        echo '<details class="gn-account">';
        echo '<summary><span class="gn-account__label">' . e($u['display_name']) . '</span><span aria-hidden="true">▾</span></summary>';
        echo '<div class="gn-account__menu">';
        echo '<a href="' . e(base_url('profile.php?u=' . urlencode($u['username']))) . '">My Profile</a>';
        echo '<a href="' . e(base_url('edit-profile.php')) . '">Edit Profile</a>';
        if (($u['role'] ?? '') === 'admin') {
            echo '<a href="' . e(base_url('admin/users.php')) . '">Administration</a>';
        }
        echo '<a href="' . e(base_url('logout.php')) . '">Sign Out</a>';
        echo '</div></details>';
    } else {
        echo '<a class="gn-signin" href="' . e(base_url('login.php')) . '">Sign In</a>';
        echo '<a class="gn-join" href="' . e(base_url('register.php')) . '">Join</a>';
    }
    echo '</div></div>';

    // Tier 2: signed-in member shortcuts only.
    if ($u) {
        echo '<div class="gn-header__member">';
        echo '<div class="gn-member-inner">';
        echo '<span class="gn-member-title">My Multiverse</span>';
        $memberLinks = [
            ['Dashboard', 'dashboard.php'],
            ['My Booths', 'booth/dashboard.php'],
            ['My Events', 'events/dashboard.php'],
            ['My Artist Page', 'artist-alley/dashboard.php'],
            ['My Courses', 'academy/dashboard.php'],
            ['My Collection', 'collectors/dashboard.php'],
        ];
        foreach ($memberLinks as [$label, $path]) {
            echo '<a href="' . e(base_url($path)) . '">' . e($label) . '</a>';
        }
        if (($u['role'] ?? '') === 'admin') {
            echo '<a class="gn-member-admin" href="' . e(base_url('admin/users.php')) . '">Administration</a>';
        }
        echo '</div></div>';
    }

    echo '</header>';
    echo '<main class="app-shell">';
    foreach (flashes() as $f) {
        echo '<div class="alert ' . e($f['type']) . '">' . e($f['message']) . '</div>';
    }
}

function app_footer(): void {
    echo '</main><footer class="site-footer app-footer"><div><strong>Geek Nation Multiverse</strong><p>Created by Marc Delsoin, Abdoul Ba, Trevor Rukwava, &amp; Sean Pisano.</p></div><div><p>Authors: Marc Delsoin, Abdoul Ba, Trevor Rukwava, &amp; Sean Pisano.</p></div></footer>';
    echo '<script src="' . e(base_url('assets/navigation-v10.js?v=10.5')) . '" defer></script>';
    echo '</body></html>';
}

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

function events_schema_ready(): bool { try { db()->query('SELECT 1 FROM events LIMIT 1'); db()->query('SELECT 1 FROM event_attendees LIMIT 1'); return true; } catch(Throwable $e){ return false; } }
function event_type_options(): array { return ['panel'=>'Panel','workshop'=>'Workshop','signing'=>'Signing','meet_greet'=>'Meet & Greet','screening'=>'Screening','tournament'=>'Tournament','competition'=>'Competition','qa'=>'Q&A','cosplay_contest'=>'Cosplay Contest','meetup'=>'Meetup','livestream'=>'Livestream','watch_party'=>'Watch Party','discussion'=>'Discussion','ama'=>'AMA','product_launch'=>'Product Launch','sale'=>'Sale','demo'=>'Demo','showcase'=>'Showcase','class'=>'Class','webinar'=>'Webinar','training'=>'Training']; }
function event_slug(string $title,?int $ignoreId=null): string { $base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$title),'-'))?:'event';$slug=$base;$i=2;while(true){$sql='SELECT COUNT(*) FROM events WHERE slug=?'.($ignoreId?' AND id<>?':'');$st=db()->prepare($sql);$args=[$slug];if($ignoreId)$args[]=$ignoreId;$st->execute($args);if(!(int)$st->fetchColumn())return $slug;$slug=$base.'-'.$i++;} }
function event_by_slug(string $slug): ?array { $s=db()->prepare('SELECT e.*,u.display_name owner_name FROM events e JOIN users u ON u.id=e.owner_user_id WHERE e.slug=? LIMIT 1');$s->execute([$slug]);return $s->fetch()?:null; }
function can_manage_event(array $event,?array $u=null): bool { $u=$u?:user();return $u && (($u['role']??'')==='admin'||(int)$event['owner_user_id']===(int)$u['id']); }
function event_date_label(array $event): string { $start=strtotime((string)$event['starts_at']);$end=strtotime((string)$event['ends_at']);if(!$start||!$end)return '';if(date('Y-m-d',$start)===date('Y-m-d',$end))return date('M j, Y · g:i A',$start).'–'.date('g:i A',$end);return date('M j, Y · g:i A',$start).' – '.date('M j, Y · g:i A',$end); }
function save_event_image(string $field,string $folder,int $eventId): ?string { return save_uploaded_image($field,'events/'.$folder,$eventId); }
function event_speakers(int $eventId): array { $s=db()->prepare('SELECT * FROM event_speakers WHERE event_id=? ORDER BY sort_order,name');$s->execute([$eventId]);return $s->fetchAll(); }
function event_relationships(int $eventId): array { $s=db()->prepare("SELECT r.*,CASE r.entity_type WHEN 'company' THEN (SELECT name FROM companies WHERE id=r.entity_id) WHEN 'brand' THEN (SELECT name FROM brands WHERE id=r.entity_id) WHEN 'booth' THEN (SELECT name FROM booths WHERE id=r.entity_id) WHEN 'universe' THEN (SELECT name FROM universes WHERE id=r.entity_id) END entity_name FROM event_relationships r WHERE r.event_id=? ORDER BY r.entity_type,entity_name");$s->execute([$eventId]);return $s->fetchAll(); }
function event_relationship_choices(): array { $out=[];try{$out['company']=db()->query("SELECT id,name FROM companies WHERE status='approved' ORDER BY name")->fetchAll();}catch(Throwable $e){$out['company']=[];}try{$out['brand']=db()->query("SELECT id,name FROM brands WHERE status='approved' ORDER BY name")->fetchAll();}catch(Throwable $e){$out['brand']=[];}try{$out['booth']=db()->query("SELECT id,name FROM booths WHERE status='approved' ORDER BY name")->fetchAll();}catch(Throwable $e){$out['booth']=[];}try{$out['universe']=db()->query("SELECT id,name FROM universes WHERE status='approved' AND is_active=1 ORDER BY name")->fetchAll();}catch(Throwable $e){$out['universe']=[];}return $out; }
function event_rsvp(int $eventId,int $userId,int $capacity=0): void { $count=(int)db()->query("SELECT COUNT(*) FROM event_attendees WHERE event_id=".$eventId." AND attendee_status IN ('registered','approved','checked_in')")->fetchColumn();$status=($capacity>0&&$count>=$capacity)?'waitlisted':'registered';db()->prepare("INSERT INTO event_attendees(event_id,user_id,attendee_status) VALUES(?,?,?) ON DUPLICATE KEY UPDATE attendee_status=VALUES(attendee_status),registered_at=CURRENT_TIMESTAMP")->execute([$eventId,$userId,$status]); }
function record_event_view(int $eventId): void { $key=session_id()?:($_SERVER['REMOTE_ADDR']??'guest');$uid=user()['id']??null;try{$s=db()->prepare('INSERT IGNORE INTO event_views(event_id,viewer_user_id,session_key,viewed_on) VALUES(?,?,?,CURDATE())');$s->execute([$eventId,$uid,hash('sha256',$key)]);}catch(Throwable $e){} }

function artist_alley_ready(): bool {
    try { db()->query('SELECT 1 FROM artist_profiles LIMIT 1'); db()->query('SELECT 1 FROM artist_commission_requests LIMIT 1'); return true; }
    catch (Throwable $e) { return false; }
}
function artist_slug(string $name, ?int $ignoreId=null): string {
    $base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name),'-')) ?: 'artist';
    $slug=$base;$i=2;
    while(true){$sql='SELECT COUNT(*) FROM artist_profiles WHERE slug=?'.($ignoreId?' AND id<>?':'');$s=db()->prepare($sql);$args=[$slug];if($ignoreId)$args[]=$ignoreId;$s->execute($args);if(!(int)$s->fetchColumn())return $slug;$slug=$base.'-'.$i++;}
}
function artist_by_slug(string $slug): ?array { $s=db()->prepare('SELECT a.*,u.display_name owner_name,u.username owner_username FROM artist_profiles a JOIN users u ON u.id=a.user_id WHERE a.slug=? LIMIT 1');$s->execute([$slug]);return $s->fetch()?:null; }
function artist_by_id(int $id): ?array { $s=db()->prepare('SELECT a.*,u.display_name owner_name,u.username owner_username FROM artist_profiles a JOIN users u ON u.id=a.user_id WHERE a.id=? LIMIT 1');$s->execute([$id]);return $s->fetch()?:null; }
function artist_for_user(int $userId): ?array { $s=db()->prepare('SELECT * FROM artist_profiles WHERE user_id=? LIMIT 1');$s->execute([$userId]);return $s->fetch()?:null; }
function can_manage_artist(array $artist, ?array $u=null): bool { $u=$u?:user();return $u && (($u['role']??'')==='admin' || (int)$artist['user_id']===(int)$u['id']); }
function artist_portfolio_types(): array { return ['artwork'=>'Artwork','comic'=>'Comic','photography'=>'Photography','video'=>'Video','music'=>'Music','writing'=>'Writing','model'=>'3D Model','cosplay'=>'Cosplay','other'=>'Other']; }
function artist_portfolio(int $artistId,bool $publicOnly=true): array { $sql='SELECT * FROM artist_portfolio_items WHERE artist_id=?'.($publicOnly?'':'').' ORDER BY is_featured DESC,sort_order,id DESC';$s=db()->prepare($sql);$s->execute([$artistId]);return $s->fetchAll(); }
function artist_services(int $artistId,bool $activeOnly=true): array { $sql='SELECT * FROM artist_commission_services WHERE artist_id=?'.($activeOnly?' AND is_active=1':'').' ORDER BY id DESC';$s=db()->prepare($sql);$s->execute([$artistId]);return $s->fetchAll(); }
function artist_requests(int $artistId): array { $s=db()->prepare('SELECT r.*,u.display_name customer_name,s.title service_title FROM artist_commission_requests r JOIN users u ON u.id=r.customer_user_id LEFT JOIN artist_commission_services s ON s.id=r.service_id WHERE r.artist_id=? ORDER BY FIELD(r.status,\'submitted\',\'reviewing\',\'accepted\',\'in_progress\',\'proof\',\'completed\',\'declined\',\'cancelled\'),r.created_at DESC');$s->execute([$artistId]);return $s->fetchAll(); }
function artist_followers(int $artistId): array { $s=db()->prepare('SELECT u.display_name,u.username,f.created_at FROM artist_follows f JOIN users u ON u.id=f.user_id WHERE f.artist_id=? ORDER BY f.created_at DESC');$s->execute([$artistId]);return $s->fetchAll(); }
function artist_stats(int $artistId): array { $pdo=db();$counts=[];foreach(['portfolio'=>'artist_portfolio_items','services'=>'artist_commission_services','followers'=>'artist_follows'] as $key=>$table){$counts[$key]=(int)$pdo->query("SELECT COUNT(*) FROM {$table} WHERE artist_id=".$artistId)->fetchColumn();}$counts['requests']=(int)$pdo->query("SELECT COUNT(*) FROM artist_commission_requests WHERE artist_id=".$artistId." AND status NOT IN ('completed','declined','cancelled')")->fetchColumn();return $counts; }


function academy_ready(): bool { try { db()->query('SELECT 1 FROM academy_courses LIMIT 1'); db()->query('SELECT 1 FROM academy_enrollments LIMIT 1'); return true; } catch(Throwable $e){ return false; } }
function academy_levels(): array { return ['beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced','all_levels'=>'All Levels']; }
function academy_formats(): array { return ['self_paced'=>'Self-Paced','live'=>'Live','hybrid'=>'Hybrid']; }
function academy_lesson_types(): array { return ['video'=>'Video','article'=>'Article','download'=>'Download','quiz'=>'Quiz','live_session'=>'Live Session','assignment'=>'Assignment']; }
function academy_slug(string $title,?int $ignoreId=null): string { $base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$title),'-'))?:'course';$slug=$base;$i=2;while(true){$sql='SELECT COUNT(*) FROM academy_courses WHERE slug=?'.($ignoreId?' AND id<>?':'');$st=db()->prepare($sql);$args=[$slug];if($ignoreId)$args[]=$ignoreId;$st->execute($args);if(!(int)$st->fetchColumn())return $slug;$slug=$base.'-'.$i++;} }
function academy_by_slug(string $slug): ?array { $s=db()->prepare('SELECT c.*,u.display_name owner_name,u.username owner_username FROM academy_courses c JOIN users u ON u.id=c.owner_user_id WHERE c.slug=? LIMIT 1');$s->execute([$slug]);return $s->fetch()?:null; }
function academy_by_id(int $id): ?array { $s=db()->prepare('SELECT c.*,u.display_name owner_name,u.username owner_username FROM academy_courses c JOIN users u ON u.id=c.owner_user_id WHERE c.id=? LIMIT 1');$s->execute([$id]);return $s->fetch()?:null; }
function can_manage_course(array $course,?array $u=null): bool { $u=$u?:user();if(!$u)return false;if(($u['role']??'')==='admin'||(int)$course['owner_user_id']===(int)$u['id'])return true;$s=db()->prepare('SELECT COUNT(*) FROM academy_instructors WHERE course_id=? AND user_id=?');$s->execute([$course['id'],$u['id']]);return (bool)$s->fetchColumn(); }
function academy_lessons(int $courseId): array { $s=db()->prepare('SELECT * FROM academy_lessons WHERE course_id=? ORDER BY sort_order,id');$s->execute([$courseId]);return $s->fetchAll(); }
function academy_instructors(int $courseId): array { $s=db()->prepare("SELECT i.*,u.display_name,u.username,u.email FROM academy_instructors i JOIN users u ON u.id=i.user_id WHERE i.course_id=? ORDER BY i.sort_order,FIELD(i.role,'lead','instructor','assistant','guest'),u.display_name");$s->execute([$courseId]);return $s->fetchAll(); }
function academy_students(int $courseId): array { $s=db()->prepare('SELECT e.*,u.display_name,u.username,u.email FROM academy_enrollments e JOIN users u ON u.id=e.user_id WHERE e.course_id=? ORDER BY e.enrolled_at DESC');$s->execute([$courseId]);return $s->fetchAll(); }
function academy_enrollment(int $courseId,int $userId): ?array { $s=db()->prepare('SELECT * FROM academy_enrollments WHERE course_id=? AND user_id=? LIMIT 1');$s->execute([$courseId,$userId]);return $s->fetch()?:null; }
function academy_enroll(int $courseId,int $userId,int $capacity=0): void { $count=(int)db()->query("SELECT COUNT(*) FROM academy_enrollments WHERE course_id=".$courseId." AND status IN ('enrolled','in_progress','completed')")->fetchColumn();$status=($capacity>0&&$count>=$capacity)?'waitlisted':'enrolled';db()->prepare('INSERT INTO academy_enrollments(course_id,user_id,status) VALUES(?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)')->execute([$courseId,$userId,$status]); }
function academy_completed_lesson_ids(int $courseId,int $userId): array { $s=db()->prepare('SELECT p.lesson_id FROM academy_lesson_progress p JOIN academy_lessons l ON l.id=p.lesson_id WHERE l.course_id=? AND p.user_id=?');$s->execute([$courseId,$userId]);return array_map('intval',$s->fetchAll(PDO::FETCH_COLUMN)); }
function academy_complete_lesson(int $lessonId,int $courseId,int $userId): void { $s=db()->prepare('SELECT COUNT(*) FROM academy_lessons WHERE id=? AND course_id=?');$s->execute([$lessonId,$courseId]);if(!(int)$s->fetchColumn())throw new RuntimeException('Lesson not found.');db()->prepare('INSERT IGNORE INTO academy_lesson_progress(lesson_id,user_id) VALUES(?,?)')->execute([$lessonId,$userId]);$total=(int)db()->query('SELECT COUNT(*) FROM academy_lessons WHERE course_id='.$courseId)->fetchColumn();$done=count(academy_completed_lesson_ids($courseId,$userId));$progress=$total?min(100,(int)round($done/$total*100)):0;$status=$progress>=100?'completed':($progress>0?'in_progress':'enrolled');db()->prepare('UPDATE academy_enrollments SET progress_percent=?,status=?,completed_at=? WHERE course_id=? AND user_id=?')->execute([$progress,$status,$status==='completed'?date('Y-m-d H:i:s'):null,$courseId,$userId]); }
function academy_stats(int $courseId): array { $pdo=db();$lessons=(int)$pdo->query('SELECT COUNT(*) FROM academy_lessons WHERE course_id='.$courseId)->fetchColumn();$students=(int)$pdo->query("SELECT COUNT(*) FROM academy_enrollments WHERE course_id={$courseId} AND status<>'cancelled'")->fetchColumn();$completed=(int)$pdo->query("SELECT COUNT(*) FROM academy_enrollments WHERE course_id={$courseId} AND status='completed'")->fetchColumn();$average=(int)$pdo->query("SELECT COALESCE(ROUND(AVG(progress_percent)),0) FROM academy_enrollments WHERE course_id={$courseId} AND status<>'cancelled'")->fetchColumn();return compact('lessons','students','completed','average'); }
function academy_datetime_local(?string $value): string { return $value?date('Y-m-d\TH:i',strtotime($value)):''; }

function collector_marketplace_ready(): bool { try{db()->query('SELECT 1 FROM collector_profiles LIMIT 1');return true;}catch(Throwable $e){return false;} }
function collector_categories(): array { return ['comics'=>'Comics','trading_cards'=>'Trading Cards','action_figures'=>'Action Figures','statues'=>'Statues','toys'=>'Toys','games'=>'Games','movies'=>'Movies','books'=>'Books','posters'=>'Posters','props'=>'Props','autographs'=>'Autographs','memorabilia'=>'Memorabilia','other'=>'Other']; }
function collector_listing_types(): array { return ['sale'=>'For Sale','trade'=>'For Trade','wanted'=>'Wanted','showcase'=>'Showcase Only']; }
function collector_slug(string $name,int $ignore=0): string {$slug=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name),'-'))?:'collector';$base=$slug;$i=2;$s=db()->prepare('SELECT COUNT(*) FROM collector_profiles WHERE slug=? AND id<>?');while(true){$s->execute([$slug,$ignore]);if(!(int)$s->fetchColumn())return $slug;$slug=$base.'-'.$i++;}}
function collector_item_slug(string $name,int $ignore=0): string {$slug=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name),'-'))?:'item';$base=$slug;$i=2;$s=db()->prepare('SELECT COUNT(*) FROM collector_items WHERE slug=? AND id<>?');while(true){$s->execute([$slug,$ignore]);if(!(int)$s->fetchColumn())return $slug;$slug=$base.'-'.$i++;}}
function collector_profile_by_user(int $uid): ?array {$s=db()->prepare('SELECT cp.*,u.display_name,u.username FROM collector_profiles cp JOIN users u ON u.id=cp.user_id WHERE cp.user_id=?');$s->execute([$uid]);return $s->fetch()?:null;}
function collector_profile_by_slug(string $slug): ?array {$s=db()->prepare('SELECT cp.*,u.display_name,u.username FROM collector_profiles cp JOIN users u ON u.id=cp.user_id WHERE cp.slug=?');$s->execute([$slug]);return $s->fetch()?:null;}
function collector_item_by_slug(string $slug): ?array {$s=db()->prepare('SELECT i.*,cp.shop_name,cp.slug collector_slug,cp.user_id seller_user_id,u.display_name seller_name FROM collector_items i JOIN collector_profiles cp ON cp.id=i.collector_id JOIN users u ON u.id=cp.user_id WHERE i.slug=?');$s->execute([$slug]);return $s->fetch()?:null;}
function can_manage_collector(array $p,?array $u=null): bool {$u=$u?:user();return $u&&(($u['role']??'')==='admin'||(int)$u['id']===(int)$p['user_id']);}
