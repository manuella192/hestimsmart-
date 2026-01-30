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

$annee  = trim($_POST['annee_scolaire'] ?? '');
$groupe = trim($_POST['groupe'] ?? '');

$filiereId = $_POST['filiere_id'] ?? '';
$niveauId  = $_POST['niveau_id'] ?? '';
$newFiliereNom = trim($_POST['new_filiere_nom'] ?? '');
$newNiveauLib  = trim($_POST['new_niveau_libelle'] ?? '');

if ($id<=0) bad("ID étudiant invalide.");
if ($nom==='' || $prenom==='' || $email==='' || $mdp==='') bad("Champs étudiant obligatoires manquants.");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) bad("Email invalide.");
if ($annee==='') bad("Année scolaire obligatoire.");

try {
  $conn->beginTransaction();

  // Filière
  if ($filiereId === '__new__') {
    if ($newFiliereNom==='') bad("Nom de la nouvelle filière obligatoire.");
    $code = strtoupper(preg_replace('/[^A-Z0-9]+/i','', substr($newFiliereNom,0,10)));
    if ($code==='') $code = 'FIL'.time();

    $st = $conn->prepare("INSERT INTO filieres(code, nom) VALUES(?,?)");
    $st->execute([$code, $newFiliereNom]);
    $filiereId = (int)$conn->lastInsertId();
  } else {
    $filiereId = (int)$filiereId;
    if ($filiereId<=0) bad("Filière invalide.");
  }

  // Niveau
  if ($niveauId === '__new__') {
    if ($newNiveauLib==='') bad("Libellé du nouveau niveau obligatoire.");
    $st = $conn->prepare("INSERT INTO niveaux(libelle) VALUES(?)");
    $st->execute([$newNiveauLib]);
    $niveauId = (int)$conn->lastInsertId();
  } else {
    $niveauId = (int)$niveauId;
    if ($niveauId<=0) bad("Niveau invalide.");
  }

  // Update étudiant
  $st = $conn->prepare("UPDATE etudiant SET NOM=?, PRENOM=?, EMAIL=?, MDP=? WHERE `ID-ETUDIANT`=?");
  $st->execute([$nom, $prenom, $email, $mdp, $id]);

  // Upsert inscription pour cette année scolaire
  $st = $conn->prepare("
    SELECT id FROM inscriptions WHERE etudiant_id=? AND annee_scolaire=? LIMIT 1
  ");
  $st->execute([$id, $annee]);
  $insc = $st->fetch();

  if ($insc) {
    $st = $conn->prepare("UPDATE inscriptions SET filiere_id=?, niveau_id=?, groupe=? WHERE id=?");
    $st->execute([$filiereId, $niveauId, ($groupe!==''?$groupe:null), $insc['id']]);
  } else {
    $st = $conn->prepare("INSERT INTO inscriptions (etudiant_id, filiere_id, niveau_id, annee_scolaire, groupe) VALUES (?,?,?,?,?)");
    $st->execute([$id, $filiereId, $niveauId, $annee, ($groupe!==''?$groupe:null)]);
  }

  $conn->commit();
  echo json_encode(['success'=>true,'message'=>"Étudiant mis à jour."], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>"Erreur serveur (mise à jour)."], JSON_UNESCAPED_UNICODE);
}
