<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Session expirée']); exit; }
require_once __DIR__ . '/db.php';

function bad($m, $code=400){ http_response_code($code); echo json_encode(['success'=>false,'message'=>$m], JSON_UNESCAPED_UNICODE); exit; }

$code = trim($_POST['code'] ?? '');
$nom  = trim($_POST['nom'] ?? '');
$desc = trim($_POST['description'] ?? '');

$filiereId = $_POST['filiere_id'] ?? '';
$niveauId  = $_POST['niveau_id'] ?? '';
$newFiliereNom = trim($_POST['new_filiere_nom'] ?? '');
$newNiveauLib  = trim($_POST['new_niveau_libelle'] ?? '');

$semestre = trim($_POST['semestre'] ?? '');
$semestre = ($semestre === '') ? null : (int)$semestre;

$enseignantId = (int)($_POST['enseignant_id'] ?? 0);
$annee = trim($_POST['annee_scolaire'] ?? '');
$groupe = trim($_POST['groupe'] ?? '');

if ($code==='' || $nom==='') bad("Code et nom du cours obligatoires.");
if ($enseignantId<=0) bad("Enseignant obligatoire.");
if ($annee==='') bad("Année scolaire obligatoire.");

try {
  $conn->beginTransaction();

  // Filière
  if ($filiereId === '__new__') {
    if ($newFiliereNom==='') bad("Nom filière obligatoire.");
    $fcode = strtoupper(preg_replace('/[^A-Z0-9]+/i','', substr($newFiliereNom,0,10)));
    if ($fcode==='') $fcode = 'FIL'.time();
    $st = $conn->prepare("INSERT INTO filieres(code, nom) VALUES(?,?)");
    $st->execute([$fcode, $newFiliereNom]);
    $filiereId = (int)$conn->lastInsertId();
  } else {
    $filiereId = (int)$filiereId;
    if ($filiereId<=0) bad("Filière invalide.");
  }

  // Niveau
  if ($niveauId === '__new__') {
    if ($newNiveauLib==='') bad("Libellé niveau obligatoire.");
    $st = $conn->prepare("INSERT INTO niveaux(libelle) VALUES(?)");
    $st->execute([$newNiveauLib]);
    $niveauId = (int)$conn->lastInsertId();
  } else {
    $niveauId = (int)$niveauId;
    if ($niveauId<=0) bad("Niveau invalide.");
  }

  // Cours
  $st = $conn->prepare("INSERT INTO cours(code, nom, description) VALUES(?,?,?)");
  $st->execute([$code, $nom, ($desc!==''?$desc:null)]);
  $coursId = (int)$conn->lastInsertId();

  // filiere_cours
  $st = $conn->prepare("INSERT INTO filiere_cours(filiere_id, cours_id, niveau_id, semestre) VALUES(?,?,?,?)");
  $st->execute([$filiereId, $coursId, $niveauId, $semestre]);

  // enseignant_affectations
  $st = $conn->prepare("
    INSERT INTO enseignant_affectations(enseignant_id, filiere_id, niveau_id, cours_id, annee_scolaire, groupe)
    VALUES(?,?,?,?,?,?)
  ");
  $st->execute([$enseignantId, $filiereId, $niveauId, $coursId, $annee, ($groupe!==''?$groupe:null)]);

  $conn->commit();
  echo json_encode(['success'=>true,'message'=>'Cours créé et affectation enregistrée.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur (création cours).'], JSON_UNESCAPED_UNICODE);
}
