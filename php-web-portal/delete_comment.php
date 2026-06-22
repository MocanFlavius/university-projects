<?php
session_start();
require_once 'db.php';

// Verificăm dacă user-ul este admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

header("Location: admin.php");
exit;
?>