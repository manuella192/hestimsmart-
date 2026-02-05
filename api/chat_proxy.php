<?php
// api/chat_proxy.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// (optionnel) bloquer si non connecté
if (!isset($_SESSION['enseignant_id']) && !isset($_SESSION['etudiant_id']) && !isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['error' => true, 'response' => 'Session expirée.'], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => true, 'response' => 'Méthode non autorisée.'], JSON_UNESCAPED_UNICODE);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$message = trim((string)($data['message'] ?? ''));
if ($message === '') {
  http_response_code(400);
  echo json_encode(['error' => true, 'response' => 'Message vide.'], JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * 1) Local (dev) : Flask sur la même machine
 * 2) Remote (prod) : PythonAnywhere (URL publique)
 */
$localUrl  = 'http://127.0.0.1:5000/chat';
$remoteUrl = 'https://dmt.pythonanywhere.com/chatbot/chat';

/**
 * Token optionnel pour sécuriser l’API PythonAnywhere.
 * Mets la même valeur que CHAT_API_TOKEN dans .env côté PythonAnywhere.
 * Si tu ne veux pas de token, laisse vide '' et supprime la vérif côté Flask.
 */
$CHAT_API_TOKEN = 'MET_TON_TOKEN_ICI'; // ex: 's3cr3t_long'

function callEndpoint($url, $message, $token = '') {
  $ch = curl_init($url);

  $headers = ['Content-Type: application/json'];
  if (!empty($token)) {
    $headers[] = 'X-CHAT-TOKEN: ' . $token;
  }

  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => json_encode(['message' => $message], JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 12,         // timeout total (court pour fallback rapide)
    CURLOPT_CONNECTTIMEOUT => 3,   // timeout connexion
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
  ]);

  $response = curl_exec($ch);
  $err      = curl_error($ch);
  $errno    = curl_errno($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return [
    'ok' => ($response !== false),
    'response' => $response,
    'httpCode' => $httpCode,
    'errno' => $errno,
    'err' => $err
  ];
}

/**
 * ✅ 1) Essayer local d’abord
 */
$tryLocal = callEndpoint($localUrl, $message, ''); // pas besoin de token en local

if ($tryLocal['ok'] && $tryLocal['httpCode'] >= 200 && $tryLocal['httpCode'] < 500) {
  // Si local répond correctement (même 400 etc) -> on renvoie tel quel
  http_response_code($tryLocal['httpCode'] ?: 200);
  echo $tryLocal['response'];
  exit;
}

/**
 * 2) Sinon fallback vers PythonAnywhere
 */
$tryRemote = callEndpoint($remoteUrl, $message, $CHAT_API_TOKEN);

if (!$tryRemote['ok']) {
  http_response_code(502);
  echo json_encode([
    'error' => true,
    'response' => 'Service chatbot indisponible (local et distant).',
    'debug' => [
      'local_errno' => $tryLocal['errno'],
      'local_error' => $tryLocal['err'],
      'remote_errno' => $tryRemote['errno'],
      'remote_error' => $tryRemote['err'],
    ]
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

// Remote répond -> renvoyer tel quel
http_response_code($tryRemote['httpCode'] ?: 200);
echo $tryRemote['response'];
