<?php
session_start();

$json_file = 'partecipanti.json';
$preghiere_file = 'preghiere.json';

// Creazione file JSON se non esistono
if (!file_exists($json_file)) {
    file_put_contents($json_file, json_encode([]));
}
if (!file_exists($preghiere_file)) {
    file_put_contents($preghiere_file, json_encode([]));
}

$partecipanti = json_decode(file_get_contents($json_file), true);
$preghiere = json_decode(file_get_contents($preghiere_file), true);

function svuotaFile($file) {
    file_put_contents($file, json_encode([]));
}
date_default_timezone_set('Europe/Rome');
// Controlla se è sabato alle 22:39
$ora_corrente = date('D H:i'); // Ottieni il giorno della settimana e l'ora
if ($ora_corrente === 'Wed 17:00') {
    svuotaFile($json_file);
    svuotaFile($preghiere_file);
}

// Se la lista partecipanti è vuota, permette di reinserire il nome
if (empty($partecipanti)) {
    setcookie('nome', '', time() - 3600, "/"); // Cancella il cookie
    $nome = '';
} else {
    $nome = isset($_COOKIE['nome']) ? $_COOKIE['nome'] : ''; // Evita errore se il cookie non è impostato
}

// Inserimento nome
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = trim($_POST['nome']);
    if (!empty($nome)) {
        if (isset($partecipanti[$nome])) {
            // Nome già esistente, mostriamo un errore
            $errore = "Questo nome è già stato preso. Scegli un altro.";
        } else {
            $partecipanti[$nome] = 0;
            file_put_contents($json_file, json_encode($partecipanti));

            setcookie('nome', $nome, time() + (6 * 24 * 60 * 60), "/");
            header("Location: index.php");
            exit;
        }
    }
}

// Controllo se ha già pescato
$persona_assegnata = $preghiere[$nome] ?? '';

// Orario valido per pescare (tra le 16:00 e le 23:30)
$pescabile = date('H:i') >= '16:00' && date('H:i') <= '23:30';

// Se l'utente preme "Pesca" e può farlo
if (isset($_POST['pesca']) && $pescabile && $nome && empty($persona_assegnata)) {
    $disponibili = array_keys(array_filter($partecipanti, fn($val) => $val === 0));
    $disponibili = array_diff($disponibili, [$nome]); // Esclude se stesso

    if (empty($disponibili)) {
        foreach ($partecipanti as $key => $val) {
            $partecipanti[$key] = 0;
        }
        file_put_contents($json_file, json_encode($partecipanti));
        $disponibili = array_keys($partecipanti);
        $disponibili = array_diff($disponibili, [$nome]); // Esclude ancora se stesso
    }

    if (!empty($disponibili)) {
        shuffle($disponibili);
        $persona_assegnata = array_pop($disponibili);
        $preghiere[$nome] = $persona_assegnata;

        file_put_contents($preghiere_file, json_encode($preghiere));
        $partecipanti[$persona_assegnata] = 1;
        file_put_contents($json_file, json_encode($partecipanti));

        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preghiera Random</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f9fc;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        p {
            font-size: 1.2em;
            margin: 20px 0;
        }
        .errore {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$nome): ?>
            <form method="POST">
                <input type="text" name="nome" placeholder="Inserisci il tuo nome e cognome" required>
                <button type="submit">Inserisci</button>
                <?php if (isset($errore)): ?>
                    <p class="errore"><?php echo $errore; ?></p>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <?php if (!$persona_assegnata): ?>
                <form method="POST">
                    <p>Ciao, <?php echo htmlspecialchars($_COOKIE['nome']); ?>!</p>
                    <button type="submit" name="pesca" <?php echo $pescabile ? '' : 'disabled'; ?>>Pesca la persona per cui pregare</button>
                </form>
            <?php else: ?>
                <p>Devi pregare per: <strong><?php echo htmlspecialchars($persona_assegnata); ?></strong></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
