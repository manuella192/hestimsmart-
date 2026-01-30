<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Session expirée']); exit; }
require_once __DIR__ . '/db.php';

try {
  // On prend l'inscription la plus récente (par date_inscription ou id) si besoin.
  // Ici : dernière inscription par date_inscription.
  $sql = "
    SELECT
      e.`ID-ETUDIANT` AS id,
      e.NOM AS nom,
      e.PRENOM AS prenom,
      e.EMAIL AS email,
      e.MDP AS mdp,
      e.date_inscription,
      i.filiere_id,
      f.nom AS filiere_nom,
      i.niveau_id,
      n.libelle AS niveau_libelle,
      i.annee_scolaire,
      i.groupe,
      CONCAT(e.PRENOM,' ',e.NOM) AS nom_complet
    FROM etudiant e
    LEFT JOIN inscriptions i
      ON i.etudiant_id = e.`ID-ETUDIANT`
     AND i.id = (
        SELECT i2.id FROM inscriptions i2
        WHERE i2.etudiant_id = e.`ID-ETUDIANT`
        ORDER BY i2.id DESC
        LIMIT 1
     )
    LEFT JOIN filieres f ON f.id = i.filiere_id
    LEFT JOIN niveaux n ON n.id = i.niveau_id
    ORDER BY e.`ID-ETUDIANT` DESC
  ";
  $rows = $conn->query($sql)->fetchAll();
  echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.']);
}
