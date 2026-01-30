<?php
session_start();
require_once __DIR__ . '/db.php';

// Récupérer les données du formulaire (POST)
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Petite validation basique
$email = trim($email);
$password = trim($password);

if ($email === '' || $password === '') {
    echo "<p>Veuillez remplir tous les champs. <a href='login-etudiant.html'>Réessayer</a></p>";
    exit;
}

try {
    // Requête préparée (anti-injection SQL)
    $sql = "SELECT `ID-ETUDIANT`, `NOM`, `PRENOM`, `EMAIL`
            FROM `etudiant`
            WHERE `EMAIL` = :email AND `MDP` = :mdp
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':mdp'   => $password
    ]);

    $etudiant = $stmt->fetch(); // false si rien trouvé

    if ($etudiant) {
        // Stocker ce dont tu as besoin en session
        $_SESSION['etudiant_id'] = $etudiant['ID-ETUDIANT'];
        $_SESSION['email']       = $etudiant['EMAIL'];
        $_SESSION['nom']         = $etudiant['NOM'];
        $_SESSION['prenom']      = $etudiant['PRENOM'];

        // Redirection
        header("Location: index.php");
        exit;
    } else {
        echo "<p>Identifiants incorrects. <a href='login-etudiant.html'>Réessayer</a></p>";
        exit;
    }

} catch (PDOException $e) {
    // Message générique côté utilisateur
    echo "<p>Une erreur est survenue. <a href='login-etudiant.html'>Retour</a></p>";

    // Optionnel: log serveur (recommandé)
    // error_log($e->getMessage());
    exit;
}
