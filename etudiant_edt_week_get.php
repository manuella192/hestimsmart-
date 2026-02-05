<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['etudiant_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Session expirée.']);
  exit;
}
require_once __DIR__ . '/db.php';

$etudiant_id = (int)$_SESSION['etudiant_id'];
$week_id = (int)($_GET['week_id'] ?? 0);

try {
  // 1) Récupérer filière/niveau/année scolaire de l'étudiant
  $stmt = $conn->prepare("
    SELECT filiere_id, niveau_id, annee_scolaire
    FROM inscriptions
    WHERE etudiant_id = :eid
    ORDER BY date_inscription DESC
    LIMIT 1
  ");
  $stmt->execute([':eid'=>$etudiant_id]);
  $insc = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$insc) {
    echo json_encode(['success'=>true,'data'=>['week'=>null,'sessions'=>[]]]);
    exit;
  }

  $filiere_id = (int)$insc['filiere_id'];
  $niveau_id  = (int)$insc['niveau_id'];
  $annee      = trim($insc['annee_scolaire']);

  // 2) Charger la semaine: soit week_id, soit "semaine qui contient aujourd'hui"
  if ($week_id > 0) {
    $wStmt = $conn->prepare("
      SELECT *
      FROM edt_weeks
      WHERE id = :wid
      LIMIT 1
    ");
    $wStmt->execute([':wid'=>$week_id]);
  } else {
    // semaine courante (aujourd'hui dans [date_debut, date_fin])
    $wStmt = $conn->prepare("
      SELECT *
      FROM edt_weeks
      WHERE filiere_id = :f AND niveau_id = :n AND annee_scolaire = :a
        AND CURDATE() BETWEEN date_debut AND date_fin
      ORDER BY date_debut DESC
      LIMIT 1
    ");
    $wStmt->execute([
      ':f'=>$filiere_id,
      ':n'=>$niveau_id,
      ':a'=>$annee
    ]);
  }

  $week = $wStmt->fetch(PDO::FETCH_ASSOC);

  if (!$week) {
    echo json_encode(['success'=>true,'data'=>['week'=>null,'sessions'=>[]]]);
    exit;
  }

  // 3) Sessions de la semaine + infos affichage
  $sStmt = $conn->prepare("
    SELECT
      s.id,
      s.week_id,
      s.jour_date,
      s.slot,
      s.affectation_id,
      s.salle_id,
      s.notes,

      c.nom AS cours_nom,
      ea.id AS affectation_id_real,

      CONCAT_WS(' ', e.PRENOM, e.NOM) AS enseignant_nom,
      sa.batiment,
      sa.nom AS salle_nom

    FROM edt_sessions s
    INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
    INNER JOIN cours c ON c.id = ea.cours_id
    INNER JOIN enseignant e ON e.`ID-ENSEIGNANT` = ea.enseignant_id
    INNER JOIN salles sa ON sa.id = s.salle_id
    WHERE s.week_id = :wid
    ORDER BY s.jour_date, FIELD(s.slot,'M1','M2','A1','A2')
  ");
  $sStmt->execute([':wid'=>(int)$week['id']]);
  $sessions = $sStmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['success'=>true,'data'=>['week'=>$week,'sessions'=>$sessions]]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.','debug'=>$e->getMessage()]);
}
