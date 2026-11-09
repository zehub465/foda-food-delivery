<?php
session_start();
require_once "database.php";
require_once "auth.php";
requireRole('Admin');

$message = "";
$error = "";
$validRoles = ['Customer','Rider','Admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $userId = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';

    if (!in_array($role, $validRoles, true)) {
        $error = "Invalid role.";
    } elseif ($userId === (int)currentUserId() && $role !== 'Admin') {
        $error = "You cannot remove your own Admin role here.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->execute([$role, $userId]);
        $message = "User role updated successfully.";
    }
}

$users = $pdo->query(
    "SELECT id,name,email,phone,address,role,created_at
     FROM users ORDER BY id DESC"
)->fetchAll();
?>
<!doctype html>
<html lang="en">

    
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Manage Users | FoodGo</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include __DIR__ . "/admin/admin_header.php"; ?>

<main class="section">
    <div class="container">
<div class="page-heading">
    <div>
        <p class="muted">Admin</p>
        <h1>Manage Users</h1>
    </div>
    <a class="btn" href="admin_dashboard.php">← Dashboard</a>
</div>

<?php if($message): ?>
    
    <div class="alert success">
        <?=htmlspecialchars($message)?>
    </div>
    
    <?php endif; ?>

<?php if($error): ?>
    
    <div class="alert error">
        
    <?=htmlspecialchars($error)?>
</div>

<?php endif; ?>

<div class="panel table-wrap">
<table class="data-table">
    <thead>
        <tr>
            <th>User</th>
            <th>Contact</th>
            <th>Role</th>
            <th>Created</th>
            <th>Update</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($users as $u): ?>
    <tr>
    <td>
        <strong><?=htmlspecialchars($u['name'])?></strong>
        <br>
        <small><?=htmlspecialchars($u['email'])?></small>
    </td>
    <td>
        <?=htmlspecialchars($u['phone']??'')?>
        <br>
        <small><?=htmlspecialchars($u['address']??'')?></small>
    </td>
    <td>
        <span class="badge">
            <?=htmlspecialchars($u['role'])?>
        </span>
    </td>
    <td><?=htmlspecialchars($u['created_at'])?></td>
    <td>
        <form method="post" class="inline">
            <input type="hidden" 
                    name="csrf_token" 
                    value="<?=htmlspecialchars(csrfToken())?>">
                    
                    <input type="hidden" 
                            name="user_id" 
                            value="<?=$u['id']?>">
                            
                            <select name="role">
                                <?php foreach($validRoles as $r): ?>
                                    
                                    <option <?=$u['role']===$r?'selected':''?>><?=$r?></option>
                                    
                                    <?php endforeach;?>
                                </select>
                                <button class="btn small">Save</button>
                            </form>
                        </td>
        </tr>
        
        <?php endforeach;?>

</tbody>
</table>
</div>
</div>
</main>
<script src="js/script.js">

</script>
</body>
</html>
