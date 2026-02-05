<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Session admin expirée']);
  exit;
}
require_once __DIR__ . '/db.php';

try {
  // Filieres
  $filieres = $conn->query("SELECT id, code, nom FROM filieres ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

  // Niveaux
  $niveaux = $conn->query("SELECT id, libelle FROM niveaux ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

  // Salles
  $salles = $conn->query("
    SELECT id, batiment, nom, type, etage, taille, capacite
    FROM salles
    ORDER BY batiment, etage, type, nom
  ")->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'success'=>true,
    'data'=>[
      'filieres'=>$filieres,
      'niveaux'=>$niveaux,
      'salles'=>$salles
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.']);
}
