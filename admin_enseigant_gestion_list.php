<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Session expirée']); exit; }
require_once __DIR__ . '/db.php';

try {
  $sql = "
    SELECT
      e.`ID-ENSEIGNANT` AS id,
      e.NOM AS nom,
      e.PRENOM AS prenom,
      e.EMAIL AS email,
      e.MDP AS mdp,
      CONCAT(e.PRENOM,' ',e.NOM) AS nom_complet
    FROM enseignant e
    ORDER BY e.`ID-ENSEIGNANT` DESC
  ";
  $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.'], JSON_UNESCAPED_UNICODE);
}
