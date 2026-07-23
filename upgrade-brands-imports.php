<?php
require __DIR__.'/includes/bootstrap.php';
require_admin();
$message='';$error='';

function column_exists(string $table,string $column): bool {
    $s=db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $s->execute([$table,$column]);
    return (bool)$s->fetchColumn();
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        run_sql_file(__DIR__.'/database/brands-imports.sql');

        if(!column_exists('companies','import_batch_id')){
            db()->exec('ALTER TABLE companies ADD import_batch_id BIGINT UNSIGNED NULL, ADD INDEX idx_company_batch(import_batch_id)');
        }

        // Repair an early v2 installation if the import_items table was manually
        // created with the MySQL 8 reserved name `row_number`.
        if(column_exists('import_items','row_number') && !column_exists('import_items','import_row')){
            db()->exec('ALTER TABLE import_items CHANGE `row_number` import_row INT UNSIGNED NOT NULL');
        }

        $message='Brands and the Admin Import Center are installed and MySQL 8 compatible.';
    }catch(Throwable $e){$error=$e->getMessage();}
}
app_header('Install Brands and Imports');
?>
<section class="dashboard-hero"><p class="eyebrow">DATABASE UPGRADE</p><h1>Brands + Import Center v3</h1><p>Install brand profiles, approvals, CSV importing, validation history, draft imports, and safe import rollback.</p></section>
<?php if($message):?><div class="alert success"><?=e($message)?></div><p><a class="button primary" href="admin/imports.php">Open Import Center</a> <a class="button ghost" href="brand/index.php">Browse Brands</a></p><?php elseif($error):?><div class="alert error"><?=e($error)?></div><?php endif?>
<form method="post" class="app-card"><?=csrf_field()?><p>This upgrade is safe to run more than once. It also repairs incomplete Version 2 installations.</p><button class="button primary">Install Upgrade</button></form>
<?php app_footer();
