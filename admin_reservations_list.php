<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$isAdmin = (($_SESSION['role'] ?? '') === 'admin') || isset($_SESSION['admin_id']);
if (!$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $sql = "
      SELECT r.id, r.cours_nom, r.niveau, r.effectif, r.motif, r.motif_autre,
             r.date_debut, r.date_fin, r.statut, r.rejet_motif, r.created_at,
             s.batiment, s.nom AS salle_nom,
             e.`NOM` AS ens_nom, e.`PRENOM` AS ens_prenom, e.`EMAIL` AS ens_email
      FROM reservations_salles r
      INNER JOIN salles s ON s.id = r.salle_id
      LEFT JOIN enseignant e ON e.`ID-ENSEIGNANT` = r.enseignant_id
      ORDER BY r.created_at DESC
      LIMIT 500
    ";
    $stmt = $conn->query($sql);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
