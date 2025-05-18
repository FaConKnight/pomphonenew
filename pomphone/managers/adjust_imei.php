<?php
// /cooladmin/manager/adjust_imei.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

$page_title = "ปรับสถานะมือถือ (IMEI)";
$success = null;
$error = null;

// ตรวจสอบสิทธิ์ผู้ใช้งาน
$employee_id = $_SESSION['employee_id'] ?? 0;
$rank = $_SESSION['employee_rank'] ?? 0;
if ($rank < 88) {
    die("⛔ Access Denied");
}

// ดึงรายการมือถือ: admin เห็นทั้งหมด, manager เห็นเฉพาะ in_stock
if ($rank === 99) {
    $stmt = $pdo->query("SELECT pi.id, pi.imei1, p.name AS product_name, pi.status
                          FROM products_items pi
                          LEFT JOIN products p ON pi.product_id = p.id
                          ORDER BY pi.created_at DESC");
} else {
    $stmt = $pdo->query("SELECT pi.id, pi.imei1, p.name AS product_name, pi.status
                          FROM products_items pi
                          LEFT JOIN products p ON pi.product_id = p.id
                          WHERE pi.status = 'in_stock'
                          ORDER BY pi.created_at DESC");
}
$phones = $stmt->fetchAll();

$reasons = [
    'ขายแล้ว' => 'sold',
    'ส่งศูนย์' => 'sent_service',
    'เครื่องหาย' => 'lost',
    'เสีย / ชำรุด' => 'damaged',
    'อื่น ๆ' => 'other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'] ?? 0;
    $new_status = $_POST['new_status'] ?? '';
    $remark = $_POST['remark'] ?? '';
    $password_input = $_POST['confirm_password'] ?? '';

    try {
        // ตรวจสอบรหัสผ่านอีกครั้ง
        $stmt = $pdo->prepare("SELECT em_password FROM employee_account WHERE em_id = ? LIMIT 1");
        $stmt->execute([$employee_id]);
        $stored_hash = $stmt->fetchColumn();

        if (!$stored_hash || !password_verify($password_input, $stored_hash)) {
            throw new Exception("รหัสผ่านไม่ถูกต้อง ไม่สามารถเปลี่ยนสถานะได้");
        }

        // ตรวจสอบสถานะเดิม
        $stmt = $pdo->prepare("SELECT status FROM products_items WHERE id = ?");
        $stmt->execute([$item_id]);
        $current_status = $stmt->fetchColumn();

        if (!$current_status) {
            throw new Exception("ไม่พบ IMEI ที่เลือก");
        }

        if ($rank < 99 && $current_status !== 'in_stock') {
            throw new Exception("เฉพาะ Admin เท่านั้นที่สามารถเปลี่ยนสถานะ IMEI ที่ไม่ใช่ in_stock ได้");
        }

        // ดำเนินการเปลี่ยนสถานะ
        $pdo->beginTransaction();

        $update = $pdo->prepare("UPDATE products_items SET status = ?, updated_at = NOW() WHERE id = ?");
        $update->execute([$new_status, $item_id]);

        $log = $pdo->prepare("INSERT INTO stock_logs (product_item_id, action, quantity, employee_id, remark, created_at)
                              VALUES (?, 'status_change', 0, ?, ?, NOW())");
        $log->execute([$item_id, $employee_id, "จาก '$current_status' เปลี่ยนสถานะเป็น '$new_status': $remark"]);

        $pdo->commit();
        $success = "อัปเดตสถานะเรียบร้อยแล้ว";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "❌ " . $e->getMessage();
    }
}
?>

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>

<div class="page-container">
    <div class="main-content">
        <div class="section__content section__content--p30">
            <div class="container-fluid">
                <h3 class="mb-4">ปรับสถานะมือถือ (IMEI)</h3>

                <?php if ($success): ?>
                    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="form-horizontal" onsubmit="return confirm('ยืนยันการเปลี่ยนสถานะ?');">
                    <div class="form-group">
                        <label>เลือกมือถือ (IMEI)</label>
                        <select name="item_id" class="form-control" required>
                            <option value="">-- เลือกมือถือ --</option>
                            <?php foreach ($phones as $phone): ?>
                                <option value="<?= $phone['id'] ?>">
                                    <?= htmlspecialchars($phone['product_name']) ?> | IMEI: <?= $phone['imei1'] ?> (<?= $phone['status'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>สถานะใหม่</label>
                        <select name="new_status" class="form-control" required>
                            <option value="">-- เลือกสถานะ --</option>
                            <?php foreach ($reasons as $label => $value): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>หมายเหตุเพิ่มเติม</label>
                        <textarea name="remark" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label>ยืนยันรหัสผ่านของคุณ</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">อัปเดตสถานะ</button>
                </form>

            </div>
        </div>
    </div>
</div>

<?php include_once('../partials/footer.php'); ?>
