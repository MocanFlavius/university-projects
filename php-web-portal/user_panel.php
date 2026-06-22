<?php
// user_panel.php
session_start();
require_once 'vendor/autoload.php';
require_once 'db.php';

// Verificăm doar dacă este logat
if (!isset($_SESSION['logat']) || $_SESSION['logat'] !== true) {
    header("Location: login.php"); 
    exit;
}

// Dacă un admin ajunge aici
if ($_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit;
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
$mesaj = '';
$user_id_curent = $_SESSION['user_id'];

// LOGICA PENTRU ȘTERGEREA UNEI GALERII PROPRII
if (isset($_GET['sterge'])) {
    $id_de_sters = (int)$_GET['sterge'];
    
    // Ne asigurăm că galeria îi aparține
    $stmtCheck = $pdo->prepare("SELECT img FROM galleries WHERE id = :id AND user_id = :user_id");
    $stmtCheck->execute(['id' => $id_de_sters, 'user_id' => $user_id_curent]);
    $poza = $stmtCheck->fetchColumn();
    
    if ($poza) {
        if (file_exists("images/" . $poza)) { unlink("images/" . $poza); }

        $stmtPozeCarusel = $pdo->prepare("SELECT picture_path FROM pictures WHERE id_gallery = :id");
        $stmtPozeCarusel->execute(['id' => $id_de_sters]);
        $pozeCarusel = $stmtPozeCarusel->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($pozeCarusel as $pozaCarusel) {
            if ($pozaCarusel && file_exists("images/" . $pozaCarusel)) {
                unlink("images/" . $pozaCarusel);
            }
        }

        // Ștergem din DB
        $stmtDel = $pdo->prepare("DELETE FROM galleries WHERE id = :id");
        $stmtDel->execute(['id' => $id_de_sters]);
        $mesaj = "Galeria ta a fost ștearsă cu succes!";
    } else {
        $mesaj = "Eroare: Nu ai permisiunea să ștergi această galerie!";
    }
}

// LOGICA PENTRU ADĂUGARE GALERIE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["poza"])) {
    $titlu = htmlspecialchars(trim($_POST['titlu']));
    $descriere_scurta = htmlspecialchars(trim($_POST['descriere_scurta']));
    $descriere_lunga = htmlspecialchars(trim($_POST['descriere_lunga']));
    
    $numeFisierSalvat = basename($_FILES["poza"]["name"]);
    $caleCompleta = "images/" . $numeFisierSalvat;
    
    $extensie = strtolower(pathinfo($caleCompleta, PATHINFO_EXTENSION));
    if (in_array($extensie, ["jpg", "jpeg", "png"])) {
        if (move_uploaded_file($_FILES["poza"]["tmp_name"], $caleCompleta)) {
            
            $sql = "INSERT INTO galleries (title, title_description, img, long_description, user_id) VALUES (:titlu, :desc_scurta, :img, :desc_lunga, :user_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'titlu' => $titlu,
                'desc_scurta' => $descriere_scurta,
                'img' => $numeFisierSalvat,
                'desc_lunga' => $descriere_lunga,
                'user_id' => $user_id_curent
            ]);
            
            $id_galerie_noua = $pdo->lastInsertId();

            if (!empty($_FILES['poze_carusel']['name'][0])) {
                foreach ($_FILES['poze_carusel']['tmp_name'] as $key => $tmp_name) {
                    $numeFisierCarusel = time() . '_' . basename($_FILES['poze_carusel']['name'][$key]);
                    $caleCompletaCarusel = "images/" . $numeFisierCarusel;
                    $extensieCarusel = strtolower(pathinfo($caleCompletaCarusel, PATHINFO_EXTENSION));
                    
                    if (in_array($extensieCarusel, ['jpg', 'jpeg', 'png'])) {
                        if (move_uploaded_file($tmp_name, $caleCompletaCarusel)) {
                            $sql_pic = "INSERT INTO pictures (id_gallery, picture_path) VALUES (:id_gallery, :picture_path)";
                            $stmt_pic = $pdo->prepare($sql_pic);
                            $stmt_pic->execute([
                                'id_gallery' => $id_galerie_noua,
                                'picture_path' => $numeFisierCarusel
                            ]);
                        }
                    }
                }
            }
            $mesaj = "Galeria a fost adăugată cu succes!";
        } else {
            $mesaj = "Eroare la mutarea fișierului.";
        }
    } else {
        $mesaj = "Sunt permise doar fișiere JPG, JPEG și PNG.";
    }
}

// Extragem doar galeriile care aparțin acestui utilizator
$stmtGal = $pdo->prepare("SELECT * FROM galleries WHERE user_id = :user_id ORDER BY id DESC");
$stmtGal->execute(['user_id' => $user_id_curent]);
$galleries = $stmtGal->fetchAll();

echo $twig->render('user_panel.tpl.html', [
    'titlu' => 'Panoul Meu',
    'session' => $_SESSION,
    'username' => $_SESSION['username'],
    'mesaj' => $mesaj,
    'galleries' => $galleries
]);
?>