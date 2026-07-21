<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$currentUser = user();
$allowedRoles = ['fan', 'creator', 'vendor', 'admin'];
$allowedStatuses = ['pending_email', 'active', 'suspended'];
$allowedAccess = ['none', 'pending', 'approved', 'rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $targetStmt = db()->prepare('SELECT id, username, display_name, role, status, company_brand_access FROM users WHERE id = ? LIMIT 1');
    $targetStmt->execute([$id]);
    $target = $targetStmt->fetch();

    if (!$target) {
        flash('error', 'User account not found.');
        redirect('admin/users.php');
    }

    if ($action === 'update_role') {
        $newRole = $_POST['role'] ?? '';
        if (!in_array($newRole, $allowedRoles, true)) {
            flash('error', 'Invalid role selected.');
            redirect('admin/users.php');
        }

        if ($target['role'] === 'admin' && $newRole !== 'admin') {
            $adminCount = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status <> 'suspended'")->fetchColumn();
            if ($adminCount <= 1) {
                flash('error', 'You cannot remove the final active administrator.');
                redirect('admin/users.php');
            }
        }

        db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $id]);
        flash('success', $newRole === 'admin'
            ? e($target['display_name']) . ' is now an administrator.'
            : e($target['display_name']) . ' role updated to ' . ucfirst($newRole) . '.');
        redirect('admin/users.php');
    }

    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        if (!in_array($newStatus, $allowedStatuses, true)) {
            flash('error', 'Invalid account status selected.');
            redirect('admin/users.php');
        }

        if ((int)$target['id'] === (int)$currentUser['id'] && $newStatus === 'suspended') {
            flash('error', 'You cannot suspend your own account.');
            redirect('admin/users.php');
        }

        if ($target['role'] === 'admin' && $newStatus === 'suspended') {
            $adminCount = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status <> 'suspended'")->fetchColumn();
            if ($adminCount <= 1) {
                flash('error', 'You cannot suspend the final active administrator.');
                redirect('admin/users.php');
            }
        }

        db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
        flash('success', 'Account status updated for ' . e($target['display_name']) . '.');
        redirect('admin/users.php');
    }

    if ($action === 'update_access') {
        $newAccess = $_POST['company_brand_access'] ?? '';
        if (!in_array($newAccess, $allowedAccess, true)) {
            flash('error', 'Invalid company or brand access status.');
            redirect('admin/users.php');
        }

        db()->prepare('UPDATE users SET company_brand_access = ? WHERE id = ?')->execute([$newAccess, $id]);
        flash('success', 'Company and brand access updated for ' . e($target['display_name']) . '.');
        redirect('admin/users.php');
    }
}

$users = db()->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
app_header('User Administration');
?>
<section class="dashboard-hero">
    <p class="eyebrow">ADMINISTRATION</p>
    <h1>Users & Permissions</h1>
    <p class="lede">Manage account roles, access, and status. Administrator access grants control of users, invitations, approvals, and identity options.</p>
</section>

<p class="admin-actions">
    <a class="button primary" href="invitations.php">Invite Users & Admins</a>
    <a class="button ghost" href="companies.php">Manage Companies</a>
    <a class="button ghost" href="identity.php">Manage User Identity Options</a>
</p>

<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>User</th>
            <th>Role</th>
            <th>Account</th>
            <th>Company/Brand Access</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $x): ?>
        <tr>
            <td>
                <strong><?= e($x['display_name']) ?></strong>
                <?php if ((int)$x['id'] === (int)$currentUser['id']): ?><span class="badge">You</span><?php endif; ?>
                <br>
                <span class="muted"><?= e($x['username']) ?> · <?= e($x['email']) ?></span>
            </td>
            <td>
                <form method="post" class="inline-form admin-user-control">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>">
                    <input type="hidden" name="action" value="update_role">
                    <select name="role" aria-label="Role for <?= e($x['display_name']) ?>">
                        <?php foreach ($allowedRoles as $role): ?>
                            <option value="<?= e($role) ?>" <?= $x['role'] === $role ? 'selected' : '' ?>><?= e($role === 'admin' ? 'Administrator' : ucfirst($role)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="button primary small" type="submit">Save Role</button>
                </form>
            </td>
            <td>
                <form method="post" class="inline-form admin-user-control">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>">
                    <input type="hidden" name="action" value="update_status">
                    <select name="status" aria-label="Account status for <?= e($x['display_name']) ?>">
                        <option value="pending_email" <?= $x['status'] === 'pending_email' ? 'selected' : '' ?>>Pending Email</option>
                        <option value="active" <?= $x['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $x['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                    <button class="button ghost small" type="submit">Save Status</button>
                </form>
            </td>
            <td>
                <form method="post" class="inline-form admin-user-control">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>">
                    <input type="hidden" name="action" value="update_access">
                    <select name="company_brand_access" aria-label="Company and brand access for <?= e($x['display_name']) ?>">
                        <option value="none" <?= $x['company_brand_access'] === 'none' ? 'selected' : '' ?>>None</option>
                        <option value="pending" <?= $x['company_brand_access'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $x['company_brand_access'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $x['company_brand_access'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                    <button class="button ghost small" type="submit">Save Access</button>
                </form>
            </td>
            <td>
                <div class="admin-row-actions">
                    <a class="button ghost small" href="<?= e(base_url('profile.php?u=' . urlencode($x['username']))) ?>">View Profile</a>
                    <?php if ($x['role'] !== 'admin'): ?>
                        <form method="post" class="inline-form" onsubmit="return confirm('Make <?= e(addslashes($x['display_name'])) ?> an administrator? This grants full administrative access.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="role" value="admin">
                            <button class="button primary small" type="submit">Make Admin</button>
                        </form>
                    <?php else: ?>
                        <span class="badge">Administrator</span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<p class="muted admin-note"><strong>Security:</strong> The final active administrator cannot be demoted or suspended, and you cannot suspend your own account.</p>
<?php app_footer(); ?>
