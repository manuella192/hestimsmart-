<?php
session_start();

// Connexion à la base de données Hestimsmart
$host = "localhost";      
$user = "root";          
$pass = "";               // ton mot de passe MySQL (vide par défaut en XAMPP/WAMP)
$dbname = "Hestimsmart";  // nom de ta base

$conn = new mysqli($host, $user, $pass, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Récupérer les données du formulaire
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Préparer la requête pour éviter les injections SQL
$stmt = $conn->prepare("SELECT * FROM liste_enseignant WHERE email = ? AND mdp = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

// Vérifier si un utilisateur correspond
if ($result->num_rows > 0) {
    $_SESSION['enseignant'] = $email;
    header("Location: portail_enseignant.html"); // ✅ redirection si OK
    exit;
} else {
    echo "<p>Identifiants incorrects. <a href='login-enseignant.html'>Réessayer</a></p>";
}

$stmt->close();
$conn->close();
?>
