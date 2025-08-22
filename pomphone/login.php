<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/includes/bootstrap.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
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
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ | ร้านป้อมมือถือ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/theme.css" rel="stylesheet">
    <link href="vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f2f2f2;
        }
        .login-container {
            max-width: 400px;
            margin: 60px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .login-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 25px;
        }
        .btn-primary {
            width: 100%;
        }
        .alert {
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-title">เข้าสู่ระบบพนักงาน</div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= safe_text($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">ชื่อผู้ใช้</label>
            <input type="text" name="username" id="username" class="form-control" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">รหัสผ่าน</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
    </form>
</div>

<script src="vendor/jquery-3.2.1.min.js"></script>
<script src="vendor/bootstrap-4.1/popper.min.js"></script>
<script src="vendor/bootstrap-4.1/bootstrap.min.js"></script>
</body>
</html>
