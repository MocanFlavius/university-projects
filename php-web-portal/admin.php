<?php
// admin.php
session_start();
require_once 'vendor/autoload.php';
require_once 'db.php';

// Verificăm dacă este logat si dacă are rolul de admin
if (!isset($_SESSION['logat']) || $_SESSION['logat'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); 
    exit;
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);
$mesaj = '';

// LOGICA PENTRU BAN / UNBAN UTILIZATORI
if (isset($_GET['action']) && isset($_GET['id_user'])) {
    $action = $_GET['action'];
    $id_user = (int)$_GET['id_user'];

    // Verificăm să nu banăm admini
    $stmtCheck = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $stmtCheck->execute(['id' => $id_user]);
    $userRole = $stmtCheck->fetchColumn();

    if ($userRole && $userRole !== 'admin') {
        if ($action === 'ban') {
            $stmtBan = $pdo->prepare("UPDATE users SET is_banned = 1 WHERE id = :id");
            $stmtBan->execute(['id' => $id_user]);
            $mesaj = "Utilizatorul a fost banat cu succes!";
        } elseif ($action === 'unban') {
            $stmtUnban = $pdo->prepare("UPDATE users SET is_banned = 0 WHERE id = :id");
            $stmtUnban->execute(['id' => $id_user]);
            $mesaj = "Utilizatorul a fost debanat cu succes!";
        }
    } else {
        $mesaj = "Nu poți bana un cont de administrator!";
    }
}

// LOGICA PENTRU ȘTERGEREA UNEI GALERII
if (isset($_GET['sterge'])) {
    $id_de_sters = (int)$_GET['sterge'];
    
    // Ștergem imaginea reprezentativă
    $stmtImg = $pdo->prepare("SELECT 
    OM galleries WHERE id = :id");
    $stmtImg->execute(['id' => $id_de_sters]);
    $poza = $stmtImg->fetchColumn();
    if ($poza && file_exists("images/" . $poza)) {
        unlink("images/" . $poza);
    }

    // Ștergem pozele din carusel
    $stmtPozeCarusel = $pdo->prepare("SELECT picture_path FROM pictures WHERE id_gallery = :id");
    $stmtPozeCarusel->execute(['id' => $id_de_sters]);
    $pozeCarusel = $stmtPozeCarusel->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($pozeCarusel as $pozaCarusel) {
        if ($pozaCarusel && file_exists("images/" . $pozaCarusel)) {
            unlink("images/" . $pozaCarusel);
        }
    }

    // Ștergem galeria din baza de date
    $stmtDel = $pdo->prepare("DELETE FROM galleries WHERE id = :id");
    $stmtDel->execute(['id' => $id_de_sters]);
    $mesaj = "Galeria a fost ștearsă cu succes!";
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
            
            // Inserăm galeria principală 
            $sql = "INSERT INTO galleries (title, title_description, img, long_description, user_id) VALUES (:titlu, :desc_scurta, :img, :desc_lunga, :user_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'titlu' => $titlu,
                'desc_scurta' => $descriere_scurta,
                'img' => $numeFisierSalvat,
                'desc_lunga' => $descriere_lunga,
                'user_id' => $_SESSION['user_id']
            ]);
            
            // Logica pentru procesarea imaginilor din carusel
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

// Extragem toate galeriile
$stmtGal = $pdo->query("SELECT * FROM galleries ORDER BY id DESC");
$galleries = $stmtGal->fetchAll();

// Extragem mesajele de contact
$stmtMesaje = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$mesaje_contact = $stmtMesaje->fetchAll();

// Extragere comentarii
$stmtComments = $pdo->query("
    SELECT c.*, u.user, g.title AS gallery_title 
    FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id 
    LEFT JOIN galleries g ON c.gallery_id = g.id 
    ORDER BY c.created_at DESC
");
$comments = $stmtComments->fetchAll();

// EXTRAGERE ȘI CĂUTARE UTILIZATORI
$search_query = '';
if (isset($_GET['search_user']) && !empty(trim($_GET['search_user']))) {
    $search_query = trim($_GET['search_user']);
    $stmtUsers = $pdo->prepare("SELECT id, user, email, role, is_banned FROM users WHERE user LIKE :search1 OR email LIKE :search2 ORDER BY id DESC");
    $stmtUsers->execute([
        'search1' => '%' . $search_query . '%',
        'search2' => '%' . $search_query . '%'
    ]);
} else {
    // Dacă nu există căutare, afișează toți utilizatorii
    $stmtUsers = $pdo->query("SELECT id, user, email, role, is_banned FROM users ORDER BY id DESC");
}
$users_list = $stmtUsers->fetchAll();

echo $twig->render('admin.tpl.html', [
    'titlu' => 'Panou Administrare',
    'session' => $_SESSION,
    'username' => $_SESSION['username'] ?? 'Admin',
    'mesaj' => $mesaj,
    'galleries' => $galleries,
    'mesaje_contact' => $mesaje_contact,
    'comments' => $comments,
    'users_list' => $users_list,
    'search_query' => $search_query
]);
?>