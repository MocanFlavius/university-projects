<?php
// login.php
session_start();
require_once 'vendor/autoload.php';
require_once 'db.php';

// Dacă ești deja autentificat
if (isset($_SESSION['logat']) && $_SESSION['logat'] === true) {
    header("Location: index.php");
    exit;
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

$eroare = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Căutăm utilizatorul în baza de date
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user = :user LIMIT 1");
    $stmt->execute(['user' => $username]);
    $user = $stmt->fetch();

    // Verificăm parola
    if ($user && password_verify($password, $user['password'])) {
        
        // Verificăm dacă utilizatorul are ban
        if (isset($user['is_banned']) && $user['is_banned'] == 1) {
            $eroare = 'Contul tău a fost suspendat. Nu te poți autentifica.';
        } else {
            $_SESSION['logat'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['user'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            

            if ($user['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit;
        }

    } else {
        $eroare = 'Credențiale incorecte. Acces respins!';
    }
}

echo $twig->render('login.tpl.html', [
    'titlu' => 'Autentificare - Călătorii prin lume',
    'session' => $_SESSION,
    'eroare' => $eroare
]);
?>