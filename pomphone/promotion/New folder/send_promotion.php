<?php
// send_promotion.php - อัปเกรดระบบเลือกเทมเพลต พร้อม preview Flex

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');
require_once('../line/line_functions.php');
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

$msg = '';

$templates = $pdo->query("SELECT * FROM promotion_templates ORDER BY created_at DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_id = $_POST['template_id'] ?? '';
    $message = trim($_POST['message']);
    $image_url = trim($_POST['image_url']);
    $flex_json = trim($_POST['flex_json']);
    $filters = $_POST['filters'] ?? [];
    $schedule = $_POST['schedule'] ?? '';

    // Template override
    if ($template_id && $template_id !== '') {
        $stmt = $pdo->prepare("SELECT * FROM promotion_templates WHERE id = ? LIMIT 1");
        $stmt->execute([$template_id]);
        $tpl = $stmt->fetch();
        if ($tpl) {
            $message = $tpl['message'] ?? $message;
            $image_url = $tpl['image_url'] ?? $image_url;
            $flex_json = $tpl['flex_json'] ?? $flex_json;
        }
    }

    $sql = "SELECT lu.line_user_id, ca.cua_id FROM line_users lu JOIN customer_account ca ON lu.cua_id = ca.cua_id WHERE lu.user_type = 'customer'";

    $params = [];

    if (!empty($_POST['tag'])) {
        $sql .= " AND lu.cua_id IN (SELECT cua_id FROM tag_assignments ta JOIN customer_tags ct ON ta.tag_id = ct.id WHERE ct.name = ?)";
        $params[] = $_POST['tag'];
    }
    if (!empty($_POST['rank'])) {
        $sql .= " AND ca.cua_rank = ?";
        $params[] = $_POST['rank'];
    }
    if (in_array('birthday_today', $filters)) {
        $sql .= " AND DATE_FORMAT(ca.cua_birthday, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')";
    }
    if (!empty($_POST['purchase_from']) && !empty($_POST['purchase_to'])) {
        $sql .= " AND ca.cua_id IN (
          SELECT sale.customer_id FROM sale
          WHERE sale.customer_id IS NOT NULL
          AND DATE(sale.sale_time) BETWEEN ? AND ?
          GROUP BY customer_id
          HAVING SUM(final_amount) BETWEEN ? AND ?
        )";
        $params[] = $_POST['purchase_start'];
        $params[] = $_POST['purchase_end'];
        $params[] = $_POST['purchase_from'];
        $params[] = $_POST['purchase_to'];
    }

    if ($message || $image_url || $flex_json) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $recipients_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $content = [];
        if ($message) {
            $content[] = ["type" => "text", "text" => $message];
        }
        if ($image_url !== '') {
            $content[] = [
                "type" => "image",
                "originalContentUrl" => $image_url,
                "previewImageUrl" => $image_url
            ];
        }

        if ($flex_json !== '') {
            $flex = json_decode($flex_json, true);
           	if (!$flex) {
	        	error_log("❌ Flex JSON decode error: " . json_last_error_msg());
	    	}
            if ($flex) {
                $content[] = [
                    "type" => "flex",
                    "altText" => "\ud83d\udce2 \u0e42\u0e1b\u0e23\u0e42\u0e21\u0e0a\u0e31\u0e19",
                    "contents" => $flex
                ];
            }
        }


        $log_stmt = $pdo->prepare("INSERT INTO promotion_logs (template_id, message, flex_json, schedule, recipients) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->execute([$template_id ?: null, $message, $flex_json, $schedule ?: date('Y-m-d H:i:s'), count($recipients_raw)]);
        $log_id = $pdo->lastInsertId();

        $rec_stmt = $pdo->prepare("INSERT INTO promotion_recipients (log_id, line_user_id, cua_id) VALUES (?, ?, ?)");

		foreach ($recipients_raw as $rec) {
		    $line_id = $rec['line_user_id'] ?? null;
		    $cua_id = $rec['cua_id'] ?? null;

		    if (!$schedule && $line_id) {
		        pushMessage($line_id, $content);
		    }

		    if ($line_id && $cua_id) {
		        $rec_stmt->execute([$log_id, $line_id, $cua_id]);
		    }
		}

        $msg = $schedule ? "\ud83d\udd33\ufe0f ตั้งเวลาส่งแล้ว จำนวน " . count($recipients_raw) . " ราย"
                         : " ส่งข้อความสำเร็จถึงลูกค้า " . count($recipients_raw) . " ราย";

    } else {
        $msg = "\u0e01\u0e23\u0e38\u0e13\u0e32\u0e01\u0e23\u0e2d\u0e01\u0e2d\u0e22\u0e48\u0e32\u0e07\u0e19\u0e49\u0e2d\u0e22\u0e02\u0e49\u0e2d\u0e04\u0e27\u0e32\u0e21\u0e2b\u0e23\u0e37\u0e2d Flex";
    }
}

$tags = $pdo->query("SELECT * FROM customer_tags ORDER BY name ASC")->fetchAll();
$ranks = $pdo->query("SELECT DISTINCT cua_rank FROM customer_account WHERE cua_rank IS NOT NULL ORDER BY cua_rank ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="main-content">
  <div class="section__content section__content--p30">
    <div class="container-fluid">
      <h3 class="mb-4">ส่งข้อความโปรโมชัน</h3>
      <?php if ($msg): ?>
        <div class="alert alert-info"> <?= htmlspecialchars($msg) ?> </div>
      <?php endif; ?>
      <form method="post">
        <div class="form-group">
          <label>เลือกเทมเพลต:</label>
          <select name="template_id" class="form-control" onchange="loadTemplatePreview(this.value)">
            <option value="">-- ไม่เลือกเทมเพลต --</option>
            <?php foreach ($templates as $tpl): ?>
              <option value="<?= $tpl['id'] ?>"> <?= htmlspecialchars($tpl['title']) ?> </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="template_preview" class="alert alert-secondary" style="display:none"></div>

        <div class="form-group">
          <label>เลือกแท็กลูกค้า (optional):</label>
          <select name="tag" class="form-control">
            <option value="">-- เลือกแท็ก --</option>
            <?php foreach ($tags as $tag): ?>
              <option value="<?= htmlspecialchars($tag['name']) ?>"> <?= htmlspecialchars($tag['name']) ?> </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>ระดับลูกค้า (Rank):</label>
          <select name="rank" class="form-control">
            <option value="">-- ทั้งหมด --</option>
            <?php foreach ($ranks as $rank): ?>
              <option value="<?= $rank ?>">ระดับ <?= $rank ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="filters[]" value="birthday_today" id="birthday_today">
          <label class="form-check-label" for="birthday_today">เฉพาะลูกค้าที่มีวันเกิดวันนี้</label>
        </div>
        <hr>
        <div class="form-group">
          <label>ยอดซื้อสะสม (ในช่วงเวลา):</label>
          <div class="form-row">
            <div class="col"><input type="date" name="purchase_start" class="form-control"></div>
            <div class="col"><input type="date" name="purchase_end" class="form-control"></div>
          </div>
          <div class="form-row mt-2">
            <div class="col"><input type="number" name="purchase_from" class="form-control" placeholder="ยอดซื้อจาก (บาท)"></div>
            <div class="col"><input type="number" name="purchase_to" class="form-control" placeholder="ถึงยอด (บาท)"></div>
          </div>
        </div>
        <hr>
        <div id="sendData"> 
        <div class="form-group">
          <label>ข้อความที่จะส่ง:</label>
          <textarea name="message" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label>แนบลิงก์รูปภาพ (optional):</label>
          <input type="url" name="image_url" class="form-control" placeholder="https://...">
        </div>
        <div class="form-group">
          <label>Flex Message JSON (optional):</label>
          <textarea name="flex_json" class="form-control" rows="4" id="flex_json_area"></textarea>
        </div>
        <div class="form-group">
          <label>ตั้งเวลาส่ง (ปล่อยว่างหากส่งทันที):</label>
          <input type="datetime-local" name="schedule" class="form-control">
        </div>
        </div>
        <button class="btn btn-primary">📤 ส่งข้อความ</button>
      </form>
    </div>
  </div>
</div>

<script>
       const templates = <?= json_encode($templates) ?>;
        function loadTemplatePreview(id) {
          const t = templates.find(x => x.id == id);
          if (!t) {
            document.getElementById('template_preview').innerHTML = '';
            document.getElementById("sendData").style.display = "block";
            document.getElementById("template_preview").style.display = "none";
            return;
          }
          let html = '';
          if (t.message) html += `<p><strong>ข้อความ:</strong> ${t.message}</p>`;
          if (t.image_url) html += `<p><img src="${t.image_url}" alt="Preview" style="max-width:100%; height:auto;"></p>`;
          if (t.flex_json) {
            html += `<div style="border:1px solid #ccc; padding:10px; background:#f9f9f9;"><pre>${t.flex_json}</pre></div>`;
          }
          document.getElementById('template_preview').innerHTML = html;
          document.getElementById("template_preview").style.display = "block";
          document.getElementById("sendData").style.display = "none";
        }
</script>

<?php include_once('../partials/footer.php'); ?>
