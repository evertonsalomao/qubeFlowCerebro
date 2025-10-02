<?php
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? 0;

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$query = "SELECT company_id FROM user_company_access WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

$access = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($access);
?>