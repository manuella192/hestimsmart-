<?php
session_start();
if (!isset($_SESSION['etudiant_id'])) {
    http_response_code(401);
    echo "<div class='alert-error'>Session expirée. Veuillez vous reconnecter.</div>";
    exit;
}

$prenom = $_SESSION['prenom'] ?? '';
$nom    = $_SESSION['nom'] ?? '';
$email  = $_SESSION['email'] ?? '';
?>

<link rel="stylesheet" href="css/supports.css">

<h2 style="margin: 28px;">Supports</h2>

<section class="supports-container">
    <h3><i class="fas fa-file-alt"></i> Demande de document</h3>

    <form id="docRequestForm" class="doc-form">
        <div class="form-group">
            <label for="type_document">Type de document</label>
            <select id="type_document" name="type_document" class="form-control" required>
                <option value="">-- Sélectionner --</option>
                <option value="homologation">Homologation</option>
                <option value="bulletin">Bulletin</option>
                <option value="certificat_scolarite">Certificat de scolarité</option>
                <option value="autre">Autre</option>
            </select>
        </div>

        <div id="autreWrap" class="form-group" style="display:none;">
            <label for="autre_document">Nom du document demandé</label>
            <input
                type="text"
                id="autre_document"
                name="autre_document"
                class="form-control"
                placeholder="Ex: Attestation de stage, Relevé de notes..."
            >
        </div>

        <div class="form-group">
            <label for="commentaire">Commentaire (optionnel)</label>
            <textarea
                id="commentaire"
                name="commentaire"
                rows="3"
                class="form-control"
                placeholder="Ex: urgent, pour dossier, précision..."
            ></textarea>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">
            <i class="fas fa-paper-plane"></i> Envoyer la demande
        </button>

        <div id="loadingIndicator" class="loading-indicator" style="display: none;">
            <div class="loading-spinner"></div>
            <span>Envoi en cours...</span>
        </div>

        <div id="docRequestMsg" class="form-message"></div>
    </form>
</section>

<script>
  (function() {
    const typeSelect = document.getElementById('type_document');
    const autreWrap  = document.getElementById('autreWrap');
    const autreInput = document.getElementById('autre_document');
    const form       = document.getElementById('docRequestForm');
    const submitBtn  = document.getElementById('submitBtn');
    const loading    = document.getElementById('loadingIndicator');
    const msg        = document.getElementById('docRequestMsg');

    typeSelect.addEventListener('change', () => {
      const v = typeSelect.value;
      if (v === 'autre') {
        autreWrap.style.display = 'block';
        autreInput.required = true;
      } else {
        autreWrap.style.display = 'none';
        autreInput.required = false;
        autreInput.value = '';
      }
    });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      // Masquer les messages précédents
      msg.textContent = '';
      msg.style.display = 'none';
      msg.className = 'form-message';
      
      // Afficher l'indicateur de chargement
      loading.style.display = 'flex';
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.7';
      submitBtn.style.cursor = 'not-allowed';

      const formData = new FormData(form);

      try {
        const res = await fetch('document-traitement.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });

        const data = await res.json();

        // Masquer l'indicateur de chargement
        loading.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';

        // Afficher le message
        msg.style.display = 'block';
        
        if (!res.ok || !data.success) {
          msg.className = 'form-message message-error';
          msg.textContent = data.message || "Erreur lors de l'envoi. Veuillez réessayer.";
          return;
        }

        msg.className = 'form-message message-success';
        msg.textContent = data.message || "Demande envoyée avec succès.";

        // Réinitialiser le formulaire
        form.reset();
        autreWrap.style.display = 'none';
        autreInput.required = false;

        // Cacher le message après 5 secondes
        setTimeout(() => {
          msg.style.display = 'none';
        }, 5000);

      } catch (err) {
        // Masquer l'indicateur de chargement en cas d'erreur
        loading.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
        
        // Afficher le message d'erreur
        msg.style.display = 'block';
        msg.className = 'form-message message-error';
        msg.textContent = "Erreur réseau. Veuillez vérifier votre connexion et réessayer.";
        console.error(err);
      }
    });
  })();
</script>