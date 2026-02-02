<!-- 2) login-admin.html -->
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login Admin</title>
  <link rel="stylesheet" href="login-enseignant.css" />
</head>
<body>
  <div class="page">
    <img src="images/image2.png" alt="admin" class="hero" />

    <main class="right">
      <header class="brand">
        <img src="images/logo_hestim.png" alt="logo" class="logo"/>
      </header>

      <form class="form-wrap" method="POST" action="login-adminB.php">
        <div class="icon">🛠️</div>
        <h2>Espace Administrateur</h2>

        <input type="email" name="email" placeholder="Entrez votre mail" required />
        <input type="password" name="password" placeholder="Entrez votre mot de passe" required />

        <div class="links-row">
          <label><input type="checkbox" name="remember" /> Se rappeler de moi ?</label>
          <a href="#">Mot de passe oublié ?</a>
        </div>

        <button class="btn" type="submit">Se connecter</button>
      </form>
    </main>
  </div>
</body>
</html>
