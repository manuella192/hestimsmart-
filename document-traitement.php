<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['etudiant_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expirée.']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ---- Données session (demandeur) ----
$etudiantId = (int)($_SESSION['etudiant_id']);
$prenom     = trim($_SESSION['prenom'] ?? '');
$nom        = trim($_SESSION['nom'] ?? '');
$emailSes   = trim($_SESSION['email'] ?? '');

if ($prenom === '' || $nom === '' || $emailSes === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Profil session incomplet (nom/prénom/email).']);
    exit;
}

// ---- Données POST ----
$type = $_POST['type_document'] ?? '';
$autre = $_POST['autre_document'] ?? '';
$commentaire = $_POST['commentaire'] ?? '';

$type = trim($type);
$autre = trim($autre);
$commentaire = trim($commentaire);

// Types autorisés
$allowed = ['homologation','bulletin','certificat_scolarite','autre'];
if (!in_array($type, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Type de document invalide.']);
    exit;
}

if ($type === 'autre' && $autre === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Veuillez préciser le nom du document.']);
    exit;
}

if ($type !== 'autre') {
    $autre = null;
}

if ($commentaire === '') {
    $commentaire = null;
}

try {
    // ---- Insert DB ----
    $sql = "INSERT INTO document_demandes
            (etudiant_id, type_document, autre_document, commentaire, statut, demandeur_nom, demandeur_prenom, demandeur_email)
            VALUES
            (:etudiant_id, :type_document, :autre_document, :commentaire, 'non_traite', :nom, :prenom, :email)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':etudiant_id'   => $etudiantId,
        ':type_document' => $type,
        ':autre_document'=> $autre,
        ':commentaire'   => $commentaire,
        ':nom'           => $nom,
        ':prenom'        => $prenom,
        ':email'         => $emailSes,
    ]);

    $demandeId = (int)$conn->lastInsertId();

    // ---- Envoi email admin (PHPMailer) ----
    // IMPORTANT: tu dois renseigner SMTP_USER/SMTP_PASS (mot de passe d'application Gmail)
    $ADMIN_EMAIL = "manuellamht@gmail.com";

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
        $mail->addAddress($ADMIN_EMAIL);

        $mail->Subject = "Nouvelle demande de document";

        $typeLabel = $type;
        if ($type === 'certificat_scolarite') $typeLabel = 'certificat de scolarité';

        $body = "Nouvelle demande de document\n\n"
              . "Nom/Prénom : {$nom} {$prenom}\n"
              . "Email : {$emailSes}\n"
              . "Type : {$typeLabel}\n";

        if ($type === 'autre') {
            $body .= "Document (Autre) : {$autre}\n";
        }

        if ($commentaire) {
            $body .= "Commentaire : {$commentaire}\n";
        }

        $body .= "Date : " . date('Y-m-d H:i:s') . "\n";

        $mail->Body = $body;

        $mail->send();

    } catch (Exception $e) {
        // On ne bloque pas la demande si le mail échoue : la DB a déjà enregistré
        // Tu peux logger: error_log($mail->ErrorInfo);
    }

    echo json_encode([
        'success' => true,
        'message' => "Demande enregistrée. L’administration a été notifiée."
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur serveur lors de l'enregistrement."]);
    exit;
}
