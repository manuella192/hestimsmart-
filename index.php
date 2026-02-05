<?php
session_start();

// Sécurité minimale : si pas connecté => login
if (!isset($_SESSION['etudiant_id'])) {
    header("Location: login-etudiant.php");
    exit;
}

$prenom = $_SESSION['prenom'] ?? '';
$nom    = $_SESSION['nom'] ?? '';
$fullName = trim($prenom . ' ' . $nom);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard HESTIM</title>
  <link rel="stylesheet" href="dashboard.css" />
  <style>
    /* Ajouts minimes pour l'état actif (tu gardes le reste de ton style) */
    .menu li { cursor: pointer; }
    .menu li.active {
      background: #b30000; /* rouge */
      color: #fff;
      border-radius: 8px;
      font-weight: 600;
    }
    /* Optionnel : zone de chargement */
    #sectionContent.is-loading { opacity: .6; pointer-events: none; }
  </style>
</head>
<body>
  <div class="dash">
    <nav class="sidebar">
      <div class="user"><?= htmlspecialchars($fullName !== '' ? $fullName : 'Utilisateur') ?></div>

      <ul class="menu" id="sidebarMenu">
        <li class="active" data-section="profil">Profil</li>
        <li data-section="planning">Planning</li>
        <li data-section="chats">Chats</li>
        <li data-section="supports">Supports</li>
        <li data-section="logout">Déconnexion</li>
      </ul>
    </nav>

    <main class="content">
      <header class="topbar">
        <img src="/images/logo-hestim.png" alt="logo" class="logo" />
      </header>

      <!-- Contenu dynamique (à droite) -->
      <section id="sectionContent"></section>

      <footer class="footer">
        <div class="footer-line"></div>
        <div class="footer-wrapper">
          <div class="footer-col">
              <h4>Nos formations</h4>
              <a href=""><p>Formation initiale</p></a>
              <a href=""><p>Formation Continue</p></a>
          </div>
          <div class="footer-col">
              <h4>Contactez-nous</h4>
              <a href=""><p>+212 670000000</p></a>
              <a href=""><p>+212 522000000</p></a>
              <a href=""><p>contact@hestim.ma</p></a>
          </div>
          <div class="footer-col">
              <h4>HESTIM</h4>
              <a href=""><p>A propos de HESTIM</p></a>
              <a href=""><p>Mentions légales</p></a>
              <div class="social-icons">
                  <a href="#"><i class="fab fa-facebook"></i></a>
                  <a href="#"><i class="fab fa-instagram"></i></a>
                  <a href="#"><i class="fab fa-youtube"></i></a>
                  <a href="#"><i class="fab fa-linkedin"></i></a>
              </div>
          </div>
        </div>
        <div class="footer-line"></div>
        Copyright © 2023 HESTIM
        <div class="footer-bottom"></div>
      </footer>
    </main>
  </div>

<script>
  const routes = {
    profil:   "sections/profil.php",
    planning: "sections/planning.php",
    chats:    "sections/chats.php",     // placeholder
    supports: "sections/supports.php",  // placeholder
    logout:   "deconnexion.php"
  };

  const menu = document.getElementById("sidebarMenu");
  const sectionContent = document.getElementById("sectionContent");

  function setActiveMenu(sectionKey) {
    menu.querySelectorAll("li").forEach(li => li.classList.remove("active"));
    const active = menu.querySelector(`li[data-section="${sectionKey}"]`);
    if (active) active.classList.add("active");
  }

  async function loadSection(sectionKey) {
    if (!routes[sectionKey]) return;

    // Déconnexion : redirection volontaire
    if (sectionKey === "logout") {
      window.location.href = routes.logout;
      return;
    }

    setActiveMenu(sectionKey);
    sectionContent.classList.add("is-loading");

    try {
      const res = await fetch(routes[sectionKey], { credentials: "same-origin" });
      if (!res.ok) throw new Error("HTTP " + res.status);
      const html = await res.text();
      sectionContent.innerHTML = html;

      // Exécuter les scripts éventuels dans le fragment chargé (important pour planning)
      executeInlineScripts(sectionContent);

    } catch (e) {
      sectionContent.innerHTML = `
        <h2>Erreur</h2>
        <p>Impossible de charger la section.</p>
      `;
      console.error(e);
    } finally {
      sectionContent.classList.remove("is-loading");
    }
  }

  // Permet de relancer les <script> injectés dans le HTML chargé
  function executeInlineScripts(container) {
    const scripts = Array.from(container.querySelectorAll("script"));
    scripts.forEach(oldScript => {
      const newScript = document.createElement("script");
      // copie attributs (si besoin)
      for (const attr of oldScript.attributes) newScript.setAttribute(attr.name, attr.value);
      newScript.text = oldScript.textContent;
      oldScript.replaceWith(newScript);
    });
  }

  menu.addEventListener("click", (e) => {
    const li = e.target.closest("li[data-section]");
    if (!li) return;
    const key = li.dataset.section;
    loadSection(key);
  });

  // Page par défaut après connexion : Profil
  loadSection("profil");
</script>
</body>
</html>
