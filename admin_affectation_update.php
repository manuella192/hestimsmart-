<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Session expirée']); exit; }
require_once __DIR__ . '/db.php';

function bad($m, $code=400){ http_response_code($code); echo json_encode(['success'=>false,'message'=>$m], JSON_UNESCAPED_UNICODE); exit; }

$id = (int)($_POST['id'] ?? 0);
$enseignantId = (int)($_POST['enseignant_id'] ?? 0);
$annee = trim($_POST['annee_scolaire'] ?? '');
$groupe = trim($_POST['groupe'] ?? '');

if ($id<=0) bad("ID affectation invalide.");
if ($enseignantId<=0) bad("Enseignant invalide.");
if ($annee==='') bad("Année scolaire obligatoire.");

try {
  $st = $conn->prepare("UPDATE enseignant_affectations SET enseignant_id=?, annee_scolaire=?, groupe=? WHERE id=?");
  $st->execute([$enseignantId, $annee, ($groupe!==''?$groupe:null), $id]);

  echo json_encode(['success'=>true,'message'=>"Affectation mise à jour."], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.'], JSON_UNESCAPED_UNICODE);
}
