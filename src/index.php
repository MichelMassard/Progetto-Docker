<?php
$db = new PDO(
    "mysql:host=db;dbname=" . getenv('DB_NAME') . ";charset=utf8mb4",
    getenv('DB_USER'),
    getenv('DB_PASSWORD')
);
$db->exec("CREATE TABLE IF NOT EXISTS messaggi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    autore VARCHAR(50),
    testo TEXT,
    creato TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$lang = $_GET['lang'] ?? 'it';
if (!in_array($lang, ['it', 'en', 'de', 'fr'])) {
    $lang = 'it';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $db->prepare("INSERT INTO messaggi (autore, testo) VALUES (?, ?)");
    $st->execute([$_POST['autore'], $_POST['testo']]);
    header("Location: /?lang=" . $lang);
    exit;
}

function traduci($testo, $lang) {
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['q' => $testo, 'source' => 'auto', 'target' => $lang]),
        'timeout' => 5,
        'ignore_errors' => true,
    ]]);
    $r = @file_get_contents(getenv('TRANSLATE_URL') . '/translate', false, $ctx);
    $j = json_decode($r, true);
    return $j['translatedText'] ?? $testo . ' (traduzione non disponibile)';
}

$messaggi = $db->query("SELECT autore, testo, creato FROM messaggi ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$messaggi = array_reverse($messaggi);
$lingue = ['it' => 'Italiano', 'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français'];
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat con traduzione</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #eef1f5;
            color: #1f2933;
        }
        .chat {
            max-width: 640px;
            margin: 32px auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        header {
            background: #2563eb;
            color: #fff;
            padding: 18px 22px;
        }
        header h1 { margin: 0 0 12px; font-size: 1.3rem; }
        .lingue { display: flex; gap: 8px; align-items: center; font-size: 0.85rem; }
        .lingue a {
            color: #fff;
            text-decoration: none;
            padding: 4px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .lingue a.attiva { background: #fff; color: #2563eb; font-weight: 600; }
        .messaggi {
            padding: 20px 22px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 260px;
        }
        .msg {
            background: #f1f5f9;
            border-radius: 12px 12px 12px 4px;
            padding: 10px 14px;
            max-width: 85%;
        }
        .msg .autore { font-weight: 600; font-size: 0.85rem; color: #2563eb; }
        .msg .ora { font-size: 0.7rem; color: #7b8794; margin-left: 6px; }
        .msg .testo { margin-top: 4px; line-height: 1.4; overflow-wrap: anywhere; }
        .vuoto { color: #7b8794; text-align: center; margin: auto; }
        form {
            display: flex;
            gap: 8px;
            padding: 14px 22px;
            border-top: 1px solid #e4e7eb;
            background: #fafbfc;
        }
        input {
            padding: 10px 12px;
            border: 1px solid #cbd2d9;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        input[name=autore] { width: 120px; }
        input[name=testo] { flex: 1; min-width: 0; }
        input:focus { outline: 2px solid #93c5fd; border-color: #2563eb; }
        button {
            background: #2563eb;
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 0.95rem;
            cursor: pointer;
        }
        button:hover { background: #1d4ed8; }
    </style>
</head>
<body>
<div class="chat">
    <header>
        <h1>Chat con traduzione</h1>
        <div class="lingue">
            <span>Leggi in:</span>
            <?php foreach ($lingue as $codice => $nome): ?>
                <a href="?lang=<?= $codice ?>" class="<?= $codice === $lang ? 'attiva' : '' ?>"><?= $nome ?></a>
            <?php endforeach; ?>
        </div>
    </header>

    <div class="messaggi">
        <?php if (!$messaggi): ?>
            <p class="vuoto">Nessun messaggio. Scrivi il primo!</p>
        <?php endif; ?>
        <?php foreach ($messaggi as $m): ?>
            <div class="msg">
                <span class="autore"><?= htmlspecialchars($m['autore']) ?></span>
                <span class="ora"><?= htmlspecialchars(substr($m['creato'], 11, 5)) ?></span>
                <div class="testo"><?= htmlspecialchars(traduci($m['testo'], $lang)) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post">
        <input name="autore" placeholder="Nome" maxlength="50" required>
        <input name="testo" placeholder="Scrivi un messaggio..." required>
        <button>Invia</button>
    </form>
</div>
</body>
</html>