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

$id = (int)($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? ''); // valider | rejeter
$motifRejet = trim($_POST['rejet_motif'] ?? '');

if ($id <= 0 || !in_array($action, ['valider','rejeter'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

if ($action === 'rejeter' && $motifRejet === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Motif de rejet obligatoire']);
    exit;
}

try {
    if ($action === 'valider') {
        $sql = "UPDATE reservations_salles SET statut='validee', rejet_motif=NULL WHERE id=:id AND statut='en_attente'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
    } else {
        $sql = "UPDATE reservations_salles SET statut='rejetee', rejet_motif=:m WHERE id=:id AND statut='en_attente'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id, ':m' => $motifRejet]);
    }

    if ($stmt->rowCount() === 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Impossible : réservation déjà traitée.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Mise à jour effectuée']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
