<?php
// register.php
session_start();
require_once 'vendor/autoload.php';
require_once 'db.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
$eroare = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Verificăm dacă parolele coincid
    if ($password !== $password_confirm) {
        $eroare = "Parolele nu coincid!";
    } else {
        // Verificăm dacă user-ul sau email-ul există deja
        $stmt = $pdo->prepare("SELECT id FROM users WHERE user = :user OR email = :email");
        $stmt->execute(['user' => $username, 'email' => $email]);
        
        if ($stmt->fetch()) {
            $eroare = "Numele de utilizator sau adresa de email este deja utilizată.";
        } else {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (user, password, email, role) VALUES (:user, :pass, :email, 'user')");
            $stmt->execute([
                'user' => $username, 
                'pass' => $password_hashed,
                'email' => $email
            ]);
            
            header("Location: login.php");
            exit;
        }
    }
}

echo $twig->render('register.tpl.html', [
    'titlu' => 'Înregistrare - Călătorii prin lume',
    'session' => $_SESSION,
    'eroare' => $eroare
]);
?>