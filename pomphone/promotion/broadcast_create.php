<?php
// admin/broadcast_create.php - สร้างแคมเปญ Broadcast
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
// ดึงเทมเพลตข้อความทั้งหมด
$templates = $pdo->query("SELECT * FROM message_templates ORDER BY created_at DESC")->fetchAll();

// ดึงแท็กทั้งหมด
$tags = $pdo->query("SELECT * FROM tags ORDER BY tag_name ASC")->fetchAll();

$previewRecipients = [];

// สร้างแคมเปญจริง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_campaign'])) {
    $templateId = intval($_POST['template_id']);
    $selectedTags = $_POST['selected_tags'] ?? [];
    $scheduledAt = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : date('Y-m-d H:i:s');

    $condition = json_encode([
        'tags' => $selectedTags,
        'birthday_today' => isset($_POST['birthday_today']),
        'min_total_spent' => floatval($_POST['min_total_spent'] ?? 0)
    ]);

    $stmt = $pdo->prepare("INSERT INTO broadcast_jobs (title, send_condition, template_id, scheduled_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_POST['title'] ?? 'แคมเปญไม่มีชื่อ',
        $condition,
        $templateId,
        $scheduledAt
    ]);
    if(empty($_POST['scheduled_at'])) {
        //$url1 = " HTTP/1.0";
        //$url2 = " HTTP/1.0";
        http_trigger("https://{$_SERVER['HTTP_HOST']}/CRM/cron/broadcast_dispatcher.php");
        http_trigger("https://{$_SERVER['HTTP_HOST']}/CRM/cron/send_messages_worker.php");
        //file_get_contents($url1);
        //file_get_contents($url2);
    }
    header("Location: broadcast_log.php");
    exit;
}
function http_trigger($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
// Preview กลุ่มเป้าหมาย
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview_recipients'])) {
    $selectedTags = $_POST['selected_tags'] ?? [];
    $birthdayToday = isset($_POST['birthday_today']);
    $minTotalSpent = floatval($_POST['min_total_spent'] ?? 0);

    $conditions = [];
    $params = [];

    if (!empty($selectedTags)) {
        $placeholders = implode(',', array_fill(0, count($selectedTags), '?'));
        $conditions[] = "c.id IN (SELECT customer_id FROM customer_tags WHERE tag_id IN ($placeholders))";
        $params = array_merge($params, $selectedTags);
    }

    if ($birthdayToday) {
        $conditions[] = "DATE_FORMAT(birthday, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')";
    }

    if ($minTotalSpent > 0) {
        // ต้องเปลี่ยนในอนาคตให้เชื่อมกับยอดจริง
        $conditions[] = "c.id IN (1,2,3)"; // จำลอง ID ลูกค้าที่มียอดซื้อเกิน
    }

    $sql = "SELECT * FROM customers c";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    $sql .= " ORDER BY c.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $previewRecipients = $stmt->fetchAll();
}
?>

<main class="main-content p-4">
    <h2>สร้าง Broadcast</h2>
    <form method="POST" onsubmit="return confirmImmediateSend();">
        <p>
            <label>ชื่อแคมเปญ: <input class="form-control" type="text" name="title" required></label>
        </p>

        <p>
            <label>เลือกแท็กกลุ่มเป้าหมาย:</label><br>
            <?php foreach ($tags as $tag): ?>
                <label>
                    <input type="checkbox" name="selected_tags[]" value="<?= $tag['id'] ?>"> <?= safe_text($tag['tag_name']) ?>
                </label><br>
            <?php endforeach; ?>
        </p>

        <p>
            <label><input type="checkbox" name="birthday_today"> วันเกิดวันนี้</label><br>
            <label>ยอดซื้อสะสมขั้นต่ำ: <input class="form-control" type="number" name="min_total_spent" step="0.01" min="0"></label>
        </p>

        <p>
            <label>เลือกเทมเพลตข้อความ:</label><a href="./message_templates.php">💬 สร้างเทมเพลตข้อความ</a><br> 
            <select name="template_id" required>
                <option value="">-- เลือก --</option>
                <?php foreach ($templates as $tpl): ?>
                    <option value="<?= $tpl['id'] ?>">[<?= $tpl['content_type'] ?>] <?= safe_text($tpl['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label>ตั้งเวลาส่ง (ถ้าไม่ใส่ = ส่งทันที):
                <input type="datetime-local" name="scheduled_at" class="form-control">
            </label>
        </p>

        <p>
            <button class="btn btn-primary" type="submit" name="preview_recipients">Preview รายชื่อกลุ่มเป้าหมาย</button>
            <button class="btn btn-success"type="submit" name="create_campaign">สร้างแคมเปญ</button>
        </p>
    </form>

    <?php if (!empty($previewRecipients)): ?>
        <h3>รายชื่อกลุ่มเป้าหมายที่จะได้รับข้อความ</h3>
        <ul>
            <?php foreach ($previewRecipients as $rec): ?>
                <li><?= safe_text($rec['display_name']) ?> (<?= safe_text($rec['phone']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><a href="broadcast_log.php">→ ดูประวัติการส่ง</a></p>
    <script>
function confirmImmediateSend() {
    const scheduledInput = document.querySelector('input[name="scheduled_at"]');
    if (!scheduledInput.value) {
        return confirm('คุณไม่ได้ตั้งเวลาส่ง แคมเปญนี้จะถูกส่งทันที\nคุณแน่ใจหรือไม่ว่าจะดำเนินการต่อ?');
    }
    return true;
}
</script>

</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
