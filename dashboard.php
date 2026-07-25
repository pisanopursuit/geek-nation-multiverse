<?php
require __DIR__.'/includes/bootstrap.php';
require_auth();
$u=user();
$uid=(int)$u['id'];

function dash_one(string $sql,array $params=[],$default=0){try{$s=db()->prepare($sql);$s->execute($params);$v=$s->fetchColumn();return $v===false?$default:$v;}catch(Throwable $e){return $default;}}
function dash_rows(string $sql,array $params=[]): array {try{$s=db()->prepare($sql);$s->execute($params);return $s->fetchAll()?:[];}catch(Throwable $e){return [];}}
function dash_status_class(string $status): string {return in_array($status,['approved','active','completed','checked_in'],true)?'good':(in_array($status,['pending','waitlisted','draft','submitted','reviewing'],true)?'warn':'accent');}

if(identity_schema_ready()){
    ensure_profile($uid);$profile=profile_for_user($uid);$completion=profile_completion($profile);
}else{$profile=null;$completion=['percent'=>0];}

$counts=['universes'=>0,'events'=>0,'courses'=>0,'favorites'=>0];
if(universe_engine_ready())$counts['universes']=(int)dash_one('SELECT COUNT(*) FROM user_universes WHERE user_id=?',[$uid]);
if(events_schema_ready())$counts['events']=(int)dash_one("SELECT COUNT(*) FROM event_attendees ea JOIN events e ON e.id=ea.event_id WHERE ea.user_id=? AND ea.attendee_status<>'cancelled' AND e.starts_at>=NOW()",[$uid]);
if(academy_ready())$counts['courses']=(int)dash_one("SELECT COUNT(*) FROM academy_enrollments WHERE user_id=? AND status IN ('enrolled','in_progress','waitlisted')",[$uid]);
if(collector_marketplace_ready())$counts['favorites']=(int)dash_one('SELECT COUNT(*) FROM collector_favorites WHERE user_id=?',[$uid]);

$ownedBooths=booths_schema_ready()?dash_rows("SELECT id,name,slug,status,(SELECT COUNT(*) FROM booth_products p WHERE p.booth_id=booths.id) products,(SELECT COUNT(*) FROM booth_orders o WHERE o.booth_id=booths.id AND o.order_status IN ('pending','confirmed','processing')) open_orders FROM booths WHERE owner_user_id=? ORDER BY updated_at DESC LIMIT 3",[$uid]):[];
$ownedEvents=events_schema_ready()?dash_rows("SELECT id,title,slug,status,starts_at FROM events WHERE owner_user_id=? ORDER BY starts_at DESC LIMIT 3",[$uid]):[];
$upcomingEvents=events_schema_ready()?dash_rows("SELECT e.id,e.title,e.slug,e.starts_at,e.format,ea.attendee_status FROM event_attendees ea JOIN events e ON e.id=ea.event_id WHERE ea.user_id=? AND ea.attendee_status<>'cancelled' AND e.starts_at>=NOW() ORDER BY e.starts_at LIMIT 4",[$uid]):[];
$artist=artist_alley_ready()?(dash_rows("SELECT ap.*,(SELECT COUNT(*) FROM artist_portfolio_items pi WHERE pi.artist_id=ap.id) portfolio_count,(SELECT COUNT(*) FROM artist_commission_requests cr WHERE cr.artist_id=ap.id AND cr.status IN ('submitted','reviewing','accepted','in_progress','proof')) open_requests,(SELECT COUNT(*) FROM artist_follows af WHERE af.artist_id=ap.id) followers FROM artist_profiles ap WHERE ap.user_id=? LIMIT 1",[$uid])[0]??null):null;
$ownedCourses=academy_ready()?dash_rows("SELECT c.id,c.title,c.slug,c.status,(SELECT COUNT(*) FROM academy_enrollments ae WHERE ae.course_id=c.id) students FROM academy_courses c WHERE c.owner_user_id=? ORDER BY c.updated_at DESC LIMIT 3",[$uid]):[];
$enrollments=academy_ready()?dash_rows("SELECT c.title,c.slug,ae.status,ae.progress_percent FROM academy_enrollments ae JOIN academy_courses c ON c.id=ae.course_id WHERE ae.user_id=? AND ae.status<>'cancelled' ORDER BY ae.enrolled_at DESC LIMIT 4",[$uid]):[];
$collector=collector_marketplace_ready()?(dash_rows("SELECT cp.*,(SELECT COUNT(*) FROM collector_items ci WHERE ci.collector_id=cp.id) item_count,(SELECT COUNT(*) FROM collector_offers co JOIN collector_items ci2 ON ci2.id=co.item_id WHERE ci2.collector_id=cp.id AND co.status='pending') offers FROM collector_profiles cp WHERE cp.user_id=? LIMIT 1",[$uid])[0]??null):null;
$companies=companies_schema_ready()?dash_rows("SELECT c.id,c.name,c.slug,c.status,cm.company_role FROM company_members cm JOIN companies c ON c.id=cm.company_id WHERE cm.user_id=? AND cm.status='active' ORDER BY c.name LIMIT 4",[$uid]):[];
$brands=brands_schema_ready()?dash_rows("SELECT b.id,b.name,b.slug,b.status,bm.brand_role FROM brand_members bm JOIN brands b ON b.id=bm.brand_id WHERE bm.user_id=? AND bm.status='active' ORDER BY b.name LIMIT 4",[$uid]):[];

$hasCreatorRole=(bool)($ownedBooths||$ownedEvents||$artist||$ownedCourses||$collector||$companies||$brands);

app_header('Dashboard');
echo '<link rel="stylesheet" href="'.e(base_url('assets/dashboard-v10.6.css?v=10.6')).'">';
?>
<main class="gn-dashboard">
<section class="gn-dashboard-hero">
  <div class="gn-dashboard-hero__grid">
    <div><p class="gn-dashboard-eyebrow">MY MULTIVERSE</p><h1>Welcome back, <?=e($u['display_name'])?>.</h1><p>Your personal convention headquarters brings your communities, events, creative work, storefronts, courses, and collection together in one place.</p>
      <div class="gn-dashboard-toolbar">
        <a class="gn-dash-btn gn-dash-btn--primary" href="<?=e(base_url('explore.php'))?>">Explore the Multiverse</a>
        <a class="gn-dash-btn" href="<?=e(base_url('profile.php?u='.urlencode($u['username'])))?>">View My Profile</a>
        <a class="gn-dash-btn" href="<?=e(base_url('edit-profile.php'))?>">Edit Profile</a>
      </div>
    </div>
    <?php if($profile):?><div class="gn-profile-meter"><div class="gn-profile-meter__top"><span>Profile completion</span><strong><?=$completion['percent']?>%</strong></div><div class="gn-profile-meter__track"><span style="width:<?=$completion['percent']?>%"></span></div><?php if($completion['percent']<100):?><a class="gn-dash-btn" style="margin-top:14px" href="<?=e(base_url(empty($profile['onboarding_completed_at'])?'onboarding.php':'edit-profile.php'))?>">Complete profile</a><?php endif?></div><?php endif?>
  </div>
</section>

<div class="gn-dashboard-layout">
<div class="gn-dashboard-main">
<section class="gn-dash-panel"><div class="gn-dash-panel__head"><h2>Your Multiverse at a glance</h2></div><div class="gn-stat-grid">
  <div class="gn-stat"><span>Joined universes</span><strong><?=$counts['universes']?></strong></div>
  <div class="gn-stat"><span>Upcoming events</span><strong><?=$counts['events']?></strong></div>
  <div class="gn-stat"><span>Active courses</span><strong><?=$counts['courses']?></strong></div>
  <div class="gn-stat"><span>Saved collectibles</span><strong><?=$counts['favorites']?></strong></div>
</div></section>

<section class="gn-dash-panel"><div class="gn-dash-panel__head"><h2>Quick actions</h2></div><div class="gn-action-grid">
<a class="gn-action" href="<?=e(base_url('universe/index.php'))?>"><span class="gn-action__icon">🌌</span><strong>Find a universe</strong><small>Discover a fandom community and join the conversation.</small></a>
<?php if(artist_alley_ready()):?><a class="gn-action" href="<?=e(base_url($artist?'artist-alley/dashboard.php':'artist-alley/create.php'))?>"><span class="gn-action__icon">🎨</span><strong><?=$artist?'Manage artist page':'Create artist page'?></strong><small>Show your work, commissions, and creative identity.</small></a><?php endif?>
<?php if(booths_schema_ready()):?><a class="gn-action" href="<?=e(base_url($ownedBooths?'booth/dashboard.php':'booth/create.php'))?>"><span class="gn-action__icon">🛍️</span><strong><?=$ownedBooths?'Manage booths':'Open a booth'?></strong><small>Build a storefront and share products with fans.</small></a><?php endif?>
<?php if(events_schema_ready()):?><a class="gn-action" href="<?=e(base_url('events/create.php'))?>"><span class="gn-action__icon">🎤</span><strong>Create an event</strong><small>Publish a panel, livestream, signing, or meetup.</small></a><?php endif?>
<?php if(academy_ready()):?><a class="gn-action" href="<?=e(base_url('academy/create.php'))?>"><span class="gn-action__icon">🎓</span><strong>Create a course</strong><small>Teach a skill through the Multiverse Academy.</small></a><?php endif?>
<?php if(collector_marketplace_ready()):?><a class="gn-action" href="<?=e(base_url($collector?'collectors/dashboard.php':'collectors/create.php'))?>"><span class="gn-action__icon">🧸</span><strong><?=$collector?'Manage collection':'Start a collection'?></strong><small>Showcase, sell, trade, and track collectibles.</small></a><?php endif?>
</div></section>

<section class="gn-dash-panel"><div class="gn-dash-panel__head"><h2>Your creator and organization tools</h2><span class="gn-status gn-status--accent"><?=$hasCreatorRole?'Active tools':'Ready when you are'?></span></div><div class="gn-role-widgets">
<?php if($artist):?><article class="gn-role-card"><div class="gn-role-card__top"><div><h3>Artist Alley</h3><p><?=e($artist['artist_name'])?></p></div><span class="gn-status gn-status--<?=dash_status_class($artist['status'])?>"><?=e($artist['status'])?></span></div><div class="gn-role-card__metrics"><div><strong><?=(int)$artist['portfolio_count']?></strong><span>Portfolio</span></div><div><strong><?=(int)$artist['followers']?></strong><span>Followers</span></div><div><strong><?=(int)$artist['open_requests']?></strong><span>Requests</span></div></div><div class="gn-role-card__links"><a href="<?=e(base_url('artist-alley/dashboard.php'))?>">Manage artist page →</a><a href="<?=e(base_url('artist-alley/view.php?slug='.urlencode($artist['slug'])))?>">View public page →</a></div></article><?php endif?>
<?php foreach($ownedBooths as $b):?><article class="gn-role-card"><div class="gn-role-card__top"><div><h3><?=e($b['name'])?></h3><p>Your convention booth</p></div><span class="gn-status gn-status--<?=dash_status_class($b['status'])?>"><?=e($b['status'])?></span></div><div class="gn-role-card__metrics"><div><strong><?=(int)$b['products']?></strong><span>Products</span></div><div><strong><?=(int)$b['open_orders']?></strong><span>Open orders</span></div></div><div class="gn-role-card__links"><a href="<?=e(base_url('booth/manage.php?id='.(int)$b['id']))?>">Manage booth →</a><a href="<?=e(base_url('booth/view.php?slug='.urlencode($b['slug'])))?>">View booth →</a></div></article><?php endforeach?>
<?php foreach($ownedCourses as $c):?><article class="gn-role-card"><div class="gn-role-card__top"><div><h3><?=e($c['title'])?></h3><p>Academy course</p></div><span class="gn-status gn-status--<?=dash_status_class($c['status'])?>"><?=e($c['status'])?></span></div><div class="gn-role-card__metrics"><div><strong><?=(int)$c['students']?></strong><span>Students</span></div></div><div class="gn-role-card__links"><a href="<?=e(base_url('academy/manage.php?id='.(int)$c['id']))?>">Manage course →</a><a href="<?=e(base_url('academy/view.php?slug='.urlencode($c['slug'])))?>">View course →</a></div></article><?php endforeach?>
<?php if($collector):?><article class="gn-role-card"><div class="gn-role-card__top"><div><h3><?=e($collector['shop_name'])?></h3><p>Collector profile</p></div><span class="gn-status gn-status--<?=dash_status_class($collector['status'])?>"><?=e($collector['status'])?></span></div><div class="gn-role-card__metrics"><div><strong><?=(int)$collector['item_count']?></strong><span>Items</span></div><div><strong><?=(int)$collector['offers']?></strong><span>Offers</span></div></div><div class="gn-role-card__links"><a href="<?=e(base_url('collectors/dashboard.php'))?>">Manage collection →</a><a href="<?=e(base_url('collectors/profile.php?slug='.urlencode($collector['slug'])))?>">View collection →</a></div></article><?php endif?>
<?php foreach($companies as $c):?><article class="gn-role-card"><div class="gn-role-card__top"><div><h3><?=e($c['name'])?></h3><p><?=e(str_replace('_',' ',$c['company_role']))?></p></div><span class="gn-status gn-status--<?=dash_status_class($c['status'])?>"><?=e($c['status'])?></span></div><div class="gn-role-card__links"><a href="<?=e(base_url('company/dashboard.php?id='.(int)$c['id']))?>">Manage company →</a><a href="<?=e(base_url('company/view.php?slug='.urlencode($c['slug'])))?>">View company →</a></div></article><?php endforeach?>
<?php foreach($brands as $b):?><article class="gn-role-card"><div class="gn-role-card__top"><div><h3><?=e($b['name'])?></h3><p><?=e(str_replace('_',' ',$b['brand_role']))?></p></div><span class="gn-status gn-status--<?=dash_status_class($b['status'])?>"><?=e($b['status'])?></span></div><div class="gn-role-card__links"><a href="<?=e(base_url('brand/dashboard.php?id='.(int)$b['id']))?>">Manage brand →</a><a href="<?=e(base_url('brand/view.php?slug='.urlencode($b['slug'])))?>">View brand →</a></div></article><?php endforeach?>
<?php if(!$hasCreatorRole):?><div class="gn-empty" style="grid-column:1/-1"><strong>Your dashboard grows with you.</strong>Create an artist page, booth, event, course, collection, company, or brand and its management widget will automatically appear here.</div><?php endif?>
</div></section>
</div>

<aside class="gn-dashboard-side">
<section class="gn-dash-panel"><div class="gn-dash-panel__head"><h2>Upcoming events</h2><a href="<?=e(base_url('events/index.php'))?>">Explore →</a></div><?php if($upcomingEvents):?><div class="gn-widget-list"><?php foreach($upcomingEvents as $ev):?><div class="gn-widget-row"><div class="gn-widget-row__icon">📅</div><div><strong><?=e($ev['title'])?></strong><small><?=e(date('M j, Y · g:i A',strtotime($ev['starts_at'])))?> · <?=e(ucfirst($ev['format']))?></small></div><a href="<?=e(base_url('events/view.php?slug='.urlencode($ev['slug'])))?>">View</a></div><?php endforeach?></div><?php else:?><div class="gn-empty"><strong>No upcoming events yet.</strong>Find your next panel, workshop, stream, or meetup.<br><a class="gn-dash-btn" href="<?=e(base_url('events/index.php'))?>">Explore events</a></div><?php endif?></section>

<section class="gn-dash-panel"><div class="gn-dash-panel__head"><h2>Learning progress</h2><a href="<?=e(base_url('academy/index.php'))?>">Academy →</a></div><?php if($enrollments):?><div class="gn-widget-list"><?php foreach($enrollments as $en):?><div class="gn-widget-row"><div class="gn-widget-row__icon">🎓</div><div><strong><?=e($en['title'])?></strong><small><?=e(ucfirst(str_replace('_',' ',$en['status'])))?> · <?=(int)$en['progress_percent']?>% complete</small></div><a href="<?=e(base_url('academy/view.php?slug='.urlencode($en['slug'])))?>">Open</a></div><?php endforeach?></div><?php else:?><div class="gn-empty"><strong>No active courses.</strong>Learn illustration, cosplay, storytelling, collecting, and more.<br><a class="gn-dash-btn" href="<?=e(base_url('academy/index.php'))?>">Browse courses</a></div><?php endif?></section>

<section class="gn-dash-panel"><div class="gn-dash-panel__head"><h2>Account</h2></div><div class="gn-widget-list"><div class="gn-widget-row"><div class="gn-widget-row__icon">👤</div><div><strong><?=e($u['username'])?></strong><small><?=e($u['email'])?></small></div><span class="gn-status gn-status--good"><?=e($u['role'])?></span></div></div></section>

<?php if(($u['role']??'')==='admin'):?><section class="gn-dash-panel gn-admin-panel"><div class="gn-dash-panel__head"><h2>Administration</h2><a href="<?=e(base_url('admin/users.php'))?>">Open →</a></div><div class="gn-action-grid" style="grid-template-columns:1fr 1fr"><a class="gn-action" href="<?=e(base_url('admin/users.php'))?>"><span class="gn-action__icon">🛡️</span><strong>Users</strong><small>Accounts and permissions</small></a><a class="gn-action" href="<?=e(base_url('admin/imports.php'))?>"><span class="gn-action__icon">📥</span><strong>Imports</strong><small>Platform data center</small></a></div></section><?php endif?>
</aside>
</div>
</main>
<?php app_footer();
