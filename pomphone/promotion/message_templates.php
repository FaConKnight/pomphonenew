<?php
// admin/message_templates.php - จัดการเทมเพลตข้อความ
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
// เพิ่มเทมเพลตใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_template'])) {
    $name = trim($_POST['name']);
    $type = $_POST['content_type'];
    $content = trim($_POST['content_json']);

    $stmt = $pdo->prepare("INSERT INTO message_templates (name, content_type, content_json, created_by) VALUES (?, ?, ?, 'admin')");
    $stmt->execute([$name, $type, $content]);
    header("Location: message_templates.php");
    exit;
}

// ลบเทมเพลต
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM message_templates WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: message_templates.php");
    exit;
}

// แก้ไขเทมเพลต
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_template'])) {
    $stmt = $pdo->prepare("UPDATE message_templates SET name = ?, content_type = ?, content_json = ? WHERE id = ?");
    $stmt->execute([
        trim($_POST['name']),
        $_POST['content_type'],
        trim($_POST['content_json']),
        $_POST['template_id']
    ]);
    header("Location: message_templates.php");
    exit;
}

// ดึงรายการเทมเพลต
$templates = $pdo->query("SELECT * FROM message_templates ORDER BY created_at DESC LIMIT 20")->fetchAll();
?>

<main class="main-content p-4">
    <h2>เพิ่มเทมเพลตข้อความ</h2>
    <form method="POST">
        <input type="hidden" name="add_template" value="1" >
        <p><label>ชื่อเทมเพลต: <input type="text" name="name" required></label></p>
        <p>
            <label>ประเภท:
                <select name="content_type">
                    <option value="text">ข้อความธรรมดา</option>
                    <option value="flex">Flex JSON</option>
                </select>
            </label>
        </p>
        <p>
            <label>เนื้อหา (ข้อความ/Flex JSON):<a href="https://developers.line.biz/flex-simulator/" target="_blank">Flex Simu</a><br>
                <textarea class="form-control" name="content_json" rows="6" cols="60" required></textarea>
            </label>
        </p>
        <button type="submit">เพิ่มเทมเพลต</button>
    </form>

    <h2>เทมเพลตทั้งหมด</h2>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ชื่อ</th>
                <th>ประเภท</th>
                <th>เนื้อหา</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($templates as $tpl): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="edit_template" value="1">
                        <input type="hidden" name="template_id" value="<?= $tpl['id'] ?>">
                        <td><input type="text" name="name" value="<?= safe_text($tpl['name']) ?>"></td>
                        <td>
                            <select name="content_type">
                                <option value="text" <?= $tpl['content_type'] === 'text' ? 'selected' : '' ?>>text</option>
                                <option value="flex" <?= $tpl['content_type'] === 'flex' ? 'selected' : '' ?>>flex</option>
                            </select>
                        </td>
                        <td><textarea class="form-control" name="content_json" rows="4" cols="40"><?= safe_text($tpl['content_json']) ?></textarea></td>
                        <td>
                            <button type="submit">บันทึก</button>
                            <a href="?delete=<?= $tpl['id'] ?>" onclick="return confirm('ลบเทมเพลตนี้?')">ลบ</a>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="broadcast_create.php">← กลับหน้าสร้างแคมเปญ</a></p>
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>