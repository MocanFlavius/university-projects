<?php
// gallery.php
session_start();
require_once 'vendor/autoload.php';
require_once 'db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['logat']) && isset($_POST['comentariu'])) {
    $comentariu = htmlspecialchars(trim($_POST['comentariu']));
    
    $sql = "INSERT INTO comments (gallery_id, user_id, comment) VALUES (:gallery_id, :user_id, :comment)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'gallery_id' => $id,
        'user_id' => $_SESSION['user_id'],
        'comment' => $comentariu
    ]);
    
    header("Location: gallery.php?id=" . $id);
    exit;
}

$stmt = $pdo->prepare("
    SELECT g.*, u.user AS autor_nume 
    FROM galleries g 
    LEFT JOIN users u ON g.user_id = u.id 
    WHERE g.id = :id
");
$stmt->execute(['id' => $id]);
$gallery = $stmt->fetch();

if (!$gallery) {
    header("Location: index.php");
    exit;
}

$stmtComm = $pdo->prepare("
    SELECT comments.*, users.user as username 
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    WHERE comments.gallery_id = :id 
    ORDER BY comments.created_at DESC
");
$stmtComm->execute(['id' => $id]);
$comments = $stmtComm->fetchAll();


$stmtPoze = $pdo->prepare("SELECT * FROM pictures WHERE id_gallery = :id");
$stmtPoze->execute(['id' => $id]);
$poze = $stmtPoze->fetchAll();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

echo $twig->render('gallery.tpl.html', [
    'titlu' => $gallery['title'] . ' - PhotoGallery',
    'session' => $_SESSION,
    'gallery' => $gallery,
    'comments' => $comments,
    'poze' => $poze
]);
?>