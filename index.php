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
$pescabile = date('H:i') >= '22:00' && date('H:i') <= '23:30';

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
    <title>HC Rugaciune</title>
    <link rel="icon" type="image/png" href="favicon.png">

    <style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Oswald:wght@200..700&family=Playball&display=swap');

body {
    font-family: "Oswald";
    background: url('hc.jpg') repeat-x;
    background-size: cover;
    display: flex;
    justify-content: center;
    align-items: flex-start; /* Allinea gli elementi in alto */
    height: 100vh;
    margin: 0;
    overflow: hidden;
    animation: slideBackground 20s linear infinite; /* Regola la velocità cambiando 20s */
    padding-top: 50px; /* Aggiunge spazio sopra */
}

@keyframes slideBackground {
    from {
        background-position: 100% center;
    }
    to {
        background-position: 0% center;
    }
}

.overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
}

.container {
    position: relative;
    background-color: rgb(255, 255, 255, 0.8); /* Trasparenza al 70% */
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    box-sizing: border-box; /* Per evitare overflow */
    margin-top: 50px; /* Solleva il form dal bordo superiore */
}

input[type="text"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box; /* Per evitare overflow */
}

button {
    font-family: "Oswald", normal;
    font-size: 17px;
    background-color: rgba(106, 188, 212, 0.84);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
    width: 100%;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    box-sizing: border-box; /* Per evitare overflow */
}

button:hover {
    background-color: rgba(78, 171, 199, 0.84);
}

button:disabled {
    background-color: #c0c0c0;
    cursor: not-allowed;
    color: black;
}

p {
    font-size: 1.2em;
    margin: 20px 0;
}

.errore {
    color: red;
    font-weight: bold;
}

/* Media query per dispositivi mobili */
@media screen and (max-width: 768px) {
    body {
        padding-top: 30px; /* Meno spazio sopra per schermi piccoli */
    }
    
    .container {
        width: 95%;
        max-width: 350px;
        padding: 15px;
        margin-top: 30px; /* Riduce lo spazio sopra su dispositivi più piccoli */
    }

    input[type="text"], button {
        font-size: 15px;
        padding: 8px;
    }

    p {
        font-size: 1em;
    }
}

/* Media query per dispositivi molto piccoli (es. telefoni in modalità verticale) */
@media screen and (max-width: 480px) {
    .container {
        padding: 10px;
        margin-top: 90px; /* Ancora meno spazio sopra per telefoni piccoli */
    }

    input[type="text"], button {
        font-size: 14px;
    }

    p {
        font-size: 0.9em;
    }
}


    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="container">
        <?php if (!$nome): ?>
            <form method="POST">
                <input type="text" name="nome" placeholder="Introdu numele și prenumele tău." required>
                <button type="submit">Introduceți</button>
                <?php if (isset($errore)): ?>
                    <p class="errore"><?php echo $errore; ?></p>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <?php if (!$persona_assegnata): ?>
                <form method="POST">
                    <p>Pace, <b><?php echo htmlspecialchars($_COOKIE['nome']); ?>!</b></p>
                    <button type="submit" name="pesca" <?php echo $pescabile ? '' : 'disabled'; ?>>Descoperă persoana pentru care va trebui să te rogi</button>
                </form>
            <?php else: ?>
                <p>Trebuie să te rogi pentru: <strong><?php echo htmlspecialchars($persona_assegnata); ?></strong></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>