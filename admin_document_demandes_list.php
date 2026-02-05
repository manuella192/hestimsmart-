<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Session admin expirée']);
  exit;
}

try {
  // On joint etudiant pour garantir qu'on a le bon email si besoin
  $sql = "
    SELECT
      d.id,
      d.etudiant_id,
      d.type_document,
      d.autre_document,
      d.commentaire,
      d.motif_refus,
      d.statut,
      d.date_demande,
      d.date_traitement,

      CONCAT_WS(' ', e.PRENOM, e.NOM) AS etudiant_nom,
      e.EMAIL AS etudiant_email

    FROM document_demandes d
    LEFT JOIN etudiant e
      ON CAST(e.`ID-ETUDIANT` AS UNSIGNED) = CAST(TRIM(d.etudiant_id) AS UNSIGNED)
    ORDER BY d.date_demande DESC
    LIMIT 300
  ";

  $stmt = $conn->prepare($sql);
  $stmt->execute();

  echo json_encode([
    'success' => true,
    'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
