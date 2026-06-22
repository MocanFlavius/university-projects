<?php
// edit.php
session_start();
require_once 'vendor/autoload.php';
require_once 'db.php';

// Verificăm doar dacă este logat
if (!isset($_SESSION['logat']) || $_SESSION['logat'] !== true) {
    header("Location: login.php"); 
    exit;
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)$_POST['id'];

    // Verificăm a cui este galeria înainte să permitem modificarea ei
    $stmtCheck = $pdo->prepare("SELECT user_id FROM galleries WHERE id = :id");
    $stmtCheck->execute(['id' => $id]);
    $owner_id = $stmtCheck->fetchColumn();

    if ($_SESSION['role'] !== 'admin' && $owner_id != $_SESSION['user_id']) {
        header("Location: index.php");
        exit;
    }

    $titlu = htmlspecialchars(trim($_POST['titlu']));
    $descriere_scurta = htmlspecialchars(trim($_POST['descriere_scurta']));
    $descriere_lunga = htmlspecialchars(trim($_POST['descriere_lunga']));

    // Actualizăm întâi textele
    $sql = "UPDATE galleries SET title = :titlu, title_description = :desc_scurta, long_description = :desc_lunga WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['titlu' => $titlu, 'desc_scurta' => $descriere_scurta, 'desc_lunga' => $descriere_lunga, 'id' => $id]);

    // Dacă utilizatorul a urcat o poză noua de copertă
    if (isset($_FILES['poza']) && $_FILES['poza']['error'] == 0) {
        $numeFisierSalvat = basename($_FILES["poza"]["name"]);
        $caleCompleta = "images/" . $numeFisierSalvat;
        
        if (move_uploaded_file($_FILES["poza"]["tmp_name"], $caleCompleta)) {
            $stmtImg = $pdo->prepare("UPDATE galleries SET img = :img WHERE id = :id");
            $stmtImg->execute(['img' => $numeFisierSalvat, 'id' => $id]);
        }
    }
    
    // Procesarea imaginilor adăugate pentru carusel
    if (!empty($_FILES['poze_carusel']['name'][0])) {
        foreach ($_FILES['poze_carusel']['tmp_name'] as $key => $tmp_name) {
            // Generăm un nume unic pentru a evita suprascrierea
            $numeFisierCarusel = time() . '_' . basename($_FILES['poze_carusel']['name'][$key]);
            $caleCompletaCarusel = "images/" . $numeFisierCarusel;
            
            $extensieCarusel = strtolower(pathinfo($caleCompletaCarusel, PATHINFO_EXTENSION));
            
            if (in_array($extensieCarusel, ['jpg', 'jpeg', 'png'])) {
                if (move_uploaded_file($tmp_name, $caleCompletaCarusel)) {
                    $sql_pic = "INSERT INTO pictures (id_gallery, picture_path) VALUES (:id_gallery, :picture_path)";
                    $stmt_pic = $pdo->prepare($sql_pic);
                    $stmt_pic->execute([
                        'id_gallery' => $id,
                        'picture_path' => $numeFisierCarusel
                    ]);
                }
            }
        }
    }
    
    // Ne întoarcem automat la panoul corect în funcție de rol
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: user_panel.php");
    }
    exit;
}

// Dacă doar am accesat pagina dând click pe "Editează"
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Extragem datele galeriei curente pentru a le pune în formular
    $stmt = $pdo->prepare("SELECT * FROM galleries WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $gallery = $stmt->fetch();

    if (!$gallery) {
        header("Location: index.php"); 
        exit;
    }

    // Dacă nu ești admin si nu ești autorul acestei galerii
    if ($_SESSION['role'] !== 'admin' && $gallery['user_id'] != $_SESSION['user_id']) {
        header("Location: index.php");
        exit;
    }

    echo $twig->render('edit.tpl.html', [
        'titlu' => 'Editează Galeria',
        'session' => $_SESSION,
        'gallery' => $gallery
    ]);
} else {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: user_panel.php");
    }
    exit;
}
?>