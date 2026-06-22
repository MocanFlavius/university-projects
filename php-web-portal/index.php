<?php
// index.php
session_start();
require_once 'vendor/autoload.php';
require_once 'db.php'; 

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

$stmt = $pdo->query("SELECT * FROM galleries ORDER BY id DESC");
$toateGaleriile = $stmt->fetchAll();

echo $twig->render('index.tpl.html', [
    'titlu' => 'Acasă - Călătorii prin lume', 
    'session' => $_SESSION,
    'slides' => $toateGaleriile,
    'galleries' => $toateGaleriile
]);
?>