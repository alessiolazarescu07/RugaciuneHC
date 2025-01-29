<?php
session_start();

$json_file = 'partecipanti.json';

if (!file_exists($json_file)) {
    file_put_contents($json_file, json_encode([]));
}

$partecipanti = json_decode(file_get_contents($json_file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = trim($_POST['nome']);
    if (!empty($nome) && !in_array($nome, $partecipanti)) {
        $partecipanti[] = $nome;
        file_put_contents($json_file, json_encode($partecipanti));
        $_SESSION['nome'] = $nome;
    }
}

$pescabile = false;
$ora_corrente = date('H:i');
if ($ora_corrente >= '22:00' && $ora_corrente <= '23:30') {
    $pescabile = true;
}

$persona_assegnata = isset($_SESSION['assegnato']) ? $_SESSION['assegnato'] : '';

if (isset($_POST['pesca']) && $pescabile && isset($_SESSION['nome']) && empty($persona_assegnata)) {
    $disponibili = array_diff($partecipanti, [$_SESSION['nome']]);
    if (!empty($disponibili)) {
        shuffle($disponibili);
        $persona_assegnata = array_pop($disponibili);
        $_SESSION['assegnato'] = $persona_assegnata;
        setcookie("assegnato", $persona_assegnata, time() + (86400 * 30), "/"); // Salva nei cookie per 30 giorni
    }
} elseif (isset($_COOKIE['assegnato']) && empty($persona_assegnata)) {
    $persona_assegnata = $_COOKIE['assegnato'];
    $_SESSION['assegnato'] = $persona_assegnata;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preghiera Random</title>
</head>
<body>
    <?php if (!isset($_SESSION['nome'])): ?>
        <form method="POST">
            <input type="text" name="nome" placeholder="Inserisci il tuo nome e cognome" required>
            <button type="submit">Inserisci</button>
        </form>
    <?php else: ?>
        <p>Ciao, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</p>
        <?php if (empty($persona_assegnata)): ?>
            <form method="POST">
                <button type="submit" name="pesca" <?php echo $pescabile ? '' : 'disabled'; ?>>Pesca la persona per cui pregare</button>
            </form>
        <?php else: ?>
            <p>Devi pregare per: <strong><?php echo htmlspecialchars($persona_assegnata); ?></strong></p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
