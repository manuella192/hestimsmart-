<?php
// ===========================
// FICHIER: enseignant_presence_submit.php
// ===========================
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['enseignant_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez vous reconnecter.']);
    exit;
}

$enseignantId = (int)$_SESSION['enseignant_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Récupération JSON
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload JSON invalide.']);
    exit;
}

$affectationId = isset($payload['affectation_id']) ? (int)$payload['affectation_id'] : 0;
$coursId       = isset($payload['cours_id']) && $payload['cours_id'] !== null ? (int)$payload['cours_id'] : 0;
$anneeScolaire = isset($payload['annee_scolaire']) ? trim((string)$payload['annee_scolaire']) : '';
$presenceAt    = isset($payload['presence_at']) ? trim((string)$payload['presence_at']) : '';
$rows          = isset($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : [];

if ($affectationId <= 0 || $anneeScolaire === '' || $presenceAt === '' || empty($rows)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Champs requis manquants (affectation, année scolaire, séance, lignes).']);
    exit;
}

// presence_at arrive "YYYY-MM-DDTHH:MM" (input datetime-local) -> convertir en "YYYY-MM-DD HH:MM:SS"
$presenceAtSql = str_replace('T', ' ', $presenceAt);
if (strlen($presenceAtSql) === 16) $presenceAtSql .= ':00';

// Validation format datetime simple
$dt = DateTime::createFromFormat('Y-m-d H:i:s', $presenceAtSql);
if (!$dt || $dt->format('Y-m-d H:i:s') !== $presenceAtSql) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Date/heure de séance invalide.']);
    exit;
}

// Charger la config DB (adapte le chemin/nom si besoin)
require_once __DIR__ . '/db.php'; // Doit définir $conn = new PDO(...)

try {
    // 1) Vérifier que l'affectation appartient à l'enseignant connecté + récupérer cours_id si non fourni
    $stmt = $conn->prepare("
        SELECT id, enseignant_id, cours_id
        FROM enseignant_affectations
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $affectationId]);
    $aff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aff) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Affectation introuvable."]);
        exit;
    }
    if ((int)$aff['enseignant_id'] !== $enseignantId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => "Accès refusé : cette affectation ne vous appartient pas."]);
        exit;
    }

    $coursIdResolved = $coursId > 0 ? $coursId : (int)$aff['cours_id'];
    if ($coursIdResolved <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Impossible de déterminer le cours lié à l'affectation."]);
        exit;
    }

    // 2) Normaliser / valider les lignes
    $clean = [];
    foreach ($rows as $r) {
        $etudiantId = isset($r['etudiant_id']) ? (int)$r['etudiant_id'] : 0;
        $present    = isset($r['present']) ? (int)$r['present'] : 0;

        if ($etudiantId <= 0) continue;
        $present = ($present === 1) ? 1 : 0;
        $clean[] = ['etudiant_id' => $etudiantId, 'present' => $present];
    }

    if (empty($clean)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aucune ligne valide à enregistrer.']);
        exit;
    }

    // 3) (Optionnel mais recommandé) vérifier que les étudiants sont bien inscrits dans le contexte filière/niveau/année/groupe de l'affectation
    // On récupère filiere_id/niveau_id/groupe de l'affectation
    $stmt = $conn->prepare("
        SELECT filiere_id, niveau_id, groupe
        FROM enseignant_affectations
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $affectationId]);
    $ctx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ctx) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Contexte d'affectation introuvable."]);
        exit;
    }

    $filiereId = (int)$ctx['filiere_id'];
    $niveauId  = (int)$ctx['niveau_id'];
    $groupe    = $ctx['groupe']; // peut être NULL

    // Construire la liste des IDs pour validation
    $ids = array_values(array_unique(array_map(fn($x) => (int)$x['etudiant_id'], $clean)));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Si groupe NULL côté affectation => on accepte tous les groupes de ce contexte (groupe élargi)
    // Sinon on exige match exact.
    if ($groupe === null || $groupe === '') {
        $sql = "
            SELECT etudiant_id
            FROM inscriptions
            WHERE annee_scolaire = ?
              AND filiere_id = ?
              AND niveau_id = ?
              AND etudiant_id IN ($placeholders)
        ";
        $params = array_merge([$anneeScolaire, $filiereId, $niveauId], $ids);
    } else {
        $sql = "
            SELECT etudiant_id
            FROM inscriptions
            WHERE annee_scolaire = ?
              AND filiere_id = ?
              AND niveau_id = ?
              AND groupe = ?
              AND etudiant_id IN ($placeholders)
        ";
        $params = array_merge([$anneeScolaire, $filiereId, $niveauId, $groupe], $ids);
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $allowed = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $allowedSet = array_flip(array_map('strval', $allowed));

    $final = [];
    foreach ($clean as $line) {
        if (isset($allowedSet[(string)$line['etudiant_id']])) {
            $final[] = $line;
        }
    }

    if (empty($final)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Aucun étudiant n'est inscrit dans le contexte de cette affectation (filière/niveau/année/groupe)."]);
        exit;
    }

    // 4) Upsert (insert or update) en transaction
    $conn->beginTransaction();

    $sqlIns = "
        INSERT INTO presences (enseignant_id, affectation_id, cours_id, etudiant_id, annee_scolaire, presence_at, present)
        VALUES (:enseignant_id, :affectation_id, :cours_id, :etudiant_id, :annee_scolaire, :presence_at, :present)
        ON DUPLICATE KEY UPDATE
            present = VALUES(present),
            updated_at = CURRENT_TIMESTAMP
    ";
    $stmtIns = $conn->prepare($sqlIns);

    foreach ($final as $line) {
        $stmtIns->execute([
            ':enseignant_id' => $enseignantId,
            ':affectation_id' => $affectationId,
            ':cours_id' => $coursIdResolved,
            ':etudiant_id' => (int)$line['etudiant_id'],
            ':annee_scolaire' => $anneeScolaire,
            ':presence_at' => $presenceAtSql,
            ':present' => (int)$line['present'],
        ]);
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Présence enregistrée avec succès.',
        'count' => count($final),
        'presence_at' => $presenceAtSql
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur serveur : " . $e->getMessage()]);
    exit;
}
