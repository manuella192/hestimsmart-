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

if ($filiere_id<=0 || $niveau_id<=0 || $annee==='') {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'Paramètres manquants (filiere_id, niveau_id, annee_scolaire).']);
  exit;
}

try {
  $sql = "
    SELECT
      ea.id AS affectation_id,
      ea.annee_scolaire,
      c.id AS cours_id, c.code AS cours_code, c.nom AS cours_nom,
      e.`ID-ENSEIGNANT` AS enseignant_id,
      CONCAT_WS(' ', e.PRENOM, e.NOM) AS enseignant_nom,
      e.EMAIL AS enseignant_email
    FROM enseignant_affectations ea
    INNER JOIN cours c ON c.id = ea.cours_id
    INNER JOIN enseignant e ON e.`ID-ENSEIGNANT` = ea.enseignant_id
    WHERE ea.filiere_id = :filiere
      AND ea.niveau_id  = :niveau
      AND ea.annee_scolaire = :annee
    ORDER BY c.nom, enseignant_nom
  ";

  $stmt = $conn->prepare($sql);
  $stmt->execute([
    ':filiere'=>$filiere_id,
    ':niveau'=>$niveau_id,
    ':annee'=>$annee
  ]);

  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.','debug'=>$e->getMessage()]);
}
