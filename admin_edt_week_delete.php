<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Session admin expirée']);
  exit;
}
require_once __DIR__ . '/db.php';

$week_id = (int)($_POST['week_id'] ?? 0);
if ($week_id<=0) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'week_id invalide.']);
  exit;
}

try {
  $stmt = $conn->prepare("DELETE FROM edt_weeks WHERE id=:id");
  $stmt->execute([':id'=>$week_id]);
  echo json_encode(['success'=>true,'message'=>'Semaine supprimée.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.','debug'=>$e->getMessage()]);
}
