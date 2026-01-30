<?php
// ===========================
// FICHIER: enseignant_etudiants_list.php
// Renvoie les étudiants de l'affectation sélectionnée (cours/filière/niveau/année/groupe)
// ===========================
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['enseignant_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expirée.']);
    exit;
}

require_once __DIR__ . '/db.php'; // doit fournir $conn (PDO)

$enseignantId = (int)$_SESSION['enseignant_id'];
$affectationId = isset($_GET['affectation_id']) ? (int)$_GET['affectation_id'] : 0;
$annee = $_GET['annee_scolaire'] ?? ($_SESSION['annee_scolaire'] ?? '2025-2026');

if ($affectationId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Paramètre affectation_id manquant."]);
    exit;
}

try {
    // Sécuriser: vérifier que l'affectation appartient bien à l'enseignant
    $check = $conn->prepare("
        SELECT a.*
        FROM enseignant_affectations a
        WHERE a.id = :id AND a.enseignant_id = :ens AND a.annee_scolaire = :annee
        LIMIT 1
    ");
    $check->execute([':id' => $affectationId, ':ens' => $enseignantId, ':annee' => $annee]);
    $aff = $check->fetch(PDO::FETCH_ASSOC);

    if (!$aff) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Affectation introuvable (ou non autorisée)."]);
        exit;
    }

    $sql = "
        SELECT
            e.`ID-ETUDIANT` AS etudiant_id,
            CONCAT(e.PRENOM, ' ', e.NOM) AS nom_complet,
            e.EMAIL AS email,
            c.nom AS cours,
            f.nom AS filiere,
            n.libelle AS niveau,
            i.groupe AS groupe
        FROM enseignant_affectations a
        JOIN cours c ON c.id = a.cours_id
        JOIN filieres f ON f.id = a.filiere_id
        JOIN niveaux n ON n.id = a.niveau_id
        JOIN inscriptions i
          ON i.filiere_id = a.filiere_id
         AND i.niveau_id = a.niveau_id
         AND i.annee_scolaire = a.annee_scolaire
         AND (a.groupe IS NULL OR a.groupe = i.groupe)
        JOIN etudiant e ON e.`ID-ETUDIANT` = i.etudiant_id
        WHERE a.id = :aff_id
          AND a.enseignant_id = :ens_id
          AND a.annee_scolaire = :annee
        ORDER BY e.NOM, e.PRENOM
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':aff_id' => $affectationId,
        ':ens_id' => $enseignantId,
        ':annee'  => $annee
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur serveur."], JSON_UNESCAPED_UNICODE);
}
