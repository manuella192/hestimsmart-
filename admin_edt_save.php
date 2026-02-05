<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Session admin expirée']);
  exit;
}

require_once __DIR__ . '/db.php';

function bad($msg, $code=400) {
  http_response_code($code);
  echo json_encode(['success'=>false,'message'=>$msg]);
  exit;
}

$payloadRaw = $_POST['payload'] ?? '';
if ($payloadRaw === '') bad("payload manquant.");

$payload = json_decode($payloadRaw, true);
if (!is_array($payload)) bad("payload JSON invalide.");

$week_id = (int)($payload['week_id'] ?? 0);

$filiere_id = (int)($payload['filiere_id'] ?? 0);
$niveau_id  = (int)($payload['niveau_id'] ?? 0);
$annee      = trim((string)($payload['annee_scolaire'] ?? ''));

$mois       = (int)($payload['mois'] ?? 0);
$dateDebut  = trim((string)($payload['date_debut'] ?? ''));
$dateFin    = trim((string)($payload['date_fin'] ?? ''));
$label      = trim((string)($payload['label'] ?? ''));

$sessions   = $payload['sessions'] ?? null;

// sync=1 => supprimer les séances qui ne sont pas dans payload
$sync = (int)($_POST['sync'] ?? 0) === 1;

if ($filiere_id<=0) bad("filiere_id invalide.");
if ($niveau_id<=0) bad("niveau_id invalide.");
if ($annee==='') bad("annee_scolaire obligatoire.");
if ($mois<1 || $mois>12) bad("mois invalide.");
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) bad("date_debut invalide.");
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin)) bad("date_fin invalide.");
if (!is_array($sessions)) bad("sessions doit être un tableau.");

$allowedSlots = ['M1','M2','A1','A2'];

try {
  $conn->beginTransaction();

  // ✅ groupe = NULL (on ne gère pas de groupe)
  $groupe = null;

  // 1) créer / update semaine
  if ($week_id > 0) {
    $stmt = $conn->prepare("SELECT id FROM edt_weeks WHERE id=:id LIMIT 1");
    $stmt->execute([':id'=>$week_id]);
    $w = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$w) bad("Semaine introuvable.", 404);

    $upd = $conn->prepare("
      UPDATE edt_weeks
      SET filiere_id=:f, niveau_id=:n, annee_scolaire=:a, groupe=:g,
          mois=:m, date_debut=:dd, date_fin=:df, label=:label
      WHERE id=:id
    ");
    $upd->execute([
      ':f'=>$filiere_id,
      ':n'=>$niveau_id,
      ':a'=>$annee,
      ':g'=>$groupe,
      ':m'=>$mois,
      ':dd'=>$dateDebut,
      ':df'=>$dateFin,
      ':label'=>($label!=='' ? $label : null),
      ':id'=>$week_id
    ]);

  } else {
    // ✅ upsert "sans groupe" => groupe IS NULL
    $stmt = $conn->prepare("
      SELECT id FROM edt_weeks
      WHERE filiere_id=:f AND niveau_id=:n AND annee_scolaire=:a
        AND groupe IS NULL
        AND date_debut=:dd AND date_fin=:df
      LIMIT 1
    ");
    $stmt->execute([
      ':f'=>$filiere_id,
      ':n'=>$niveau_id,
      ':a'=>$annee,
      ':dd'=>$dateDebut,
      ':df'=>$dateFin
    ]);
    $w = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($w) {
      $week_id = (int)$w['id'];
      $upd = $conn->prepare("UPDATE edt_weeks SET label=:label, mois=:m WHERE id=:id");
      $upd->execute([
        ':label'=>($label!=='' ? $label : null),
        ':m'=>$mois,
        ':id'=>$week_id
      ]);
    } else {
      $ins = $conn->prepare("
        INSERT INTO edt_weeks (filiere_id, niveau_id, annee_scolaire, groupe, mois, date_debut, date_fin, label)
        VALUES (:f,:n,:a,:g,:m,:dd,:df,:label)
      ");
      $ins->execute([
        ':f'=>$filiere_id,
        ':n'=>$niveau_id,
        ':a'=>$annee,
        ':g'=>$groupe,
        ':m'=>$mois,
        ':dd'=>$dateDebut,
        ':df'=>$dateFin,
        ':label'=>($label!=='' ? $label : null)
      ]);
      $week_id = (int)$conn->lastInsertId();
    }
  }

  // 2) upsert séances
  $upsert = $conn->prepare("
    INSERT INTO edt_sessions (week_id, jour_date, slot, affectation_id, salle_id, notes)
    VALUES (:week_id, :jour_date, :slot, :aff, :salle, :notes)
    ON DUPLICATE KEY UPDATE
      affectation_id=VALUES(affectation_id),
      salle_id=VALUES(salle_id),
      notes=VALUES(notes),
      updated_at=NOW()
  ");

  $kept = [];
  $count = 0;

  foreach ($sessions as $i => $s) {
    if (!is_array($s)) continue;

    $jour_date = trim((string)($s['jour_date'] ?? ''));
    $slot      = trim((string)($s['slot'] ?? ''));
    $aff_id    = (int)($s['affectation_id'] ?? 0);
    $salle_id  = (int)($s['salle_id'] ?? 0);
    $notes     = trim((string)($s['notes'] ?? ''));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $jour_date)) bad("sessions[$i].jour_date invalide.");
    if (!in_array($slot, $allowedSlots, true)) bad("sessions[$i].slot invalide.");
    if ($aff_id <= 0) bad("sessions[$i].affectation_id invalide.");
    if ($salle_id <= 0) bad("sessions[$i].salle_id invalide.");

    $upsert->execute([
      ':week_id'=>$week_id,
      ':jour_date'=>$jour_date,
      ':slot'=>$slot,
      ':aff'=>$aff_id,
      ':salle'=>$salle_id,
      ':notes'=>($notes!=='' ? $notes : null)
    ]);

    $kept[] = $jour_date . '|' . $slot;
    $count++;
  }

  // 3) sync delete
  if ($sync) {
    $existing = $conn->prepare("SELECT jour_date, slot FROM edt_sessions WHERE week_id=:wid");
    $existing->execute([':wid'=>$week_id]);
    $rows = $existing->fetchAll(PDO::FETCH_ASSOC);

    $keptSet = array_flip($kept);

    foreach ($rows as $r) {
      $k = $r['jour_date'].'|'.$r['slot'];
      if (!isset($keptSet[$k])) {
        $del = $conn->prepare("DELETE FROM edt_sessions WHERE week_id=:wid AND jour_date=:jd AND slot=:sl");
        $del->execute([':wid'=>$week_id, ':jd'=>$r['jour_date'], ':sl'=>$r['slot']]);
      }
    }
  }

  $conn->commit();

  echo json_encode([
    'success'=>true,
    'message'=>"EDT enregistré. ($count séance(s))",
    'week_id'=>$week_id
  ]);

} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Erreur serveur.','debug'=>$e->getMessage()]);
}
