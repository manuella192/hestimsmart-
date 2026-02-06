<?php
session_start();
require_once __DIR__ . '/db.php';

// Variables pour garder les valeurs et gérer le message
$error   = '';
$email   = '';
$remember = isset($_POST['remember']) ? 'checked' : '';

// Traitement UNIQUEMENT si le formulaire est soumis (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $sql = "SELECT `ID-ENSEIGNANT`, `NOM`, `PRENOM`, `EMAIL`
                    FROM `enseignant`
                    WHERE `EMAIL` = :email AND `MDP` = :mdp
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':email' => $email,
                ':mdp'   => $password
            ]);

            $enseignant = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($enseignant) {
                $_SESSION['enseignant_id']    = $enseignant['ID-ENSEIGNANT'];
                $_SESSION['enseignant_email'] = $enseignant['EMAIL'];
                $_SESSION['enseignant_nom']   = $enseignant['NOM'];
                $_SESSION['enseignant_prenom']= $enseignant['PRENOM'];

                header("Location: portail_enseignant.php");
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Une erreur technique est survenue. Veuillez réessayer plus tard.';
            // error_log("Erreur login enseignant : " . $e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login Enseignant</title>
  <link rel="stylesheet" href="login-enseignant.css" />
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
    <img src="images/image2.png" alt="teacher" class="hero" />
    
    <main class="right">
      <header class="brand">
        <img src="images/logo_hestim.png" alt="logo" class="logo"/>
      </header>

      <form class="form-wrap" method="POST" action="">
        <div class="icon">👨‍🏫</div>
        <h2>Espace Enseignant</h2>
        
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
        <a href="login-admin.php">Administration</a>
      </p>
    
    </main>
  </div>
</body>
</html>