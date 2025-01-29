<?php
session_start();

$json_file = 'partecipanti.json';

// Controllo se l'admin è autenticato
$admin_password = 'admin123'; // Cambia questa password

if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
    $_SESSION['admin'] = true;
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    header('Location: admin.php');
    exit();
}

if (isset($_SESSION['admin']) && isset($_POST['reset'])) {
    file_put_contents($json_file, json_encode([]));
    setcookie("assegnato", "", time() - 3600, "/"); // Cancella il cookie assegnato
    $message = "Partecipanti eliminati con successo.";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestione Partecipanti</title>
</head>
<body>
    <?php if (!isset($_SESSION['admin'])): ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Inserisci password admin" required>
            <button type="submit">Accedi</button>
        </form>
    <?php else: ?>
        <h2>Pannello Admin</h2>
        <?php if (isset($message)) echo "<p>$message</p>"; ?>
        <form method="POST">
            <button type="submit" name="reset">Elimina tutti i partecipanti</button>
        </form>
        <a href="?logout">Esci</a>
    <?php endif; ?>
</body>
</html>
