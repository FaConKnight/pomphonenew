<?php
// /cooladmin/manager/add_category.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat_name = trim($_POST['name'] ?? '');

    if ($cat_name === '') {
        $error = "\u274c กรุณากรอกชื่อหมวดหมู่";
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        if ($stmt->execute([$cat_name])) {
            $success = "\u2705 เพิ่มหมวดหมู่เรียบร้อยแล้ว";
        } else {
            $error = "\u274c ไม่สามารถเพิ่มหมวดหมู่ได้";
        }
    }
}
?>

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>

<div class="page-container">
    <div class="main-content">
        <div class="section__content section__content--p30">
            <div class="container-fluid">
                <h3 class="mb-4">เพิ่มหมวดหมู่สินค้า</h3>

                <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <form method="POST" class="form-horizontal">
                    <div class="form-group">
                        <label>ชื่อหมวดหมู่</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once('../partials/footer.php'); ?>
