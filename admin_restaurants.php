<?php
session_start();
require_once "database.php";
require_once "auth.php";
requireRole('Admin');

$message = "";
$error = "";
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = ($_POST['status'] ?? 'Open') === 'Closed' ? 'Closed' : 'Open';

        if ($name === '') {
            $error = "Restaurant name is required.";
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE restaurants SET name=?, description=?, address=?, phone=?, status=? WHERE id=?");
                $stmt->execute([$name, $description, $address, $phone, $status, $id]);
                $message = "Restaurant updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO restaurants(name,description,address,phone,status) VALUES(?,?,?,?,?)");
                $stmt->execute([$name, $description, $address, $phone, $status]);
                $message = "Restaurant added successfully.";
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $check = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id=?");
        $check->execute([$id]);

        if ((int)$check->fetchColumn() > 0) {
            $error = "This restaurant cannot be deleted because it has existing orders. Set it to Closed instead.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id=?");
                $stmt->execute([$id]);
                $message = "Restaurant deleted successfully.";
            } catch (PDOException $e) {
                $error = "Restaurant could not be deleted. Remove dependent records first.";
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id=?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}

$restaurants = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM food_items f WHERE f.restaurant_id=r.id) food_count FROM restaurants r ORDER BY r.id DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Restaurants | FoodGo</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include __DIR__ . "/admin/admin_header.php"; ?>
<main class="section"><div class="container">
<div class="page-heading"><div><p class="muted">Admin</p><h1>Manage Restaurants</h1></div><a class="btn" href="admin_dashboard.php">← Dashboard</a></div>
<?php if($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<div class="admin-grid">
<section class="panel"><h2><?= $edit ? 'Edit Restaurant' : 'Add Restaurant' ?></h2>
<form method="post">
<?= csrfField() ?>
<input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<label>Restaurant name<input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
<label>Description<textarea name="description"><?= e($edit['description'] ?? '') ?></textarea></label>
<label>Address<input name="address" value="<?= e($edit['address'] ?? '') ?>"></label>
<label>Phone<input name="phone" value="<?= e($edit['phone'] ?? '') ?>"></label>
<label>Status<select name="status"><option <?=($edit['status']??'Open')==='Open'?'selected':''?>>Open</option><option <?=($edit['status']??'')==='Closed'?'selected':''?>>Closed</option></select></label>
<div class="form-actions"><button class="btn"><?= $edit ? 'Save Changes' : 'Add Restaurant' ?></button><?php if($edit): ?><a class="btn secondary" href="admin_restaurants.php">Cancel</a><?php endif; ?></div>
</form></section>
<section class="panel"><h2>Restaurant List</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>Restaurant</th><th>Status</th><th>Food</th><th>Actions</th></tr></thead><tbody>
<?php foreach($restaurants as $r): ?><tr><td><strong><?= e($r['name']) ?></strong><br><small><?= e($r['address']) ?></small></td><td><span class="badge <?=$r['status']==='Closed'?'closed-badge':''?>"><?= e($r['status']) ?></span></td><td><?= (int)$r['food_count'] ?></td><td class="actions"><a href="?edit=<?=$r['id']?>">Edit</a><form method="post" class="inline-form" data-confirm="Delete this restaurant?"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="link danger">Delete</button></form></td></tr><?php endforeach; ?>
<?php if(!$restaurants): ?><tr><td colspan="4" class="empty-cell">No restaurants.</td></tr><?php endif; ?>
</tbody></table></div></section>
</div></div></main>
<script src="js/script.js"></script>
</body>
</html>
