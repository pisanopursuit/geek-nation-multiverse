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
function base_url(string $path = ''): string { return rtrim((string)config('app.url'), '/') . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . (str_starts_with($path,'http') ? $path : base_url($path))); exit; }
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
    if ($u) { echo '<a href="'.e(base_url('dashboard.php')).'">Dashboard</a><a href="'.e(base_url('profile.php?u='.urlencode($u['username']))).'">Profile</a>'; if ($u['role']==='admin') echo '<a href="'.e(base_url('admin/users.php')).'">Admin</a><a href="'.e(base_url('admin/invitations.php')).'">Invitations</a>'; }
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
