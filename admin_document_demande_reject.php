<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Session admin expirée']);
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$motif = trim($_POST['motif_refus'] ?? '');

if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'ID invalide.']);
  exit;
}
if ($motif === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Motif obligatoire.']);
  exit;
}

function typeLabel($type, $autre = null) {
  if ($type === 'homologation') return "Homologation";
  if ($type === 'bulletin') return "Bulletin";
  if ($type === 'certificat_scolarite') return "Certificat de scolarité";
  if ($type === 'autre') return $autre ? ("Document : " . $autre) : "Autre document";
  return $type ?: "Document";
}

try {
  // 1) Lire la demande + email étudiant
  $sql = "
    SELECT
      d.*,
      CONCAT_WS(' ', e.PRENOM, e.NOM) AS etudiant_nom,
      e.EMAIL AS etudiant_email
    FROM document_demandes d
    LEFT JOIN etudiant e
      ON CAST(e.`ID-ETUDIANT` AS UNSIGNED) = CAST(TRIM(d.etudiant_id) AS UNSIGNED)
    WHERE d.id = :id
    LIMIT 1
  ";
  $stmt = $conn->prepare($sql);
  $stmt->execute([':id' => $id]);
  $d = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$d) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Demande introuvable.']);
    exit;
  }

  // 2) Update statut
  $upd = "
    UPDATE document_demandes
    SET statut = 'refusee',
        date_traitement = NOW(),
        motif_refus = :motif
    WHERE id = :id
  ";
  $stmt2 = $conn->prepare($upd);
  $stmt2->execute([':id' => $id, ':motif' => $motif]);

  // 3) Email étudiant
  $toEmail = trim($d['etudiant_email'] ?? '');
  $toName  = trim($d['etudiant_nom'] ?? '');

  if ($toEmail !== '') {
    $docLabel = typeLabel($d['type_document'] ?? '', $d['autre_document'] ?? null);

    $mail = new PHPMailer(true);
    try {
        // SMTP Gmail (recommandé)
        $SMTP_HOST = "smtp.gmail.com";
        $SMTP_USER = "dmtwoleu@gmail.com";
        $SMTP_PASS = "ftln gwiz epvs mzko"; 
        $SMTP_PORT = 587;

        $mail->isSMTP();
        $mail->Host       = $SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = $SMTP_USER;
        $mail->Password   = $SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $SMTP_PORT;

      $mail->CharSet = 'UTF-8';
      $mail->setFrom($SMTP_USER, 'HESTIM - Demandes documents');
      $mail->addAddress($toEmail, $toName ?: $toEmail);

      $mail->Subject = "Votre demande de document a été refusée";

      $body = "Bonjour " . ($toName ?: "") . "\n\n"
            . "Votre demande de document a été REFUSÉE.\n"
            . "Document : {$docLabel}\n"
            . "Motif : {$motif}\n\n"
            . "Si besoin, contactez le service de scolarité.\n\n"
            . "Cordialement,\n"
            . "Administration HESTIM";

      $mail->Body = $body;
      $mail->send();
    } catch (Exception $e) {
      // ne bloque pas
    }
  }

  echo json_encode(['success' => true, 'message' => 'Demande refusée.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
