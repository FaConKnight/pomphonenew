<?php
// /cooladmin/login.php
define('SECURE_ACCESS', true);
session_start();
require_once('includes/connectdb.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        $error = "\u274c กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM employee_account WHERE em_username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['em_password'])) {
            $_SESSION['employee_id'] = $user['em_id'];
            $_SESSION['employee_rank'] = $user['em_rank'];
            $_SESSION['employee_name'] = $user['em_username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "\u274c ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ | ร้านป้อมมือถือ</title>
    <link href="assets/css/login.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h2>เข้าสู่ระบบ</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>ชื่อผู้ใช้</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
        </form>
    </div>
</body>
</html>
