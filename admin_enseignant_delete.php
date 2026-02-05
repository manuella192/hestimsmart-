<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Session expirée']); exit; }
require_once __DIR__ . '/db.php';

$id = (int)($_POST['id'] ?? 0);
if ($id<=0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID invalide']); exit; }

try {
  // FK sur enseignant_affectations ON DELETE CASCADE => ok
  $st = $conn->prepare("DELETE FROM enseignant WHERE `ID-ENSEIGNANT`=?");
  $st->execute([$id]);
  echo json_encode(['success'=>true,'message'=>'Enseignant supprimé.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.'], JSON_UNESCAPED_UNICODE);
}
