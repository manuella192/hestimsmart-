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
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

try {
    // L'enseignant ne peut annuler que SES réservations en attente
    $sql = "UPDATE reservations_salles
            SET statut = 'annulee'
            WHERE id = :id AND enseignant_id = :eid AND statut = 'en_attente'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id, ':eid' => $enseignantId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Action non autorisée ou réservation non annulable.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Demande annulée.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
