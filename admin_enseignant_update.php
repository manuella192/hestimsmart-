<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Session expirée']); exit; }
require_once __DIR__ . '/db.php';

function bad($m, $code=400){ http_response_code($code); echo json_encode(['success'=>false,'message'=>$m], JSON_UNESCAPED_UNICODE); exit; }

$id     = (int)($_POST['id'] ?? 0);
$nom    = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$email  = trim($_POST['email'] ?? '');
$mdp    = trim($_POST['mdp'] ?? '');

if ($id<=0) bad("ID enseignant invalide.");
if ($nom==='' || $prenom==='' || $email==='' || $mdp==='') bad("Champs enseignant obligatoires manquants.");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) bad("Email invalide.");

try {
  // vérifier existence
  $st = $conn->prepare("SELECT 1 FROM enseignant WHERE `ID-ENSEIGNANT`=? LIMIT 1");
  $st->execute([$id]);
  if (!$st->fetchColumn()) bad("Enseignant introuvable.", 404);

  // éviter doublon email sur un autre id
  $st = $conn->prepare("SELECT 1 FROM enseignant WHERE EMAIL=? AND `ID-ENSEIGNANT`<>? LIMIT 1");
  $st->execute([$email, $id]);
  if ($st->fetchColumn()) bad("Cet email est déjà utilisé par un autre enseignant.");

  $st = $conn->prepare("UPDATE enseignant SET NOM=?, PRENOM=?, EMAIL=?, MDP=? WHERE `ID-ENSEIGNANT`=?");
  $st->execute([$nom, $prenom, $email, $mdp, $id]);

  echo json_encode(['success'=>true,'message'=>"Enseignant mis à jour."], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>"Erreur serveur (mise à jour)."], JSON_UNESCAPED_UNICODE);
}
