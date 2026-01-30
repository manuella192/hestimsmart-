<?php
// 7) admin_reservation_validate.php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Session expirée']); exit; }

require_once __DIR__ . '/db.php';

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID invalide']); exit; }

try {
    $st = $conn->prepare("UPDATE reservations_salles SET statut='validee', rejet_motif=NULL WHERE id=? AND statut='en_attente'");
    $st->execute([$id]);

    if ($st->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>"Impossible de valider (déjà traitée ou inexistante)."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success'=>true,'message'=>'Réservation validée.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Erreur serveur.'], JSON_UNESCAPED_UNICODE);
}
