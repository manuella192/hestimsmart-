<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['enseignant_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session enseignant expirée']);
    exit;
}

$enseignantId = (int)$_SESSION['enseignant_id'];

$salleId   = (int)($_POST['salle_id'] ?? 0);
$coursNom  = trim($_POST['cours_nom'] ?? '');
$niveau    = trim($_POST['niveau'] ?? '');
$effectif  = (int)($_POST['effectif'] ?? 0);
$motif     = trim($_POST['motif'] ?? 'cours');
$motifAutre= trim($_POST['motif_autre'] ?? '');

$dateDebut = trim($_POST['date_debut'] ?? ''); // YYYY-MM-DDTHH:MM (from datetime-local)
$dateFin   = trim($_POST['date_fin'] ?? '');

$allowedMotifs = ['cours','td','tp','reunion','examen','autre'];
if ($salleId <= 0 || $coursNom === '' || $niveau === '' || $effectif <= 0 || !in_array($motif, $allowedMotifs, true) || $dateDebut === '' || $dateFin === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Champs invalides ou manquants.']);
    exit;
}

if ($motif === 'autre' && $motifAutre === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Veuillez préciser le motif (Autre).']);
    exit;
}
if ($motif !== 'autre') $motifAutre = null;

// Convert datetime-local -> "YYYY-MM-DD HH:MM:SS"
$debut = str_replace('T', ' ', $dateDebut) . ':00';
$fin   = str_replace('T', ' ', $dateFin) . ':00';

if (strtotime($fin) <= strtotime($debut)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La date/heure de fin doit être après le début.']);
    exit;
}

try {
    // Check conflit (même salle + overlap + statut != rejetee/annulee)
    $confSql = "
      SELECT COUNT(*) AS c
      FROM reservations_salles
      WHERE salle_id = :sid
        AND statut IN ('en_attente','validee')
        AND (:debut < date_fin AND :fin > date_debut)
    ";
    $conf = $conn->prepare($confSql);
    $conf->execute([':sid' => $salleId, ':debut' => $debut, ':fin' => $fin]);
    $count = (int)$conf->fetchColumn();

    if ($count > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Conflit : cette salle est déjà réservée sur ce créneau.']);
        exit;
    }

    $sql = "
      INSERT INTO reservations_salles
      (enseignant_id, salle_id, cours_nom, niveau, effectif, motif, motif_autre, date_debut, date_fin, statut)
      VALUES
      (:eid, :sid, :cours, :niveau, :effectif, :motif, :motif_autre, :debut, :fin, 'en_attente')
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':eid' => $enseignantId,
        ':sid' => $salleId,
        ':cours' => $coursNom,
        ':niveau' => $niveau,
        ':effectif' => $effectif,
        ':motif' => $motif,
        ':motif_autre' => $motifAutre,
        ':debut' => $debut,
        ':fin' => $fin,
    ]);

    echo json_encode(['success' => true, 'message' => 'Demande envoyée. Statut : En attente.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
