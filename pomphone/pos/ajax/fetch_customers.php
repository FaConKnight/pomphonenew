<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 1) {
    http_response_code(403);
    exit('Unauthorized');
}

$query = trim($_POST['query'] ?? '');
if ($query === '') {
    exit;
}

$sql = "
    SELECT cua_id, cua_name, cua_lastname, cua_tel, cua_username 
    FROM customer_account
    WHERE cua_name LIKE :q1
       OR cua_lastname LIKE :q2 
       OR cua_tel LIKE :q3 
       OR cua_username LIKE :q4
    ORDER BY cua_name ASC
    LIMIT 20
";

$stmt = $pdo->prepare($sql);
$search = '%' . $query . '%';
$stmt->execute([
    'q1' => $search,
    'q2' => $search,
    'q3' => $search,
    'q4' => $search,
]);
$results = $stmt->fetchAll();

foreach ($results as $row) {
    $id = $row['cua_id'];
    $fullname = htmlspecialchars($row['cua_name'] . ' ' . $row['cua_lastname']);
    $tel = htmlspecialchars($row['cua_tel']);
    $username = htmlspecialchars($row['cua_username']);

    echo '<a href="#" class="list-group-item list-group-item-action select-customer" data-id="' . $id . '" data-name="' . $fullname . '">';
    echo $fullname . ' (' . $tel . ') [' . $username . ']';
    echo '</a>';
}
