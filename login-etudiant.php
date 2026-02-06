<?php
session_start();
require_once __DIR__ . '/db.php';

$error   = '';
$email   = '';
$remember = isset($_POST['remember']) ? 'checked' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $sql = "SELECT `ID-ETUDIANT`, `NOM`, `PRENOM`, `EMAIL`
                    FROM `etudiant`
                    WHERE `EMAIL` = :email AND `MDP` = :mdp
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':email' => $email,
                ':mdp'   => $password
            ]);

            $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($etudiant) {
                $_SESSION['etudiant_id']   = $etudiant['ID-ETUDIANT'];
                $_SESSION['email']         = $etudiant['EMAIL'];
                $_SESSION['nom']           = $etudiant['NOM'];
                $_SESSION['prenom']        = $etudiant['PRENOM'];

                header("Location: index.php");
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Une erreur technique est survenue. Veuillez réessayer plus tard.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login Étudiant</title>
  <link rel="stylesheet" href="login-etudiant.css" />
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
    <img src="images/image1.png" alt="student" class="hero" />
    
    <main class="right">
      <header class="brand">
        <img src="images/logo_hestim.png" alt="logo" class="logo"/>
      </header>

      <form class="form-wrap" method="POST" action="">
        <div class="icon">🎓</div>
        <h2>Espace Étudiant</h2>
        
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

      <p>Partie réservée : 
        <a href="login-enseignant.php">enseignants</a> / 
        <a href="login-admin.php">administration</a>
      </p>
    </main>
  </div>
</body>
</html>