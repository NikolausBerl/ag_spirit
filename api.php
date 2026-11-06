<?php
// api.php — Speichert und lädt Daten zentral auf dem Server
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$dataFile = __DIR__ . '/data.json';$adminPassword = "Paris"; // Ihr Passwort für Schreiboperationen

$method =$_SERVER['REQUEST_METHOD'];

// 1. Daten abrufen (GET)
if ($method === 'GET') {
    if (file_exists($dataFile)) {
        echo file_get_contents($dataFile);
    } else {
        // Falls noch keine data.json existiert, leeres Array liefern
        echo json_encode([]);
    }
    exit;
}

// 2. Daten speichern (POST)
if ($method === 'POST') {
    // Authentifizierung prüfen
    $headers = getallheaders();$providedAuth = isset($headers['X-Admin-Password']) ? trim($headers['X-Admin-Password']) : '';

    if ($providedAuth !==$adminPassword) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Nicht autorisiert: Ungültiges Passwort.']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $decoded = json_decode($rawInput, true);

    if ($decoded === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ungültige JSON-Daten empfangen.']);
        exit;
    }

    // Sicher mit Dateisperre schreiben, um Überschneidungen zu verhindern
    if (file_put_contents($dataFile, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Konnte Datei nicht auf dem Server schreiben. Prüfen Sie die Schreibrechte (chmod).']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Methode nicht erlaubt.']);