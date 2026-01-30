<?php
session_start();

// Vérification de session
if (!isset($_SESSION['etudiant_id'])) {
    http_response_code(401);
    echo "<div class='alert-error'>Session expirée. Veuillez vous reconnecter.</div>";
    exit;
}

require_once __DIR__ . "/../db.php";

try {
    $etudiantId = (int)$_SESSION['etudiant_id'];
    
    $sql = "SELECT `ID-ETUDIANT`, `NOM`, `PRENOM`, `EMAIL`
            FROM `etudiant`
            WHERE `ID-ETUDIANT` = :id
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $etudiantId]);
    $etudiant = $stmt->fetch();
    
    if (!$etudiant) {
        echo "<div class='alert-warning'>
                <h2>Profil non trouvé</h2>
                <p>Impossible de charger vos informations. Contactez l'administration.</p>
              </div>";
        exit;
    }
} catch (Exception $e) {
    error_log("Erreur chargement profil: " . $e->getMessage());
    echo "<div class='alert-error'>Erreur technique lors du chargement du profil.</div>";
    exit;
}
?>

<!-- Inclure le CSS spécifique au profil -->
<link rel="stylesheet" href="css/profil.css">

<div class="profil-container">
    <div class="profil-header">
        <h1><i class="fas fa-user-circle"></i> Mon profil HESTIM</h1>
        <p class="subtitle">Informations personnelles de l'étudiant</p>
    </div>

    <div class="profil-card">
        <div class="profil-card-header">
            <h2><i class="fas fa-id-card"></i> Informations personnelles</h2>
        </div>
        
        <div class="profil-grid">
            <div class="profil-info-group">
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-user"></i> Prénom
                    </span>
                    <span class="info-value"><?= htmlspecialchars($etudiant['PRENOM']) ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-users"></i> Nom
                    </span>
                    <span class="info-value"><?= htmlspecialchars($etudiant['NOM']) ?></span>
                </div>
            </div>
            
            <div class="profil-info-group">
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-envelope"></i> Email
                    </span>
                    <span class="info-value"><?= htmlspecialchars($etudiant['EMAIL']) ?></span>
                </div>
            </div>
        </div>
        
        <div class="profil-footer">
            <div class="info-note">
                <i class="fas fa-info-circle"></i>
                <p>
                    Ces informations sont synchronisées avec votre compte institutionnel. 
                    Pour toute modification, contactez le service administratif.
                </p>
            </div>
        </div>
    </div>
</div>