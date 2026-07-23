<?php
require __DIR__.'/../includes/bootstrap.php';
require_admin();
require_once GNM_ROOT.'/includes/developer-center.php';

if(!dev_table_exists('developer_demo_batches')) redirect('upgrade-developer-center.php');

$action=$_POST['action']??'';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        if($action==='generate_complete'){
            $result=dev_generate_complete((int)user()['id']);
            $_SESSION['dev_last_batch']=$result;
            flash('success','Complete test environment created and all demo passwords verified. Password: '.$result['password']);
        }elseif($action==='reset_demo_passwords'){
            $batchId=(int)($_POST['batch_id']??0);
            $result=dev_reset_batch_passwords($batchId);
            $_SESSION['dev_last_batch']=['batch'=>['label'=>'Demo accounts repaired'],'password'=>$result['password'],'accounts'=>$result['accounts']];
            flash('success','Reset and verified '.$result['verified'].' demo accounts. Password: '.$result['password']);
        }elseif($action==='login_as_demo'){
            $targetId=(int)($_POST['user_id']??0);
            $allowed=false;
            foreach(dev_batch_user_ids((int)($_POST['batch_id']??0)) as $demoId){if($demoId===$targetId){$allowed=true;break;}}
            if(!$allowed) throw new RuntimeException('That user is not part of the selected demo batch.');
            $_SESSION['return_admin_user_id']=(int)user()['id'];
            session_regenerate_id(true);
            $_SESSION['user_id']=$targetId;
            redirect('dashboard.php');
        }elseif($action==='cleanup_batch'){
            $batchId=(int)($_POST['batch_id']??0);$result=dev_cleanup_batch($batchId);
            flash($result['errors']?'error':'success','Cleanup removed '.$result['deleted'].' demo records'.($result['errors']?' with '.count($result['errors']).' warnings.':'.'));
        }elseif($action==='cleanup_all'){
            $ids=db()->query("SELECT id FROM developer_demo_batches WHERE status IN ('ready','partial','failed') ORDER BY id DESC")->fetchAll(PDO::FETCH_COLUMN);$total=0;
            foreach($ids as $id){$r=dev_cleanup_batch((int)$id);$total+=$r['deleted'];}
            flash('success','All active demo batches were cleaned up. Removed '.$total.' tracked records.');
        }
    }catch(Throwable $e){flash('error','Developer Center action failed: '.$e->getMessage());}
    redirect('admin/developer-center.php');
}

$diagnostics=dev_diagnostics();
$passed=count(array_filter($diagnostics,fn($x)=>$x['ok']));
$batches=db()->query('SELECT b.*,u.display_name AS creator_name,(SELECT COUNT(*) FROM developer_demo_records r WHERE r.batch_id=b.id) AS record_count FROM developer_demo_batches b JOIN users u ON u.id=b.created_by ORDER BY b.id DESC LIMIT 30')->fetchAll();
$active=(int)db()->query("SELECT COUNT(*) FROM developer_demo_batches WHERE status IN ('building','ready','partial','failed')")->fetchColumn();
$last=$_SESSION['dev_last_batch']??null;unset($_SESSION['dev_last_batch']);
app_header('Developer Center');
?>
<section class="dashboard-hero developer-hero">
  <p class="eyebrow">ADMINISTRATOR TOOLS</p>
  <h1>Developer Center</h1>
  <p class="lede">Generate connected test content, verify each completed module, and remove only records created by a specific demo batch.</p>
</section>

<?php if($last):?>
<section class="app-card dev-login-card">
  <div>
    <p class="eyebrow">TEST ENVIRONMENT READY</p>
    <h2><?=e($last['batch']['label'])?></h2>
    <p>All demo accounts use the password <code><?=e($last['password'])?></code>.</p>
  </div>
  <a class="button primary" href="<?=e(base_url('booth/index.php'))?>">Open Booths</a>
</section>
<?php if(!empty($last['accounts'])):?>
<section class="app-card">
  <p class="eyebrow">DEMO LOGIN ACCOUNTS</p>
  <h2>Use any account below</h2>
  <p class="muted">Sign out of the administrator account, open the sign-in page, and use one of these usernames with the shared password above.</p>
  <div class="table-wrap"><table><thead><tr><th>Name</th><th>Role</th><th>Username</th><th>Email</th></tr></thead><tbody>
  <?php foreach($last['accounts'] as $account):?>
    <tr><td><?=e($account['display_name'])?></td><td><?=e(ucfirst($account['role']))?></td><td><code><?=e($account['username'])?></code></td><td><?=e($account['email'])?></td></tr>
  <?php endforeach?>
  </tbody></table></div>
</section>
<?php endif?>
<?php endif?>

<section class="dev-stat-grid">
  <article class="app-card dev-stat"><span>System checks</span><strong><?=$passed?> / <?=count($diagnostics)?></strong><small>passing</small></article>
  <article class="app-card dev-stat"><span>Active batches</span><strong><?=$active?></strong><small>available for testing</small></article>
  <article class="app-card dev-stat"><span>Application version</span><strong>5.2.7</strong><small>Developer Center</small></article>
  <article class="app-card dev-stat"><span>PHP</span><strong><?=e(PHP_VERSION)?></strong><small><?=e(PHP_SAPI)?></small></article>
</section>

<div class="dev-layout">
<section class="app-card">
  <p class="eyebrow">TEST LAB</p><h2>Create a complete environment</h2>
  <p>This creates connected demo users, profiles, companies, brands, hierarchical universes, memberships, posts, chat, booths, teams, galleries, downloads, analytics, products, and orders.</p>
  <div class="dev-module-list">
    <span>Users & Profiles</span><span>Companies & Brands</span><span>Universes</span><span>Community</span><span>Booths</span><span>Marketplace</span>
  </div>
  <form method="post" onsubmit="return confirm('Create a new connected demo environment?');">
    <?=csrf_field()?><input type="hidden" name="action" value="generate_complete">
    <button class="button primary" type="submit">Create Complete Test Environment</button>
  </form>
  <p class="muted dev-note">Generated email addresses use <code>@example.test</code>. No real email is sent. The shared demo password is shown after generation.</p>
</section>

<section class="app-card">
  <p class="eyebrow">DIAGNOSTICS</p><h2>System health</h2>
  <div class="diagnostic-list">
  <?php foreach($diagnostics as $check):?>
    <div class="diagnostic-row <?=$check['ok']?'pass':'fail'?>"><span class="diagnostic-icon"><?=$check['ok']?'✓':'!'?></span><div><strong><?=e($check['name'])?></strong><small><?=e($check['detail'])?></small></div><span class="diagnostic-status"><?=$check['ok']?'PASS':'MISSING'?></span></div>
  <?php endforeach?>
  </div>
</section>
</div>

<section class="app-card">
  <div class="section-heading-row"><div><p class="eyebrow">BATCHES & CLEANUP</p><h2>Generated test environments</h2></div>
  <?php if($active):?><form method="post" onsubmit="return confirm('Remove every tracked demo batch? Real records are not included.');"><?=csrf_field()?><input type="hidden" name="action" value="cleanup_all"><button class="button danger" type="submit">Clean Up All Demo Data</button></form><?php endif?></div>
  <?php if(!$batches):?><div class="empty-state"><h3>No test batches yet</h3><p>Create a complete environment above to begin testing.</p></div><?php else:?>
  <div class="table-wrap"><table><thead><tr><th>Batch</th><th>Scenario</th><th>Records</th><th>Status</th><th>Created</th><th>Action</th></tr></thead><tbody>
  <?php foreach($batches as $batch):$summary=json_decode((string)($batch['summary_json']??''),true)?:[];?>
  <tr><td><strong><?=e($batch['label'])?></strong><br><span class="muted"><?=e($batch['batch_key'])?></span><?php if($summary):?><br><small><?=e(implode(' · ',array_map(fn($k,$v)=>ucfirst((string)$k).': '.$v,array_keys($summary),$summary)))?></small><?php endif?></td><td><?=e(ucfirst($batch['scenario']))?></td><td><?=number_format((int)$batch['record_count'])?></td><td><span class="badge dev-status-<?=e($batch['status'])?>"><?=e(ucfirst($batch['status']))?></span></td><td><?=e(date('M j, Y g:i A',strtotime($batch['created_at'])))?><br><small>by <?=e($batch['creator_name'])?></small></td><td><?php if($batch['status']!=='cleaned'):?>
  <div class="dev-action-stack">
    <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="reset_demo_passwords"><input type="hidden" name="batch_id" value="<?=(int)$batch['id']?>"><button class="button primary small" type="submit">Reset Demo Passwords</button></form>
    <form method="post" onsubmit="return confirm('Delete only the records tracked in this demo batch?');"><?=csrf_field()?><input type="hidden" name="action" value="cleanup_batch"><input type="hidden" name="batch_id" value="<?=(int)$batch['id']?>"><button class="button ghost small" type="submit">Clean Up</button></form>
  </div><?php else:?><span class="muted">Removed <?=e($batch['cleaned_at']?date('M j',strtotime($batch['cleaned_at'])):'')?></span><?php endif?></td></tr>
  <?php endforeach?></tbody></table></div>
  <?php endif?>
</section>

<section class="app-card dev-testing-guide"><p class="eyebrow">TESTING GUIDE</p><h2>Recommended end-to-end test</h2><ol><li>Create a complete test environment.</li><li>Open <strong>Booths</strong> and enter Geek Nation Collectibles.</li><li>Add a product to the cart and complete demo checkout.</li><li>Return as the administrator and open <strong>My Booths → Manage Booth</strong>.</li><li>Verify the new order, inventory, team, gallery, downloads, settings, and analytics.</li><li>Return here and clean up the batch when testing is complete.</li></ol></section>
<?php app_footer();
