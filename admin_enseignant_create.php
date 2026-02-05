<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Session expirée']); exit; }
require_once __DIR__ . '/db.php';

function bad($m, $code=400){ http_response_code($code); echo json_encode(['success'=>false,'message'=>$m], JSON_UNESCAPED_UNICODE); exit; }

$nom    = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$email  = trim($_POST['email'] ?? '');
$mdp    = trim($_POST['mdp'] ?? '');

if ($nom==='' || $prenom==='' || $email==='' || $mdp==='') bad("Champs enseignant obligatoires manquants.");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) bad("Email invalide.");

try {
  // éviter doublons
  $st = $conn->prepare("SELECT 1 FROM enseignant WHERE EMAIL=? LIMIT 1");
  $st->execute([$email]);
  if ($st->fetchColumn()) bad("Cet email enseignant existe déjà.");

  $st = $conn->prepare("INSERT INTO enseignant (NOM, PRENOM, EMAIL, MDP) VALUES (?,?,?,?)");
  $st->execute([$nom, $prenom, $email, $mdp]);

  echo json_encode(['success'=>true,'message'=>"Enseignant ajouté."], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
      'success'=>false,
      'message'=>"Erreur serveur (création): ".$e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
  }
