<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['enseignant_id']) && !isset($_SESSION['admin_id']) && (($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $stmt = $conn->query("SELECT id, batiment, nom, type, etage, taille, capacite FROM salles ORDER BY batiment, type, nom");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
