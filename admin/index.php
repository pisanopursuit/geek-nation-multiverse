<?php
require __DIR__.'/../includes/bootstrap.php';
require_admin();

function admin_count_status(string $table, string $status='pending'): int {
    try {$s=db()->prepare("SELECT COUNT(*) FROM {$table} WHERE status=?");$s->execute([$status]);return (int)$s->fetchColumn();}catch(Throwable $e){return 0;}
}
$types=[
 ['Booths','booths','booths.php','shop'],['Artists','artist_profiles','artists.php','star'],['Events','events','events.php','calendar'],
 ['Courses','academy_courses','academy.php','book'],['Collectors','collector_profiles','collectors.php','users'],['Collectibles','collector_items','approvals.php?type=collectible','spark'],
 ['Companies','companies','companies.php','users'],['Brands','brands','brands.php','star'],['Universes','universes','universes.php','spark']
];
$total=0;foreach($types as &$type){$type['count']=admin_count_status($type[1]);$total+=$type['count'];}unset($type);
app_header('Administration Center');
gnm_page_hero(['eyebrow'=>'ADMINISTRATION','title'=>'Administration Center','description'=>'Review and approve every public-facing submission across Geek Nation Multiverse.','actions'=>[['label'=>'Open Approval Queue','href'=>base_url('admin/approvals.php'),'variant'=>'primary']]]);
?>
<section class="gnm-section"><div class="gnm-stat-grid"><?php gnm_stat_card('Total Pending',$total,'Across all content types','spark');gnm_stat_card('Content Types',count($types),'One unified review system','book');gnm_stat_card('Your Role','Administrator','Full moderation access','users');?></div></section>
<section class="gnm-section"><?php gnm_section_header('Pending Approvals','Review everything','approvals.php','Select a content type or open the unified queue.');?><div class="gnm-grid gnm-grid--3"><?php foreach($types as $type):?>
<a class="gnm-card gnm-admin-type-card" href="<?=e($type[2])?>"><div class="gnm-card__icon"><?=gnm_icon($type[3])?></div><div class="gnm-card__body"><p class="gnm-card__eyebrow">PENDING</p><h3><?=e($type[0])?></h3><p class="gnm-admin-count"><?=(int)$type['count']?></p><span class="gnm-card__arrow"><?=gnm_icon('arrow')?></span></div></a>
<?php endforeach?></div></section>
<section class="gnm-section"><?php gnm_section_header('Administration Tools');?><div class="gnm-grid gnm-grid--3"><?php gnm_action_card('Users','Manage accounts, permissions, and suspensions.','Manage Users','users.php','users');gnm_action_card('Import Center','Bring platform data into Geek Nation Multiverse.','Open Imports','imports.php','book');gnm_action_card('Developer Center','Generate and inspect test content.','Open Developer Center','developer-center.php','spark');gnm_action_card('Platform Settings','Manage branding, features, SEO, social links, and maintenance mode.','Open Settings','settings.php','spark');?></div></section>
<?php app_footer();
