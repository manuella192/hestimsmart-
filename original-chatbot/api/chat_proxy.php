<?php
// api/chat_proxy.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// (optionnel) bloquer si non connecté
if (!isset($_SESSION['enseignant_id']) && !isset($_SESSION['etudiant_id']) && !isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['error' => true, 'response' => 'Session expirée.']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => true, 'response' => 'Méthode non autorisée.']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$message = trim((string)($data['message'] ?? ''));
if ($message === '') {
  http_response_code(400);
  echo json_encode(['error' => true, 'response' => 'Message vide.']);
  exit;
}

/**
 * URL du service Flask
 * - En dev: http://127.0.0.1:5000/chat
 * - En prod: http://127.0.0.1:5001/chat (ou un domaine interne)
 */
$flaskUrl = 'http://127.0.0.1:5000/chat';

// Appel HTTP vers Flask (cURL)
$ch = curl_init($flaskUrl);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode(['message' => $message], JSON_UNESCAPED_UNICODE),
  CURLOPT_TIMEOUT => 45,
]);

$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
  http_response_code(502);
  echo json_encode(['error' => true, 'response' => "Proxy erreur: $err"]);
  exit;
}

// Flask renvoie déjà {response, error}
http_response_code($httpCode ?: 200);
echo $response;
