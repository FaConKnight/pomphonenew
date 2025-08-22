<?php
// /cooladmin/manager/adjust_stock.php

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

$page_title = "ปรับสต๊อกสินค้า";
$success = null;
$error = null;

// ดึงรายการสินค้าที่ไม่มี IMEI เท่านั้น
$stmt = $pdo->query("SELECT p.id, p.name, c.name AS category_name, p.sku, p.stock_quantity, p.is_trackable
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE p.is_trackable = 0 AND p.is_active = 1
                      ORDER BY p.name ASC");
$products = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? 0;
    $new_quantity = $_POST['new_quantity'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    $employee_id = $_SESSION['employee_id'] ?? 0;

    try {
        $pdo->beginTransaction();

        // ตรวจสอบว่ามี IMEI หรือไม่
        $track_stmt = $pdo->prepare("SELECT is_trackable FROM products WHERE id = ?");
        $track_stmt->execute([$product_id]);
        $is_trackable = $track_stmt->fetchColumn();

        if ($is_trackable) {
            throw new Exception("ไม่สามารถปรับจำนวนมือถือได้จากหน้านี้ กรุณาใช้งานหน้าจัดการ IMEI");
        }

        $current = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? LIMIT 1");
        $current->execute([$product_id]);
        $current_stock = $current->fetchColumn();

        $update = $pdo->prepare("UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?");
        $update->execute([$new_quantity, $product_id]);

        $log = $pdo->prepare("INSERT INTO stock_logs (product_item_id,  product_id, action, quantity, employee_id, remark, created_at)
                              VALUES (?, 'adjust', ?, ?, ?, NOW())");
        $log->execute([0, $product_id, $new_quantity - $current_stock, $employee_id, "ปรับจาก $current_stock → $new_quantity: $reason"]);

        $pdo->commit();
        $success = "ปรับปรุงจำนวนสำเร็จแล้ว";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>


<main>
<div class="page-container">
    <div class="main-content">
        <div class="section__content section__content--p30">
            <div class="container-fluid">
                <h3 class="mb-4">ปรับสต๊อกสินค้า</h3>

                <?php if ($success): ?>
                  <div class="alert alert-success">✅ <?= safe_text($success) ?></div>
                <?php elseif ($error): ?>
                  <div class="alert alert-danger">❌ <?= safe_text($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="form-horizontal" onsubmit="return confirm('ยืนยันการปรับจำนวนสินค้า?');">
                    <div class="form-group">
                        <label>เลือกสินค้า</label>
                        <select name="product_id" class="form-control" required>
                            <option value="">-- เลือกสินค้า --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= safe_text($p['name']) ?> (หมวด: <?= $p['category_name'] ?> | คงเหลือ: <?= $p['stock_quantity'] ?> <?= $p['is_trackable'] ? ' - มี IMEI' : '' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>จำนวนใหม่</label>
                        <input type="number" name="new_quantity" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>หมายเหตุ (เช่น: นับผิด, สินค้าชำรุด)</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">บันทึกการปรับสต๊อก</button>
                </form>

                <hr>
                <!--h5 class="mt-4">🕓 ประวัติการปรับสต๊อกล่าสุด</h5>
                < table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>วันเวลา</th>
                            <th>พนักงาน</th>
                            <th>การเปลี่ยนแปลง</th>
                            <th>เหตุผล</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php /*
                        $log_stmt = $pdo->query("SELECT sl.*, e.em_username
                            FROM stock_logs sl
                            LEFT JOIN employee_account e ON sl.employee_id = e.em_id
                            WHERE sl.action = 'adjust'
                            ORDER BY sl.created_at DESC
                            LIMIT 20");
                        foreach ($log_stmt->fetchAll() as $log): */?>
                            <tr>
                                <td><?//= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                <td><?//= htmlspecialchars($log['em_username']) ?></td>
                                <td><?//= ($log['quantity'] > 0 ? '+' : '') . $log['quantity'] ?></td>
                                <td><?//= htmlspecialchars($log['remark']) ?></td>
                            </tr>
                        <?php// endforeach; ?>
                    </tbody -->
                </table>
            </div>
        </div>
    </div>
</div>

</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>