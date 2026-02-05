<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Session admin expirée']);
  exit;
}
require_once __DIR__ . '/db.php';

$filiere_id = (int)($_GET['filiere_id'] ?? 0);
$niveau_id  = (int)($_GET['niveau_id'] ?? 0);
$annee      = trim($_GET['annee_scolaire'] ?? '');
$mois       = (int)($_GET['mois'] ?? 0);

if ($filiere_id<=0 || $niveau_id<=0 || $annee==='' || $mois<1 || $mois>12) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'Paramètres invalides.']);
  exit;
}

try {
  $sqlW = "
    SELECT *
    FROM edt_weeks
    WHERE filiere_id=:f AND niveau_id=:n AND annee_scolaire=:a
      AND mois=:m
    ORDER BY date_debut
  ";
  $stmt = $conn->prepare($sqlW);
  $stmt->execute([
    ':f'=>$filiere_id,
    ':n'=>$niveau_id,
    ':a'=>$annee,
    ':m'=>$mois
  ]);
  $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (!$weeks) {
    echo json_encode(['success'=>true,'data'=>['weeks'=>[], 'sessions'=>[]]]);
    exit;
  }

  $weekIds = array_map(fn($w)=>(int)$w['id'], $weeks);
  $in = implode(',', array_fill(0, count($weekIds), '?'));

  $sqlS = "
    SELECT
      s.*,
      c.code AS cours_code,
      c.nom  AS cours_nom,
      CONCAT_WS(' ', e.PRENOM, e.NOM) AS enseignant_nom,
      e.EMAIL AS enseignant_email,
      sa.batiment, sa.nom AS salle_nom, sa.type AS salle_type, sa.etage
    FROM edt_sessions s
    INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
    INNER JOIN cours c ON c.id = ea.cours_id
    INNER JOIN enseignant e ON e.`ID-ENSEIGNANT` = ea.enseignant_id
    INNER JOIN salles sa ON sa.id = s.salle_id
    WHERE s.week_id IN ($in)
    ORDER BY s.jour_date, FIELD(s.slot,'M1','M2','A1','A2')
  ";
  $stmt2 = $conn->prepare($sqlS);
  $stmt2->execute($weekIds);
  $sessions = $stmt2->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['success'=>true,'data'=>['weeks'=>$weeks,'sessions'=>$sessions]]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.','debug'=>$e->getMessage()]);
}
