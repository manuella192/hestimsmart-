<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['enseignant_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Session expirée.']);
  exit;
}
require_once __DIR__ . '/db.php';

$enseignant_id = (int)$_SESSION['enseignant_id'];
$week_id = (int)($_GET['week_id'] ?? 0);

try {
  // ✅ 0) Récupérer le nom complet de l'enseignant
  // (table enseignant, clé `ID-ENSEIGNANT`)
  $tStmt = $conn->prepare("
    SELECT CONCAT_WS(' ', PRENOM, NOM) AS enseignant_nom
    FROM enseignant
    WHERE `ID-ENSEIGNANT` = :eid
    LIMIT 1
  ");
  $tStmt->execute([':eid' => $enseignant_id]);
  $enseignant_nom = (string)($tStmt->fetchColumn() ?: '');

  // 1) Liste semaines accessibles à cet enseignant
  $lStmt = $conn->prepare("
    SELECT DISTINCT w.id, w.label, w.date_debut, w.date_fin
    FROM edt_weeks w
    INNER JOIN edt_sessions s ON s.week_id = w.id
    INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
    WHERE ea.enseignant_id = :eid
    ORDER BY w.date_debut ASC
  ");
  $lStmt->execute([':eid'=>$enseignant_id]);
  $weeksList = $lStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  if (!count($weeksList)) {
    echo json_encode([
      'success'=>true,
      'data'=>[
        'week'=>null,
        'sessions'=>[],
        'weeks'=>[],
        'enseignant_nom'=>$enseignant_nom
      ]
    ]);
    exit;
  }

  $allowed = array_map(fn($w)=> (int)$w['id'], $weeksList);

  // 2) Choisir la semaine
  if ($week_id > 0 && in_array($week_id, $allowed, true)) {
    $wStmt = $conn->prepare("SELECT * FROM edt_weeks WHERE id=:wid LIMIT 1");
    $wStmt->execute([':wid'=>$week_id]);
    $week = $wStmt->fetch(PDO::FETCH_ASSOC);
  } else {
    // semaine courante sinon dernière
    $wStmt = $conn->prepare("
      SELECT w.*
      FROM edt_weeks w
      INNER JOIN edt_sessions s ON s.week_id = w.id
      INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
      WHERE ea.enseignant_id = :eid
        AND CURDATE() BETWEEN w.date_debut AND w.date_fin
      ORDER BY w.date_debut DESC
      LIMIT 1
    ");
    $wStmt->execute([':eid'=>$enseignant_id]);
    $week = $wStmt->fetch(PDO::FETCH_ASSOC);

    if (!$week) {
      $wStmt2 = $conn->prepare("
        SELECT w.*
        FROM edt_weeks w
        INNER JOIN edt_sessions s ON s.week_id = w.id
        INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
        WHERE ea.enseignant_id = :eid
        ORDER BY w.date_debut DESC
        LIMIT 1
      ");
      $wStmt2->execute([':eid'=>$enseignant_id]);
      $week = $wStmt2->fetch(PDO::FETCH_ASSOC);
    }
  }

  if (!$week) {
    echo json_encode([
      'success'=>true,
      'data'=>[
        'week'=>null,
        'sessions'=>[],
        'weeks'=>$weeksList,
        'enseignant_nom'=>$enseignant_nom
      ]
    ]);
    exit;
  }

  // 3) Sessions filtrées enseignant
  $sStmt = $conn->prepare("
    SELECT
      s.id, s.week_id, s.jour_date, s.slot, s.affectation_id, s.salle_id, s.notes,
      c.nom AS cours_nom,
      sa.batiment, sa.nom AS salle_nom
    FROM edt_sessions s
    INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
    INNER JOIN cours c ON c.id = ea.cours_id
    INNER JOIN salles sa ON sa.id = s.salle_id
    WHERE s.week_id = :wid
      AND ea.enseignant_id = :eid
    ORDER BY s.jour_date, FIELD(s.slot,'M1','M2','A1','A2')
  ");
  $sStmt->execute([':wid'=>(int)$week['id'], ':eid'=>$enseignant_id]);
  $sessions = $sStmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'success'=>true,
    'data'=>[
      'week'=>$week,
      'sessions'=>$sessions,
      'weeks'=>$weeksList,
      'enseignant_nom'=>$enseignant_nom
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.','debug'=>$e->getMessage()]);
}
