<?php
session_start();
require_once __DIR__ . '/db.php';

// Variables pour le formulaire et les messages
$error   = '';
$email   = '';
$remember = isset($_POST['remember']) ? 'checked' : '';

// Traitement UNIQUEMENT quand le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            // Requête corrigée : on sélectionne aussi MDP
            $sql = "SELECT id, NOM, PRENOM, EMAIL, MDP
                    FROM admin
                    WHERE EMAIL = :email
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':email' => $email]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && $admin['MDP'] === $password) {
                // Connexion réussie
                $_SESSION['admin_id']     = (int)$admin['id'];
                $_SESSION['admin_nom']    = $admin['NOM'];
                $_SESSION['admin_prenom'] = $admin['PRENOM'];
                $_SESSION['admin_email']  = $admin['EMAIL'];

                header("Location: portail_admin.php");
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Une erreur technique est survenue. Veuillez réessayer plus tard.';
            // error_log("Erreur login admin : " . $e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login Administrateur</title>
  <link rel="stylesheet" href="login-enseignant.css" />  <!-- ou login-admin.css si différent -->
  <style>
    .error-text {
      color: #dc2626;
      font-size: 13px;
      margin: 8px 0 4px 0;
      text-align: left;
      font-weight: 500;
      padding-left: 4px;
    }
  </style>
</head>
<body>
  <div class="page">
    <img src="images/image2.png" alt="admin" class="hero" />

    <main class="right">
      <header class="brand">
        <img src="images/logo_hestim.png" alt="logo" class="logo"/>
      </header>

      <form class="form-wrap" method="POST" action="">
        <div class="icon">🛠️</div>
        <h2>Espace Administrateur</h2>

        <input 
          type="email" 
          name="email" 
          placeholder="Entrez votre mail" 
          value="<?= htmlspecialchars($email) ?>" 
          required 
        />

        <input 
          type="password" 
          name="password" 
          placeholder="Entrez votre mot de passe" 
          required 
        />

        <?php if ($error !== ''): ?>
          <div class="error-text"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="links-row">
          <label>
            <input type="checkbox" name="remember" <?= $remember ?> /> 
            Se rappeler de moi ?
          </label>
          <a href="#">Mot de passe oublié ?</a>
        </div>

        <button class="btn" type="submit">Se connecter</button>
      </form>

      <p style="text-align:center; margin-top:20px;">
        <a href="login-etudiant.php">Espace Étudiant</a> | 
        <a href="login-enseignant.php">Espace Enseignant</a>
      </p>
    </main>
  </div>
</body>
</html>