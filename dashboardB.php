<?php
// Connexion à la base
$conn = new mysqli("localhost", "root", "", "Hestimsmart");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer l'email transmis par l'URL
$email = $_GET['email'] ?? '';

echo '<section class="banner">';
echo '<h2>Informations de l\'étudiant</h2>';

if ($email) {
    $stmt = $conn->prepare("SELECT nom, prenom, email FROM etudiant WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $etudiant = $result->fetch_assoc();
        echo "<p><strong>Nom :</strong> " . htmlspecialchars($etudiant['nom']) . "</p>";
        echo "<p><strong>Prénom :</strong> " . htmlspecialchars($etudiant['prenom']) . "</p>";
        echo "<p><strong>Email :</strong> " . htmlspecialchars($etudiant['email']) . "</p>";
    } else {
        echo "<p>Aucun étudiant trouvé.</p>";
    }

    $stmt->close();
} else {
    echo "<p>Aucun email reçu.</p>";
}

$conn->close();
echo '</section>';
?>
