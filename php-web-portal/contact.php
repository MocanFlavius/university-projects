<?php
// contact.php
session_start();
require_once 'vendor/autoload.php';
require_once 'db.php'; 

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
$success = '';

// Verificăm dacă userul este logat
if (!isset($_SESSION['logat']) || $_SESSION['logat'] !== true) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preluăm doar mesajul din formular
    $mesaj = htmlspecialchars(trim($_POST['mesaj']));
    
    // Preluăm numele și emailul din sesiune
    $nume = $_SESSION['username'];
    $email = $_SESSION['email'];

    // Verificăm dacă avem un mesaj
    if (!empty($mesaj)) {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (nume, email, mesaj) VALUES (:nume, :email, :mesaj)");
        $stmt->execute([
            'nume' => $nume, 
            'email' => $email, 
            'mesaj' => $mesaj
        ]);

        $success = "Mesajul tău a fost recepționat, $nume! Îți mulțumim.";
    } else {
        $success = "Te rugăm să scrii un mesaj!";
    }
}

echo $twig->render('contact.tpl.html', [
    'titlu' => 'Contact - PhotoGallery',
    'session' => $_SESSION,
    'success' => $success
]);
?>