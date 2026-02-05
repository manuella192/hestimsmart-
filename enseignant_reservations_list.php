<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['enseignant_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Session enseignant expirée']);
  exit;
}

$enseignantId = (int)$_SESSION['enseignant_id'];

try {
  $sql = "
    SELECT
      r.id,
      r.enseignant_id,
      r.cours_nom,
      r.niveau,
      r.effectif,
      r.motif,
      r.motif_autre,
      r.date_debut,
      r.date_fin,
      r.statut,
      r.rejet_motif,
      r.created_at,
      s.batiment,
      s.nom AS salle_nom,
      s.type AS salle_type,
      s.etage,
      s.taille,
      s.capacite
    FROM reservations_salles r
    INNER JOIN salles s ON s.id = r.salle_id
    WHERE r.enseignant_id = :eid
    ORDER BY r.created_at DESC
    LIMIT 500
  ";

  $stmt = $conn->prepare($sql);
  $stmt->execute([':eid'=>$enseignantId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur SQL : '.$e->getMessage()]);
}
