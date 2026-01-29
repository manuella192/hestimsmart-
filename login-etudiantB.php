<?php
session_start();

// Connexion à la base de données Hestimsmart
$host = "localhost";      // ou 127.0.0.1
$user = "root";           // ton utilisateur MySQL (par défaut root en local)
$pass = "";               // ton mot de passe MySQL (vide par défaut en XAMPP/WAMP)
$dbname = "Hestimsmart";  // nom de ta base

$conn = new pdo($host, $user, $pass, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Récupérer les données du formulaire
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Préparer la requête pour éviter les injections SQL
$stmt = $conn->prepare("SELECT * FROM etudiant WHERE email = ? AND mdp = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

// Vérifier si un utilisateur correspond
if ($result->num_rows > 0) {
    $_SESSION['email'] =$stmt['email'];
    
    header("Location: dashboard.html"); // ✅ redirection si OK
    exit;
} else {
    echo "<p>Identifiants inco rrects. <a href='login-etudiant.html'>Réessayer</a></p>";
}
if ($result->num_rows > 0) {
    $etudiant = $result->fetch_assoc();
    


    // ✅ Redirection vers dashboard.php avec l'email en paramètre GET
    
   
 

    
   

    exit;
} else {
    echo "<p>Identifiants incorrects. <a href='login-etudiant.html'>Réessayer</a></p>";
}


$stmt->close();
$conn->close();
?>