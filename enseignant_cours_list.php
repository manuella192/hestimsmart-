<?php
// ===========================
// FICHIER: enseignant_cours_list.php
// Renvoie les affectations de l'enseignant (pour remplir le select)
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
$annee = $_GET['annee_scolaire'] ?? ($_SESSION['annee_scolaire'] ?? '2025-2026');

try {
    $sql = "
        SELECT
            a.id AS affectation_id,
            a.annee_scolaire,
            a.groupe,
            c.id AS cours_id,
            c.nom AS cours_nom,
            f.id AS filiere_id,
            f.nom AS filiere_nom,
            n.id AS niveau_id,
            n.libelle AS niveau_libelle
        FROM enseignant_affectations a
        JOIN cours c ON c.id = a.cours_id
        JOIN filieres f ON f.id = a.filiere_id
        JOIN niveaux n ON n.id = a.niveau_id
        WHERE a.enseignant_id = :ens
          AND a.annee_scolaire = :annee
        ORDER BY f.nom, n.id, c.nom, a.groupe
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':ens' => $enseignantId, ':annee' => $annee]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur serveur."], JSON_UNESCAPED_UNICODE);
}
