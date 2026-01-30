<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Session expirée']); exit; }
require_once __DIR__ . '/db.php';

try {
  $sql = "
    SELECT
      a.id AS affectation_id,
      a.enseignant_id,
      a.annee_scolaire,
      a.groupe,

      c.id AS cours_id,
      c.code AS cours_code,
      c.nom  AS cours_nom,

      f.id AS filiere_id,
      f.nom AS filiere_nom,

      n.id AS niveau_id,
      n.libelle AS niveau_libelle,

      fc.semestre,

      CONCAT(e.PRENOM,' ',e.NOM) AS enseignant_nom
    FROM enseignant_affectations a
    JOIN cours c ON c.id = a.cours_id
    JOIN filieres f ON f.id = a.filiere_id
    JOIN niveaux n ON n.id = a.niveau_id
    LEFT JOIN filiere_cours fc
      ON fc.filiere_id = a.filiere_id
     AND fc.cours_id = a.cours_id
     AND fc.niveau_id = a.niveau_id
    LEFT JOIN enseignant e ON e.`ID-ENSEIGNANT` = a.enseignant_id
    ORDER BY f.nom, n.id, c.nom, a.annee_scolaire DESC
  ";

  $rows = $conn->query($sql)->fetchAll();
  echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.'], JSON_UNESCAPED_UNICODE);
}
