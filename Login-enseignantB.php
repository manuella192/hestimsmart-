<?php
session_start();
require_once __DIR__ . '/db.php';

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$email = trim($email);
$password = trim($password);

if ($email === '' || $password === '') {
    echo "<p>Veuillez remplir tous les champs. <a href='login-enseignant.html'>Réessayer</a></p>";
    exit;
}

try {
    // On lit dans la table enseignant pour récupérer NOM + PRENOM
    $sql = "SELECT `ID-ENSEIGNANT`, `NOM`, `PRENOM`, `EMAIL`
            FROM `enseignant`
            WHERE `EMAIL` = :email AND `MDP` = :mdp
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':mdp'   => $password
    ]);

    $ens = $stmt->fetch();

    if ($ens) {
        $_SESSION['enseignant_id']    = $ens['ID-ENSEIGNANT'];
        $_SESSION['enseignant_email'] = $ens['EMAIL'];
        $_SESSION['enseignant_nom']   = $ens['NOM'];
        $_SESSION['enseignant_prenom']= $ens['PRENOM'];

        header("Location: portail_enseignant.php");
        exit;
    } else {
        echo "<p>Identifiants incorrects. <a href='login-enseignant.html'>Réessayer</a></p>";
        exit;
    }

} catch (PDOException $e) {
    echo "<p>Une erreur est survenue. <a href='login-enseignant.html'>Retour</a></p>";
    // error_log($e->getMessage());
    exit;
}
