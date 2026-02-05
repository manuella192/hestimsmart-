<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.html");
    exit;
}
$adminName = trim(($_SESSION['admin_prenom'] ?? '') . ' ' . ($_SESSION['admin_nom'] ?? '')) ?: 'Administrateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portail Administrateur</title>
    <link rel="stylesheet" href="portails.css">
</head>
<body>

<header class="header">
    <img src="images/logo-hestim.png" class="logo" alt="logo">
    <h1>Bienvenue sur le portail Administrateur : <?= htmlspecialchars($adminName) ?></h1>
    <button class="logout-btn" onclick="location.href='deconnexion.php'">Déconnexion</button>
</header>

<nav class="navbar">
    <button class="nav-btn active" onclick="showMainSection('salles', event)">Salles</button>
    <button class="nav-btn" onclick="showMainSection('gestion', event)">Gestion utilisateurs</button>
    <button class="nav-btn" onclick="showMainSection('cours', event)">Gestion Cours</button>
    <!-- NOUVEAU -->
    <button class="nav-btn" onclick="showMainSection('docs', event)">Demandes documents</button>

    <button class="nav-btn" onclick="showMainSection('edt', event)">Emploi du temps</button>

</nav>

<div id="salles-section">
    <div class="sub-menu">
        <button class="sub-btn active" onclick="showSallesSubSection('reservations', event)">Réservations (Gestion)</button>
        <button class="sub-btn" onclick="showSallesSubSection('salles', event)">Liste des salles</button>
    </div>

    <section class="reservations-container" id="admin-reservations-section">
        <div class="reservations-header">
            <h2>Gestion des Réservations</h2>
            <button class="btn-add-reservation" onclick="loadAdminReservations()">Rafraîchir</button>
        </div>

        <div class="search-filter-bar" style="margin: 10px 0;">
            <select id="resFilterStatut" class="filter-select" style="max-width:220px;">
                <option value="">Tous les statuts</option>
                <option value="en_attente">En attente</option>
                <option value="validee">Validée</option>
                <option value="rejetee">Rejetée</option>
                <option value="annulee">Annulée</option>
            </select>
            <input type="text" id="resSearch" class="search-input" placeholder="Rechercher (enseignant, cours, salle...)">
        </div>

        <div id="adminReservationsMsg" style="margin:10px 0;"></div>
        <div id="admin-reservations-list" class="reservations-list"></div>
    </section>

    <section class="content" id="admin-salles-list-section" style="display:none;">
        <div class="page-header">
            <h2>Liste des salles</h2>
            <div class="search-filter-bar">
                <input type="text" id="salleSearch" class="search-input" placeholder="Rechercher une salle...">
                <button class="btn-add-reservation" onclick="loadAdminSalles()">Rafraîchir</button>
            </div>
            <div id="adminSallesMsg" style="margin-top:10px;"></div>
        </div>

        <div class="students-table-container">
            <table class="students-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bâtiment</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Étage</th>
                        <th>Taille</th>
                        <th>Capacité</th>
                    </tr>
                </thead>
                <tbody id="adminSallesTbody"></tbody>
            </table>
        </div>
    </section>
</div>

<div id="gestion-section" style="display:none;">
    <div class="sub-menu">
        <button class="sub-btn active" onclick="showGestionSubSection('etudiants', event)">Gestion étudiants</button>
        <button class="sub-btn" onclick="showGestionSubSection('enseignants', event)">Gestion enseignants</button>
    </div>

    <!-- === SOUS SECTION ETUDIANTS (ton code actuel inchangé) === -->
    <div id="gestion-etudiants-subsection">
        <section class="content">
            <div class="page-header">
                <h2>Liste des Étudiants</h2>
                <div class="search-filter-bar">
                    <input type="text" id="searchStudent" class="search-input" placeholder="Rechercher un étudiant...">
                    <select id="filterFiliere" class="filter-select">
                        <option value="">Toutes les filières</option>
                    </select>
                    <select id="filterNiveau" class="filter-select">
                        <option value="">Tous les niveaux</option>
                    </select>
                    <button class="btn-add-reservation" onclick="openStudentModal()">+ Nouvel Étudiant</button>
                </div>
                <div id="studentsMsg" style="margin-top:10px;"></div>
            </div>

            <div class="students-table-container">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Filière</th>
                            <th>Niveau</th>
                            <th>Année</th>
                            <th>Groupe</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody"></tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- === SOUS SECTION ENSEIGNANTS (nouvelle) === -->
    <div id="gestion-enseignants-subsection" style="display:none;">
        <section class="content">
            <div class="page-header">
                <h2>Liste des Enseignants</h2>
                <div class="search-filter-bar">
                    <input type="text" id="searchTeacher" class="search-input" placeholder="Rechercher un enseignant...">
                    <button class="btn-add-reservation" onclick="openTeacherModal()">+ Nouvel Enseignant</button>
                </div>

                <div id="teachersMsg" style="margin-top:10px;"></div>
            </div>

            <div class="students-table-container">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Mot de passe</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="teachersTableBody"></tbody>
                </table>
            </div>
        </section>
    </div>
</div>


<div id="cours-section" style="display:none;">
    <div class="sub-menu">
        <button class="sub-btn active" onclick="showCoursSubSection('add', event)">Ajouter un cours</button>
        <button class="sub-btn" onclick="showCoursSubSection('list', event)">Liste des cours / Affectations</button>
    </div>

    <div id="cours-add-section">
        <section class="content">
            <div class="form-container">
                <h2>Ajouter un cours + Affectation Enseignant</h2>

                <form id="courseCreateForm" class="cours-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Code cours *</label>
                            <input type="text" id="cours_code" required placeholder="Ex: ALGO1">
                        </div>
                        <div class="form-group">
                            <label>Nom du cours *</label>
                            <input type="text" id="cours_nom" required placeholder="Ex: Algorithmes et structures de données">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="cours_desc" rows="3" placeholder="Optionnel"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Filière *</label>
                            <select id="cours_filiere" required></select>
                            <small style="display:block;margin-top:6px;color:#666;">Choisis "— Ajouter —" pour créer une nouvelle filière.</small>
                        </div>
                        <div class="form-group" id="wrapNewFiliere" style="display:none;">
                            <label>Nouvelle filière (Nom) *</label>
                            <input type="text" id="new_filiere_nom" placeholder="Ex: Informatique">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Niveau *</label>
                            <select id="cours_niveau" required></select>
                        </div>
                        <div class="form-group" id="wrapNewNiveau" style="display:none;">
                            <label>Nouveau niveau (Libellé) *</label>
                            <input type="text" id="new_niveau_libelle" placeholder="Ex: 1ère année">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Semestre</label>
                            <select id="cours_semestre">
                                <option value="">(Optionnel)</option>
                                <option value="1">Semestre 1</option>
                                <option value="2">Semestre 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Enseignant *</label>
                            <select id="cours_enseignant" required></select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Année scolaire *</label>
                            <input type="text" id="cours_annee" required value="2025-2026" placeholder="Ex: 2025-2026">
                        </div>
                        <div class="form-group">
                            <label>Groupe (optionnel)</label>
                            <input type="text" id="cours_groupe" placeholder="Ex: G1">
                        </div>
                    </div>

                    <div id="courseMsg" style="margin:10px 0;"></div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="document.getElementById('courseCreateForm').reset();toggleNewFieldsCourse();">Annuler</button>
                        <button type="submit" class="btn-submit">Créer</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <div id="cours-list-section" style="display:none;">
        <section class="content">
            <div class="page-header">
                <h2>Liste des cours / Affectations</h2>
                <div class="search-filter-bar">
                    <input type="text" id="searchCourse" class="search-input" placeholder="Rechercher un cours...">
                    <button class="btn-add-reservation" onclick="loadCourses()">Rafraîchir</button>
                </div>
                <div id="coursesMsg" style="margin-top:10px;"></div>
            </div>

            <div class="students-table-container">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Cours</th>
                            <th>Filière</th>
                            <th>Niveau</th>
                            <th>Semestre</th>
                            <th>Enseignant</th>
                            <th>Année</th>
                            <th>Groupe</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="coursesTableBody"></tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<!-- ====================== NOUVEAU : DEMANDES DOCUMENTS ====================== -->
<div id="docs-section" style="display:none;">
    <section class="reservations-container">
        <div class="reservations-header">
            <h2>Demandes de documents étudiants</h2>
            <button class="btn-add-reservation" onclick="loadDocDemandes()">Rafraîchir</button>
        </div>

        <div class="search-filter-bar" style="margin: 10px 0;">
            <select id="docFilterStatut" class="filter-select" style="max-width:220px;">
                <option value="">Tous les statuts</option>
                <option value="non_traite">Non traitée</option>
                <option value="traitee">Traitée</option>
                <option value="refusee">Refusée</option>
            </select>
            <input type="text" id="docSearch" class="search-input" placeholder="Rechercher (étudiant, email, type, commentaire...)">
        </div>

        <div id="docDemandesMsg" style="margin:10px 0;"></div>
        <div id="doc-demandes-list" class="reservations-list"></div>
    </section>
</div>

<!-- ====================== NOUVEAU : EMPLOI DU TEMPS ====================== -->
<?php include __DIR__ . '/sections/edt_form.php'; ?>
<!-- ====================== FIN NOUVEAU : EMPLOI DU TEMPS ====================== -->


<!-- MODAL REFUS DOCUMENT (NOUVEAU) -->
<div id="docRejectModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Refuser la demande</h3>
            <span class="close-modal" onclick="closeDocRejectModal()">&times;</span>
        </div>

        <form id="docRejectForm">
            <input type="hidden" id="doc_reject_id">
            <div class="form-group">
                <label>Motif de refus *</label>
                <input type="text" id="doc_reject_motif" required placeholder="Ex: Dossier incomplet / paiement non à jour...">
            </div>

            <div id="docRejectMsg" style="margin:10px 0;"></div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeDocRejectModal()">Annuler</button>
                <button type="submit" class="btn-submit">Refuser</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL VALIDATION DOCUMENT (NOUVEAU) -->
<div id="docValidateModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Valider la demande</h3>
            <span class="close-modal" onclick="closeDocValidateModal()">&times;</span>
        </div>
        <div style="padding: 20px 0; font-size: 1.05em;">
            <p>Voulez-vous vraiment <strong>valider</strong> cette demande ?</p>
            <p style="margin-top: 12px; color: #555; font-size: 0.95em;">
                Un email sera envoyé à l’étudiant pour venir récupérer le document au service de scolarité.
            </p>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-cancel" onclick="closeDocValidateModal()">Annuler</button>
            <button type="button" class="btn-submit" id="btnDocConfirmValidate">Valider</button>
        </div>
    </div>
</div>
<!-- ====================== FIN NOUVEAU ====================== -->

<!-- MODAL ÉTUDIANT -->
<div id="studentModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="studentModalTitle">Nouvel Étudiant</h3>
            <span class="close-modal" onclick="closeStudentModal()">&times;</span>
        </div>

        <form id="studentForm">
            <input type="hidden" id="student_id">

            <div class="form-row">
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" id="student_nom" required>
                </div>
                <div class="form-group">
                    <label>Prénom *</label>
                    <input type="text" id="student_prenom" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" id="student_email" required>
                </div>
                <div class="form-group">
                    <label>Mot de passe *</label>
                    <input type="text" id="student_mdp" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Filière *</label>
                    <select id="student_filiere" required></select>
                </div>
                <div class="form-group" id="wrapStudentNewFiliere" style="display:none;">
                    <label>Nouvelle filière (Nom) *</label>
                    <input type="text" id="student_new_filiere_nom" placeholder="Ex: Informatique">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Niveau *</label>
                    <select id="student_niveau" required></select>
                </div>
                <div class="form-group" id="wrapStudentNewNiveau" style="display:none;">
                    <label>Nouveau niveau (Libellé) *</label>
                    <input type="text" id="student_new_niveau_libelle" placeholder="Ex: 1ère année">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Année scolaire *</label>
                    <input type="text" id="student_annee" required value="2025-2026">
                </div>
                <div class="form-group">
                    <label>Groupe (optionnel)</label>
                    <input type="text" id="student_groupe" placeholder="Ex: G1">
                </div>
            </div>

            <div id="studentMsg" style="margin:10px 0;"></div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeStudentModal()">Annuler</button>
                <button type="submit" class="btn-submit">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ENSEIGNANT -->
<div id="teacherModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="teacherModalTitle">Nouvel Enseignant</h3>
            <span class="close-modal" onclick="closeTeacherModal()">&times;</span>
        </div>

        <form id="teacherForm">
            <input type="hidden" id="teacher_id">

            <div class="form-row">
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" id="teacher_nom" required>
                </div>
                <div class="form-group">
                    <label>Prénom *</label>
                    <input type="text" id="teacher_prenom" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" id="teacher_email" required>
                </div>
                <div class="form-group">
                    <label>Mot de passe *</label>
                    <input type="text" id="teacher_mdp" required>
                </div>
            </div>

            <div id="teacherMsg" style="margin:10px 0;"></div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeTeacherModal()">Annuler</button>
                <button type="submit" class="btn-submit">Enregistrer</button>
            </div>
        </form>
    </div>
</div>


<!-- MODAL CONFIRMATION SUPPRESSION -->
<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmation</h3>
            <span class="close-modal" onclick="closeConfirmModal()">&times;</span>
        </div>
        <div style="padding: 10px 0;">
            <p id="confirmText" style="margin:0;"></p>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-cancel" onclick="closeConfirmModal()">Annuler</button>
            <button type="button" class="btn-submit" id="confirmOkBtn">Valider</button>
        </div>
    </div>
</div>

<!-- MODAL MODIFICATION AFFECTATION -->
<div id="affectModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Modifier l'affectation</h3>
            <span class="close-modal" onclick="closeAffectModal()">&times;</span>
        </div>

        <form id="affectForm">
            <input type="hidden" id="affect_id">

            <div class="form-row">
                <div class="form-group">
                    <label>Enseignant *</label>
                    <select id="affect_enseignant" required></select>
                </div>
                <div class="form-group">
                    <label>Année scolaire *</label>
                    <input type="text" id="affect_annee" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Groupe (optionnel)</label>
                    <input type="text" id="affect_groupe" placeholder="Ex: G1">
                </div>
            </div>

            <div id="affectMsg" style="margin:10px 0;"></div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeAffectModal()">Annuler</button>
                <button type="submit" class="btn-submit">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REJET RÉSERVATION -->
<div id="rejectModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Rejeter la réservation</h3>
            <span class="close-modal" onclick="closeRejectModal()">&times;</span>
        </div>

        <form id="rejectForm">
            <input type="hidden" id="reject_id">
            <div class="form-group">
                <label>Motif de rejet *</label>
                <input type="text" id="reject_motif" required placeholder="Ex: Conflit horaire / salle indisponible">
            </div>

            <div id="rejectMsg" style="margin:10px 0;"></div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeRejectModal()">Annuler</button>
                <button type="submit" class="btn-submit">Rejeter</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL VALIDATION RÉSERVATION (NOUVEAU) -->
<div id="validateReservationModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmer la validation</h3>
            <span class="close-modal" onclick="closeValidateReservationModal()">&times;</span>
        </div>
        <div style="padding: 20px 0; font-size: 1.05em;">
            <p>Voulez-vous vraiment <strong>valider</strong> cette réservation ?</p>
            <p style="margin-top: 12px; color: #555; font-size: 0.95em;">
                Une fois validée, la réservation sera approuvée et l'enseignant sera informé.
            </p>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-cancel" onclick="closeValidateReservationModal()">Annuler</button>
            <button type="button" class="btn-submit" id="btnConfirmValidate">Valider</button>
        </div>
    </div>
</div>

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
        </div>
    </div>
    <div class="footer-line"></div>
    Copyright © 2023 HESTIM
</footer>

<script>
function showMainSection(section, event) {
    document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
    if (event?.target) event.target.classList.add('active');

    document.getElementById('salles-section').style.display = 'none';
    document.getElementById('gestion-section').style.display = 'none';
    document.getElementById('cours-section').style.display = 'none';
    // NOUVEAU
    document.getElementById('docs-section').style.display = 'none';
    document.getElementById('edt-section').style.display = 'none';


    if (section === 'salles') {
        document.getElementById('salles-section').style.display = 'block';
        initSalles();
    }
    if (section === 'gestion') {
        document.getElementById('gestion-section').style.display = 'block';
        initGestion(); 
    }
    if (section === 'cours') {
        document.getElementById('cours-section').style.display = 'block';
        initCours();
    }
    // NOUVEAU
    if (section === 'docs') {
        document.getElementById('docs-section').style.display = 'block';
        initDocs();
    }

    if (section === 'edt') {
    document.getElementById('edt-section').style.display = 'block';
    initEdt();
   }

}

function showGestionSubSection(sub, event) {
  document.querySelectorAll('#gestion-section .sub-btn').forEach(btn => btn.classList.remove('active'));
  if (event?.target) event.target.classList.add('active');

  document.getElementById('gestion-etudiants-subsection').style.display = (sub === 'etudiants') ? 'block' : 'none';
  document.getElementById('gestion-enseignants-subsection').style.display = (sub === 'enseignants') ? 'block' : 'none';

  if (sub === 'etudiants') initStudents();
  if (sub === 'enseignants') initTeachers();
}

async function initGestion() {
  // par défaut onglet étudiants
  showGestionSubSection('etudiants', null);
}

function showCoursSubSection(sub, event) {
    document.querySelectorAll('#cours-section .sub-btn').forEach(btn => btn.classList.remove('active'));
    if (event?.target) event.target.classList.add('active');

    document.getElementById('cours-add-section').style.display = (sub === 'add') ? 'block' : 'none';
    document.getElementById('cours-list-section').style.display = (sub === 'list') ? 'block' : 'none';

    if (sub === 'list') loadCourses();
}

function showSallesSubSection(sub, event) {
    document.querySelectorAll('#salles-section .sub-btn').forEach(btn => btn.classList.remove('active'));
    if (event?.target) event.target.classList.add('active');

    document.getElementById('admin-reservations-section').style.display = (sub === 'reservations') ? 'block' : 'none';
    document.getElementById('admin-salles-list-section').style.display = (sub === 'salles') ? 'block' : 'none';

    if (sub === 'reservations') loadAdminReservations();
    if (sub === 'salles') loadAdminSalles();
}

function escapeHtml(str) {
  return String(str ?? '')
    .replaceAll('&','&amp;').replaceAll('<','&lt;')
    .replaceAll('>','&gt;').replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

function setMsg(id, text, ok=false) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.color = ok ? 'green' : '#b30000';
    el.textContent = text || '';
}

function fmtDateTime(sqlDt) {
    if (!sqlDt) return '-';
    const d = new Date(String(sqlDt).replace(' ', 'T'));
    return d.toLocaleString('fr-FR', { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit' });
}

function statusUi(statut) {
    if (statut === 'validee') return {label:'Validée', cls:'status-confirmed'};
    if (statut === 'en_attente') return {label:'En attente', cls:'status-pending'};
    if (statut === 'rejetee') return {label:'Rejetée', cls:'status-cancelled'};
    if (statut === 'annulee') return {label:'Annulée', cls:'status-cancelled'};
    return {label: statut, cls:'status-pending'};
}

let ADMIN_RES = [];
let ADMIN_SALLES = [];

document.getElementById('resFilterStatut').addEventListener('change', () => renderAdminReservations());
document.getElementById('resSearch').addEventListener('input', () => renderAdminReservations());

async function loadAdminReservations() {
    const list = document.getElementById('admin-reservations-list');
    list.innerHTML = `<p style="padding:10px;color:#666;">Chargement...</p>`;
    setMsg('adminReservationsMsg','');

    const res = await fetch('admin_reservations_list.php', { credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        list.innerHTML = `<p style="padding:10px;color:#b30000;">${escapeHtml(data.message || 'Erreur')}</p>`;
        ADMIN_RES = [];
        return;
    }
    ADMIN_RES = data.data || [];
    renderAdminReservations();
}

function renderAdminReservations() {
    const list = document.getElementById('admin-reservations-list');
    const statut = document.getElementById('resFilterStatut').value;
    const q = (document.getElementById('resSearch').value || '').toLowerCase();

    let rows = ADMIN_RES;

    if (statut) rows = rows.filter(r => r.statut === statut);

    if (q) {
        rows = rows.filter(r => {
            const hay = [
                r.salle_nom, r.batiment, r.cours_nom, r.niveau,
                r.enseignant_nom || '', r.enseignant_email || '',
                r.motif, r.motif_autre
            ].map(x => String(x ?? '').toLowerCase()).join(' ');
            return hay.includes(q);
        });
    }

    if (!rows.length) {
        list.innerHTML = `<p style="text-align:center; padding: 20px; color: #666;">Aucune réservation</p>`;
        return;
    }

    list.innerHTML = '';
    rows.forEach(r => {
        const st = statusUi(r.statut);
        const motif = (r.motif === 'autre' && r.motif_autre) ? r.motif_autre : (r.motif || '').toUpperCase();

        const item = document.createElement('div');
        item.className = 'reservation-item';
        item.innerHTML = `
            <div class="reservation-info">
                <strong>${escapeHtml(r.salle_nom)} (Bât. ${escapeHtml(r.batiment)})</strong>
                <span class="status-badge ${st.cls}" style="margin-left:10px;">${st.label}</span>
                <br>
                <small>
                    ${fmtDateTime(r.date_debut)} → ${fmtDateTime(r.date_fin)}
                    | ${escapeHtml(motif)}
                    | Cours: ${escapeHtml(r.cours_nom)} | Niveau: ${escapeHtml(r.niveau)} | Effectif: ${escapeHtml(r.effectif)}
                </small>
                <br>
                ${r.rejet_motif ? `<br><small style="color:#b30000;">Motif rejet : ${escapeHtml(r.rejet_motif)}</small>` : ''}
            </div>

            <div class="reservation-actions">
                ${r.statut === 'en_attente' ? `
                    <button class="btn-edit" onclick="openValidateReservationModal(${r.id})">Valider</button>
                    <button class="btn-delete" onclick="openRejectModal(${r.id})">Rejeter</button>
                ` : ''}
            </div>
        `;
        list.appendChild(item);
    });
}

let resToValidateId = null;

function openValidateReservationModal(id) {
    resToValidateId = id;
    document.getElementById('validateReservationModal').style.display = 'flex';
}

function closeValidateReservationModal() {
    resToValidateId = null;
    document.getElementById('validateReservationModal').style.display = 'none';
}

document.getElementById('btnConfirmValidate').addEventListener('click', async () => {
    if (!resToValidateId) return;

    const id = resToValidateId;
    closeValidateReservationModal();

    const fd = new FormData();
    fd.append('id', id);

    const res = await fetch('admin_reservation_validate.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        setMsg('adminReservationsMsg', data.message || 'Erreur lors de la validation');
        return;
    }

    setMsg('adminReservationsMsg', data.message || 'Réservation validée.', true);
    await loadAdminReservations();
});

function openRejectModal(id) {
    setMsg('rejectMsg','');
    document.getElementById('reject_id').value = id;
    document.getElementById('reject_motif').value = '';
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

document.getElementById('rejectForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    setMsg('rejectMsg','');

    const id = document.getElementById('reject_id').value;
    const motif = document.getElementById('reject_motif').value;

    const fd = new FormData();
    fd.append('id', id);
    fd.append('rejet_motif', motif);

    const res = await fetch('admin_reservation_reject.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        setMsg('rejectMsg', data.message || 'Erreur');
        return;
    }

    closeRejectModal();
    setMsg('adminReservationsMsg', data.message || 'Réservation rejetée.', true);
    await loadAdminReservations();
});

document.getElementById('salleSearch').addEventListener('input', renderAdminSalles);

async function loadAdminSalles() {
    const tbody = document.getElementById('adminSallesTbody');
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:16px;color:#666;">Chargement...</td></tr>`;
    setMsg('adminSallesMsg','');

    const res = await fetch('admin_salles_list.php', { credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:16px;color:#b30000;">${escapeHtml(data.message || 'Erreur')}</td></tr>`;
        ADMIN_SALLES = [];
        return;
    }
    ADMIN_SALLES = data.data || [];
    renderAdminSalles();
}

function renderAdminSalles() {
    const tbody = document.getElementById('adminSallesTbody');
    const q = (document.getElementById('salleSearch').value || '').toLowerCase();

    let rows = ADMIN_SALLES;
    if (q) {
        rows = rows.filter(s => {
            const hay = [s.id, s.batiment, s.nom, s.type, s.etage, s.taille, s.capacite].map(x=>String(x??'').toLowerCase()).join(' ');
            return hay.includes(q);
        });
    }

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:16px;color:#666;">Aucune salle</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    rows.forEach(s => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escapeHtml(s.id)}</td>
            <td>${escapeHtml(s.batiment)}</td>
            <td>${escapeHtml(s.nom)}</td>
            <td>${escapeHtml(s.type)}</td>
            <td>${escapeHtml(s.etage)}</td>
            <td>${escapeHtml(s.taille)}</td>
            <td>${escapeHtml(s.capacite)}</td>
        `;
        tbody.appendChild(tr);
    });
}

let FILIERES = [];
let NIVEAUX = [];
let ENSEIGNANTS = [];
let STUDENTS = [];
let COURSES = [];

async function loadFilieres() {
    const res = await fetch('admin_filieres_list.php', { credentials:'same-origin' });
    const data = await res.json();
    if (res.ok && data.success) FILIERES = data.data || [];
}

async function loadNiveaux() {
    const res = await fetch('admin_niveaux_list.php', { credentials:'same-origin' });
    const data = await res.json();
    if (res.ok && data.success) NIVEAUX = data.data || [];
}

async function loadEnseignants() {
    const res = await fetch('admin_enseignants_list.php', { credentials:'same-origin' });
    const data = await res.json();
    if (res.ok && data.success) ENSEIGNANTS = data.data || [];
}

async function initStudents() {
    await Promise.all([loadFilieres(), loadNiveaux()]);
    fillFiliereSelect('filterFiliere', true);
    fillNiveauSelect('filterNiveau', true);
    await loadStudents();
}

async function loadStudents() {
    const tbody = document.getElementById('studentsTableBody');
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:16px;color:#666;">Chargement...</td></tr>`;

    const res = await fetch('admin_etudiants_list.php', { credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:16px;color:#b30000;">${escapeHtml(data.message || 'Erreur')}</td></tr>`;
        STUDENTS = [];
        return;
    }
    STUDENTS = data.data || [];
    renderStudents();
}

function renderStudents() {
    const tbody = document.getElementById('studentsTableBody');
    const search = (document.getElementById('searchStudent').value || '').toLowerCase();
    const filiereId = document.getElementById('filterFiliere').value;
    const niveauId = document.getElementById('filterNiveau').value;

    let rows = STUDENTS;

    if (search) rows = rows.filter(s => (s.nom_complet || '').toLowerCase().includes(search) || (s.email || '').toLowerCase().includes(search));
    if (filiereId) rows = rows.filter(s => String(s.filiere_id) === String(filiereId));
    if (niveauId) rows = rows.filter(s => String(s.niveau_id) === String(niveauId));

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:16px;color:#666;">Aucun étudiant trouvé</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    rows.forEach(s => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escapeHtml(s.nom_complet)}</td>
            <td>${escapeHtml(s.email)}</td>
            <td>${escapeHtml(s.filiere_nom || '-')}</td>
            <td>${escapeHtml(s.niveau_libelle || '-')}</td>
            <td>${escapeHtml(s.annee_scolaire || '-')}</td>
            <td>${escapeHtml(s.groupe || '-')}</td>
            <td>${fmtDateTime(s.date_inscription)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-edit" onclick="openEditStudent(${s.id})">Modifier</button>
                    <button class="btn-delete" onclick="confirmDeleteStudent(${s.id}, '${escapeHtml(s.nom_complet)}')">Supprimer</button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

document.getElementById('searchStudent').addEventListener('input', renderStudents);
document.getElementById('filterFiliere').addEventListener('change', renderStudents);
document.getElementById('filterNiveau').addEventListener('change', renderStudents);

function fillFiliereSelect(selectId, includeAll=false) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    sel.innerHTML = includeAll ? `<option value="">Toutes les filières</option>` : '';
    if (!includeAll) sel.innerHTML = `<option value="">Sélectionnez</option>`;
    sel.insertAdjacentHTML('beforeend', `<option value="__new__">— Ajouter —</option>`);
    FILIERES.forEach(f => sel.insertAdjacentHTML('beforeend', `<option value="${f.id}">${escapeHtml(f.nom)}</option>`));
}

function fillNiveauSelect(selectId, includeAll=false) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    sel.innerHTML = includeAll ? `<option value="">Tous les niveaux</option>` : '';
    if (!includeAll) sel.innerHTML = `<option value="">Sélectionnez</option>`;
    sel.insertAdjacentHTML('beforeend', `<option value="__new__">— Ajouter —</option>`);
    NIVEAUX.forEach(n => sel.insertAdjacentHTML('beforeend', `<option value="${n.id}">${escapeHtml(n.libelle)}</option>`));
}

function openStudentModal() {
    setMsg('studentMsg','');
    document.getElementById('studentModalTitle').textContent = 'Nouvel Étudiant';
    document.getElementById('studentForm').reset();
    document.getElementById('student_id').value = '';

    fillFiliereSelect('student_filiere', false);
    fillNiveauSelect('student_niveau', false);

    document.getElementById('wrapStudentNewFiliere').style.display = 'none';
    document.getElementById('wrapStudentNewNiveau').style.display = 'none';

    document.getElementById('studentModal').style.display = 'flex';
}

function closeStudentModal() {
    document.getElementById('studentModal').style.display = 'none';
}

document.getElementById('student_filiere').addEventListener('change', () => {
    document.getElementById('wrapStudentNewFiliere').style.display = (document.getElementById('student_filiere').value === '__new__') ? 'block' : 'none';
});

document.getElementById('student_niveau').addEventListener('change', () => {
    document.getElementById('wrapStudentNewNiveau').style.display = (document.getElementById('student_niveau').value === '__new__') ? 'block' : 'none';
});

document.getElementById('studentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    setMsg('studentMsg','');

    const id = document.getElementById('student_id').value;

    const fd = new FormData();
    fd.append('id', id);
    fd.append('nom', document.getElementById('student_nom').value);
    fd.append('prenom', document.getElementById('student_prenom').value);
    fd.append('email', document.getElementById('student_email').value);
    fd.append('mdp', document.getElementById('student_mdp').value);
    fd.append('annee_scolaire', document.getElementById('student_annee').value);
    fd.append('groupe', document.getElementById('student_groupe').value);
    fd.append('filiere_id', document.getElementById('student_filiere').value);
    fd.append('niveau_id', document.getElementById('student_niveau').value);
    fd.append('new_filiere_nom', document.getElementById('student_new_filiere_nom').value);
    fd.append('new_niveau_libelle', document.getElementById('student_new_niveau_libelle').value);

    const url = id ? 'admin_etudiant_update.php' : 'admin_etudiant_create.php';
    const res = await fetch(url, { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        setMsg('studentMsg', data.message || 'Erreur');
        return;
    }

    setMsg('studentsMsg', data.message || 'OK', true);
    closeStudentModal();
    await Promise.all([loadFilieres(), loadNiveaux()]);
    fillFiliereSelect('filterFiliere', true);
    fillNiveauSelect('filterNiveau', true);
    await loadStudents();
});

function openEditStudent(id) {
    const s = STUDENTS.find(x => Number(x.id) === Number(id));
    if (!s) return;

    openStudentModal();
    document.getElementById('studentModalTitle').textContent = 'Modifier Étudiant';
    document.getElementById('student_id').value = s.id;

    document.getElementById('student_nom').value = s.nom || '';
    document.getElementById('student_prenom').value = s.prenom || '';
    document.getElementById('student_email').value = s.email || '';
    document.getElementById('student_mdp').value = s.mdp || '';

    document.getElementById('student_annee').value = s.annee_scolaire || '2025-2026';
    document.getElementById('student_groupe').value = s.groupe || '';

    document.getElementById('student_filiere').value = s.filiere_id ? String(s.filiere_id) : '';
    document.getElementById('student_niveau').value = s.niveau_id ? String(s.niveau_id) : '';
}

function confirmDeleteStudent(id, label) {
    document.getElementById('confirmText').textContent = `Supprimer l'étudiant "${label}" ? (suppression en cascade)`;
    document.getElementById('confirmOkBtn').onclick = async () => {
        closeConfirmModal();
        await deleteStudent(id);
    };
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

async function deleteStudent(id) {
    const fd = new FormData();
    fd.append('id', id);

    const res = await fetch('admin_etudiant_delete.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        setMsg('studentsMsg', data.message || 'Erreur');
        return;
    }
    setMsg('studentsMsg', data.message || 'Supprimé', true);
    await loadStudents();
}

let TEACHERS = [];

async function initTeachers() {
  await loadTeachers();
}

document.getElementById('searchTeacher')?.addEventListener('input', renderTeachers);

async function loadTeachers() {
  const tbody = document.getElementById('teachersTableBody');
  tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:16px;color:#666;">Chargement...</td></tr>`;
  setMsg('teachersMsg','');

  const res = await fetch('admin_enseigant_gestion_list.php', { credentials:'same-origin' });
  const data = await res.json();

  if (!res.ok || !data.success) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:16px;color:#b30000;">${escapeHtml(data.message || 'Erreur')}</td></tr>`;
    TEACHERS = [];
    return;
  }
  TEACHERS = data.data || [];
  renderTeachers();

  // refresh liste enseignants pour la création cours/affectations
  await loadEnseignants();
}

function renderTeachers() {
  const tbody = document.getElementById('teachersTableBody');
  const q = (document.getElementById('searchTeacher').value || '').toLowerCase();

  let rows = TEACHERS;
  if (q) {
    rows = rows.filter(t => {
      const hay = [t.nom_complet, t.email].map(x => String(x ?? '').toLowerCase()).join(' ');
      return hay.includes(q);
    });
  }

  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:16px;color:#666;">Aucun enseignant</td></tr>`;
    return;
  }

  tbody.innerHTML = '';
  rows.forEach(t => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${escapeHtml(t.nom_complet)}</td>
      <td>${escapeHtml(t.email)}</td>
      <td>${escapeHtml(t.mdp)}</td>
      <td>
        <div class="action-buttons">
          <button class="btn-edit" onclick="openEditTeacher(${t.id})">Modifier</button>
        </div>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

// <button class="btn-delete" onclick="confirmDeleteTeacher(${t.id}, '${escapeHtml(t.nom_complet)}')">Supprimer</button>

/* Modal enseignant */
function openTeacherModal() {
  setMsg('teacherMsg','');
  document.getElementById('teacherModalTitle').textContent = 'Nouvel Enseignant';
  document.getElementById('teacherForm').reset();
  document.getElementById('teacher_id').value = '';
  document.getElementById('teacherModal').style.display = 'flex';
}
function closeTeacherModal() {
  document.getElementById('teacherModal').style.display = 'none';
}

document.getElementById('teacherForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  setMsg('teacherMsg','');

  const id = document.getElementById('teacher_id').value;

  const fd = new FormData();
  fd.append('id', id);
  fd.append('nom', document.getElementById('teacher_nom').value);
  fd.append('prenom', document.getElementById('teacher_prenom').value);
  fd.append('email', document.getElementById('teacher_email').value);
  fd.append('mdp', document.getElementById('teacher_mdp').value);

  const url = id ? 'admin_enseignant_update.php' : 'admin_enseignant_create.php';
  const res = await fetch(url, { method:'POST', body:fd, credentials:'same-origin' });
  const data = await res.json();

  if (!res.ok || !data.success) {
    setMsg('teacherMsg', data.message || 'Erreur');
    return;
  }

  setMsg('teachersMsg', data.message || 'OK', true);
  closeTeacherModal();
  await loadTeachers();
});

/* Edit */
function openEditTeacher(id) {
  const t = TEACHERS.find(x => Number(x.id) === Number(id));
  if (!t) return;

  openTeacherModal();
  document.getElementById('teacherModalTitle').textContent = 'Modifier Enseignant';
  document.getElementById('teacher_id').value = t.id;

  document.getElementById('teacher_nom').value = t.nom || '';
  document.getElementById('teacher_prenom').value = t.prenom || '';
  document.getElementById('teacher_email').value = t.email || '';
  document.getElementById('teacher_mdp').value = t.mdp || '';
}

/* Delete */
function confirmDeleteTeacher(id, label) {
  document.getElementById('confirmText').textContent = `Supprimer l'enseignant "${label}" ? (affectations supprimées en cascade)`;
  document.getElementById('confirmOkBtn').onclick = async () => {
    closeConfirmModal();
    await deleteTeacher(id);
  };
  document.getElementById('confirmModal').style.display = 'flex';
}

async function deleteTeacher(id) {
  const fd = new FormData();
  fd.append('id', id);

  const res = await fetch('admin_enseignant_delete.php', { method:'POST', body:fd, credentials:'same-origin' });
  const data = await res.json();

  if (!res.ok || !data.success) {
    setMsg('teachersMsg', data.message || 'Erreur');
    return;
  }
  setMsg('teachersMsg', data.message || 'Supprimé', true);
  await loadTeachers();
}

async function initCours() {
    await Promise.all([loadFilieres(), loadNiveaux(), loadEnseignants()]);
    fillCourseFormReferentials();
}

function fillCourseFormReferentials() {
    const fSel = document.getElementById('cours_filiere');
    const nSel = document.getElementById('cours_niveau');
    const eSel = document.getElementById('cours_enseignant');

    fSel.innerHTML = `<option value="">Sélectionnez</option><option value="__new__">— Ajouter —</option>`;
    FILIERES.forEach(f => fSel.insertAdjacentHTML('beforeend', `<option value="${f.id}">${escapeHtml(f.nom)}</option>`));

    nSel.innerHTML = `<option value="">Sélectionnez</option><option value="__new__">— Ajouter —</option>`;
    NIVEAUX.forEach(n => nSel.insertAdjacentHTML('beforeend', `<option value="${n.id}">${escapeHtml(n.libelle)}</option>`));

    eSel.innerHTML = `<option value="">Sélectionnez</option>`;
    ENSEIGNANTS.forEach(en => eSel.insertAdjacentHTML('beforeend', `<option value="${en.id}">${escapeHtml(en.nom_complet)} (${escapeHtml(en.email)})</option>`));

    toggleNewFieldsCourse();
}

function toggleNewFieldsCourse() {
    document.getElementById('wrapNewFiliere').style.display = (document.getElementById('cours_filiere').value === '__new__') ? 'block' : 'none';
    document.getElementById('wrapNewNiveau').style.display = (document.getElementById('cours_niveau').value === '__new__') ? 'block' : 'none';
}

document.getElementById('cours_filiere').addEventListener('change', toggleNewFieldsCourse);
document.getElementById('cours_niveau').addEventListener('change', toggleNewFieldsCourse);

document.getElementById('courseCreateForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    setMsg('courseMsg','');

    const fd = new FormData();
    fd.append('code', document.getElementById('cours_code').value);
    fd.append('nom', document.getElementById('cours_nom').value);
    fd.append('description', document.getElementById('cours_desc').value);
    fd.append('filiere_id', document.getElementById('cours_filiere').value);
    fd.append('new_filiere_nom', document.getElementById('new_filiere_nom').value);
    fd.append('niveau_id', document.getElementById('cours_niveau').value);
    fd.append('new_niveau_libelle', document.getElementById('new_niveau_libelle').value);
    fd.append('semestre', document.getElementById('cours_semestre').value);
    fd.append('enseignant_id', document.getElementById('cours_enseignant').value);
    fd.append('annee_scolaire', document.getElementById('cours_annee').value);
    fd.append('groupe', document.getElementById('cours_groupe').value);

    const res = await fetch('admin_cours_create.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        setMsg('courseMsg', data.message || 'Erreur');
        return;
    }

    setMsg('courseMsg', data.message || 'Créé', true);
    await Promise.all([loadFilieres(), loadNiveaux(), loadEnseignants()]);
    fillCourseFormReferentials();
    document.getElementById('courseCreateForm').reset();
    toggleNewFieldsCourse();
});

async function loadCourses() {
    const tbody = document.getElementById('coursesTableBody');
    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:16px;color:#666;">Chargement...</td></tr>`;

    const res = await fetch('admin_cours_list.php', { credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:16px;color:#b30000;">${escapeHtml(data.message || 'Erreur')}</td></tr>`;
        COURSES = [];
        return;
    }
    COURSES = data.data || [];
    renderCourses();
}

function renderCourses() {
    const tbody = document.getElementById('coursesTableBody');
    const search = (document.getElementById('searchCourse').value || '').toLowerCase();

    let rows = COURSES;
    if (search) {
        rows = rows.filter(r =>
            (r.cours_nom || '').toLowerCase().includes(search) ||
            (r.cours_code || '').toLowerCase().includes(search) ||
            (r.filiere_nom || '').toLowerCase().includes(search)
        );
    }

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:16px;color:#666;">Aucun cours</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    rows.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escapeHtml(r.cours_code)}</td>
            <td>${escapeHtml(r.cours_nom)}</td>
            <td>${escapeHtml(r.filiere_nom)}</td>
            <td>${escapeHtml(r.niveau_libelle)}</td>
            <td>${r.semestre ? escapeHtml(r.semestre) : '-'}</td>
            <td>${escapeHtml(r.enseignant_nom || '-')}</td>
            <td>${escapeHtml(r.annee_scolaire || '-')}</td>
            <td>${escapeHtml(r.groupe || '-')}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-edit" onclick="openAffectModal(${r.affectation_id})">Modifier</button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

document.getElementById('searchCourse').addEventListener('input', renderCourses);

function openAffectModal(affectId) {
    setMsg('affectMsg','');
    const r = COURSES.find(x => Number(x.affectation_id) === Number(affectId));
    if (!r) return;

    const sel = document.getElementById('affect_enseignant');
    sel.innerHTML = `<option value="">Sélectionnez</option>`;
    ENSEIGNANTS.forEach(en => sel.insertAdjacentHTML('beforeend', `<option value="${en.id}">${escapeHtml(en.nom_complet)} (${escapeHtml(en.email)})</option>`));

    document.getElementById('affect_id').value = r.affectation_id;
    document.getElementById('affect_enseignant').value = r.enseignant_id ? String(r.enseignant_id) : '';
    document.getElementById('affect_annee').value = r.annee_scolaire || '2025-2026';
    document.getElementById('affect_groupe').value = r.groupe || '';

    document.getElementById('affectModal').style.display = 'flex';
}

function closeAffectModal() {
    document.getElementById('affectModal').style.display = 'none';
}

document.getElementById('affectForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    setMsg('affectMsg','');

    const fd = new FormData();
    fd.append('id', document.getElementById('affect_id').value);
    fd.append('enseignant_id', document.getElementById('affect_enseignant').value);
    fd.append('annee_scolaire', document.getElementById('affect_annee').value);
    fd.append('groupe', document.getElementById('affect_groupe').value);

    const res = await fetch('admin_affectation_update.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        setMsg('affectMsg', data.message || 'Erreur');
        return;
    }

    setMsg('coursesMsg', data.message || 'Mis à jour', true);
    closeAffectModal();
    await loadCourses();
});

/* ====================== NOUVEAU : DEMANDES DOCUMENTS (JS) ====================== */
let DOC_DEMANDES = [];
let docToValidateId = null;

function typeDocLabel(type, autre) {
    if (type === 'homologation') return 'Homologation';
    if (type === 'bulletin') return 'Bulletin';
    if (type === 'certificat_scolarite') return 'Certificat de scolarité';
    if (type === 'autre') return autre ? ('Autre : ' + autre) : 'Autre';
    return type || '-';
}

function docStatusUi(statut) {
    if (statut === 'traitee') return {label:'Traitée', cls:'status-confirmed'};
    if (statut === 'refusee') return {label:'Refusée', cls:'status-cancelled'};
    return {label:'Non traitée', cls:'status-pending'};
}

function initDocs() {
    loadDocDemandes();
}

document.getElementById('docFilterStatut').addEventListener('change', renderDocDemandes);
document.getElementById('docSearch').addEventListener('input', renderDocDemandes);

async function loadDocDemandes() {
    const list = document.getElementById('doc-demandes-list');
    list.innerHTML = `<p style="padding:10px;color:#666;">Chargement...</p>`;
    setMsg('docDemandesMsg','');

    const res = await fetch('admin_document_demandes_list.php', { credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        list.innerHTML = `<p style="padding:10px;color:#b30000;">${escapeHtml(data.message || 'Erreur')}</p>`;
        DOC_DEMANDES = [];
        return;
    }
    DOC_DEMANDES = data.data || [];
    renderDocDemandes();
}

function renderDocDemandes() {
    const list = document.getElementById('doc-demandes-list');
    const statut = document.getElementById('docFilterStatut').value;
    const q = (document.getElementById('docSearch').value || '').toLowerCase();

    let rows = DOC_DEMANDES;

    if (statut) rows = rows.filter(d => d.statut === statut);

    if (q) {
        rows = rows.filter(d => {
            const hay = [
                d.id, d.etudiant_id, d.etudiant_nom, d.etudiant_email,
                d.type_document, d.autre_document, d.commentaire, d.motif_refus, d.statut
            ].map(x => String(x ?? '').toLowerCase()).join(' ');
            return hay.includes(q);
        });
    }

    if (!rows.length) {
        list.innerHTML = `<p style="text-align:center; padding: 20px; color: #666;">Aucune demande</p>`;
        return;
    }

    list.innerHTML = '';
    rows.forEach(d => {
        const st = docStatusUi(d.statut);
        const docLabel = typeDocLabel(d.type_document, d.autre_document);

        const item = document.createElement('div');
        item.className = 'reservation-item';
        item.innerHTML = `
            <div class="reservation-info">
                <strong>Demande #${escapeHtml(d.id)} — ${escapeHtml(docLabel)}</strong>
                <span class="status-badge ${st.cls}" style="margin-left:10px;">${st.label}</span>
                <br>
                <small>
                    Étudiant : ${escapeHtml(d.etudiant_nom || 'Inconnu')} ${d.etudiant_email ? `(${escapeHtml(d.etudiant_email)})` : ''}
                </small>
                <br>
                <small>
                    Demandée le : ${fmtDateTime(d.date_demande)}
                    ${d.date_traitement ? `| Traitée le : ${fmtDateTime(d.date_traitement)}` : ''}
                </small>
                ${d.commentaire ? `<br><small>Commentaire : ${escapeHtml(d.commentaire)}</small>` : ''}
                ${d.motif_refus ? `<br><small style="color:#b30000;">Motif refus : ${escapeHtml(d.motif_refus)}</small>` : ''}
            </div>

            <div class="reservation-actions">
                ${d.statut === 'non_traite' ? `
                    <button class="btn-edit" onclick="openDocValidateModal(${d.id})">Valider</button>
                    <button class="btn-delete" onclick="openDocRejectModal(${d.id})">Refuser</button>
                ` : ''}
            </div>
        `;
        list.appendChild(item);
    });
}

function openDocValidateModal(id) {
    docToValidateId = id;
    document.getElementById('docValidateModal').style.display = 'flex';
}

function closeDocValidateModal() {
    docToValidateId = null;
    document.getElementById('docValidateModal').style.display = 'none';
}

document.getElementById('btnDocConfirmValidate').addEventListener('click', async () => {
    if (!docToValidateId) return;

    const id = docToValidateId;
    closeDocValidateModal();

    const fd = new FormData();
    fd.append('id', id);

    const res = await fetch('admin_document_demande_validate.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        setMsg('docDemandesMsg', data.message || 'Erreur lors de la validation');
        return;
    }

    setMsg('docDemandesMsg', data.message || 'Demande validée.', true);
    await loadDocDemandes();
});

function openDocRejectModal(id) {
    setMsg('docRejectMsg','');
    document.getElementById('doc_reject_id').value = id;
    document.getElementById('doc_reject_motif').value = '';
    document.getElementById('docRejectModal').style.display = 'flex';
}

function closeDocRejectModal() {
    document.getElementById('docRejectModal').style.display = 'none';
}

document.getElementById('docRejectForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    setMsg('docRejectMsg','');

    const id = document.getElementById('doc_reject_id').value;
    const motif = document.getElementById('doc_reject_motif').value;

    const fd = new FormData();
    fd.append('id', id);
    fd.append('motif_refus', motif);

    const res = await fetch('admin_document_demande_reject.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        setMsg('docRejectMsg', data.message || 'Erreur');
        return;
    }

    closeDocRejectModal();
    setMsg('docDemandesMsg', data.message || 'Demande refusée.', true);
    await loadDocDemandes();
});

/* ====================== NOUVEAU : EMPLOI DU TEMPS (JS) ====================== */

let EDT_REF = { filieres: [], niveaux: [], salles: [] };
let EDT_AFFECTATIONS = []; // listées selon filière/niveau/année/groupe
let EDT_LOADED_WEEKS = []; // semaines chargées du mois

function iso(d) {
  // Date -> YYYY-MM-DD
  const pad = (n) => String(n).padStart(2,'0');
  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
}

function frDateLabel(isoDate) {
  const d = new Date(isoDate + 'T00:00:00');
  return d.toLocaleDateString('fr-FR', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}

function setSelectOptions(sel, items, getVal, getLab, placeholder='Sélectionnez') {
  sel.innerHTML = `<option value="">${placeholder}</option>`;
  items.forEach(it => sel.insertAdjacentHTML('beforeend', `<option value="${escapeHtml(getVal(it))}">${escapeHtml(getLab(it))}</option>`));
}

async function initEdt() {
  setMsg('edtMsg','');
  // Charger référentiels
  const res = await fetch('admin_edt_referentials.php', { credentials:'same-origin' });
  const data = await res.json();

  if (!res.ok || !data.success) {
    setMsg('edtMsg', data.message || 'Erreur chargement référentiels');
    return;
  }

  EDT_REF = data.data || { filieres:[], niveaux:[], salles:[] };

  setSelectOptions(
    document.getElementById('edt_filiere'),
    EDT_REF.filieres || [],
    x => x.id,
    x => x.nom,
    'Choisir filière'
  );

  setSelectOptions(
    document.getElementById('edt_niveau'),
    EDT_REF.niveaux || [],
    x => x.id,
    x => x.libelle,
    'Choisir niveau'
  );

  // Valeur par défaut mois = mois courant
  const now = new Date();
  document.getElementById('edt_mois').value = String(now.getMonth()+1);

  // auto charger quand filière/niveau changent
  document.getElementById('edt_filiere').onchange = () => { clearEdtUI(); };
  document.getElementById('edt_niveau').onchange = () => { clearEdtUI(); };
}

function clearEdtUI() {
  EDT_LOADED_WEEKS = [];
  document.getElementById('edtWeeksWrap').innerHTML = '';
}

function edtParams() {
  return {
    filiere_id: document.getElementById('edt_filiere').value,
    niveau_id: document.getElementById('edt_niveau').value,
    annee_scolaire: document.getElementById('edt_annee').value.trim(),
    groupe: document.getElementById('edt_groupe').value.trim(),
    mois: document.getElementById('edt_mois').value
  };
}

async function loadEdtAffectations() {
  const p = edtParams();
  if (!p.filiere_id || !p.niveau_id || !p.annee_scolaire) return [];

    const qs = new URLSearchParams({
    filiere_id: p.filiere_id,
    niveau_id: p.niveau_id,
    annee_scolaire: p.annee_scolaire
    });

  const res = await fetch('admin_edt_affectations_list.php?' + qs.toString(), { credentials:'same-origin' });
  const data = await res.json();

  if (res.ok && data.success) return (data.data || []);
  return [];
}

async function loadEdtMonth() {
  setMsg('edtMsg','');
  const p = edtParams();

  if (!p.filiere_id || !p.niveau_id || !p.annee_scolaire || !p.mois) {
    setMsg('edtMsg', 'Veuillez choisir Filière + Niveau + Année scolaire + Mois');
    return;
  }

  // Charger affectations (cours+enseignant) pour remplir les selects
  EDT_AFFECTATIONS = await loadEdtAffectations();

  // Charger mois depuis la base
  const qs = new URLSearchParams({
    filiere_id: p.filiere_id,
    niveau_id: p.niveau_id,
    annee_scolaire: p.annee_scolaire,
    mois: p.mois
  });

  const res = await fetch('admin_edt_month_get.php?' + qs.toString(), { credentials:'same-origin' });
  const data = await res.json();

  if (!res.ok || !data.success) {
    clearEdtUI();
    setMsg('edtMsg', data.message || 'Erreur chargement mois');
    return;
  }

  EDT_LOADED_WEEKS = data.data?.weeks || [];
  renderWeeks();
  setMsg('edtMsg', 'Mois chargé.', true);
}

function renderWeeks() {
  const wrap = document.getElementById('edtWeeksWrap');
  wrap.innerHTML = '';

  if (!EDT_LOADED_WEEKS.length) {
    wrap.innerHTML = `<p style="padding:12px;color:#666;">Aucune semaine enregistrée pour ce mois. Clique sur “+ Ajouter semaine”.</p>`;
    return;
  }

  EDT_LOADED_WEEKS.forEach((w, idx) => {
    wrap.appendChild(buildWeekCard(w, idx));
  });
}

function buildWeekCard(week, idx) {
  const card = document.createElement('div');
  card.className = 'reservation-item';
  card.style.marginBottom = '12px';

  const weekId = week.week_id || week.id || '';

  card.innerHTML = `
    <div class="reservation-info" style="width:100%;">
      <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
        <div>
          <strong>Semaine ${idx+1}</strong>
          <span style="margin-left:10px;color:#555;">${escapeHtml(week.label || '')}</span>
        </div>
        <div style="display:flex; gap:8px;">
          <button class="btn-edit" type="button" onclick="saveWeek('${weekId}', ${idx})">Enregistrer</button>
          ${weekId ? `<button class="btn-delete" type="button" onclick="deleteWeek('${weekId}')">Supprimer semaine</button>` : ''}
        </div>
      </div>

      <div class="search-filter-bar" style="margin-top:10px;">
        <div class="form-group" style="min-width:220px;">
          <label>Label semaine</label>
          <input class="search-input" id="edt_week_label_${idx}" value="${escapeHtml(week.label || '')}" placeholder="Ex: Du 02 au 06 Février 2026">
        </div>

        <div class="form-group" style="min-width:180px;">
          <label>Date début (lundi)</label>
          <input type="date" class="search-input" id="edt_week_start_${idx}" value="${escapeHtml(week.date_debut || '')}">
        </div>

        <div class="form-group" style="min-width:180px;">
          <label>Date fin (vendredi)</label>
          <input type="date" class="search-input" id="edt_week_end_${idx}" value="${escapeHtml(week.date_fin || '')}">
        </div>

        <button class="btn-add-reservation" type="button" onclick="genWeekDays(${idx})">Générer jours</button>
      </div>

      <div id="edt_week_days_${idx}" style="margin-top:10px;"></div>
    </div>
  `;

  // Render days if already present
  renderDays(idx, week.days || []);
  return card;
}

function addWeek() {
  const p = edtParams();
  if (!p.filiere_id || !p.niveau_id || !p.annee_scolaire || !p.mois) {
    setMsg('edtMsg', 'Choisis Filière + Niveau + Année scolaire + Mois, puis clique sur Charger.', false);
    return;
  }

  EDT_LOADED_WEEKS.push({
    week_id: null,
    label: '',
    date_debut: '',
    date_fin: '',
    days: []
  });

  renderWeeks();
}

function genWeekDays(idx) {
  const start = document.getElementById(`edt_week_start_${idx}`).value;
  const end = document.getElementById(`edt_week_end_${idx}`).value;

  if (!start || !end) {
    setMsg('edtMsg', 'Renseigne date début et date fin.', false);
    return;
  }

  const d0 = new Date(start + 'T00:00:00');
  const d1 = new Date(end + 'T00:00:00');
  if (d1 < d0) {
    setMsg('edtMsg', 'Date fin doit être >= date début.', false);
    return;
  }

  // On génère 5 jours max (lundi->vendredi) selon interval donné (tu peux ajuster)
  const days = [];
  let cur = new Date(d0);
  while (cur <= d1 && days.length < 5) {
    const dayIso = iso(cur);
    days.push({
      jour_date: dayIso,
      sessions: buildDefaultSessions(dayIso)
    });
    cur.setDate(cur.getDate()+1);
  }

  EDT_LOADED_WEEKS[idx].date_debut = start;
  EDT_LOADED_WEEKS[idx].date_fin = end;
  EDT_LOADED_WEEKS[idx].days = days;

  renderDays(idx, days);
}

function buildDefaultSessions(dayIso) {
  // 4 slots: Matin M1/M2, Après-midi A1/A2
  return [
    { slot:'M1', affectation_id:'', salle_id:'', notes:'' },
    { slot:'M2', affectation_id:'', salle_id:'', notes:'' },
    { slot:'A1', affectation_id:'', salle_id:'', notes:'' },
    { slot:'A2', affectation_id:'', salle_id:'', notes:'' },
  ].map(s => ({ ...s, jour_date: dayIso }));
}

function renderDays(idx, days) {
  const wrap = document.getElementById(`edt_week_days_${idx}`);
  if (!wrap) return;

  if (!days.length) {
    wrap.innerHTML = `<p style="padding:10px;color:#666;">Clique sur “Générer jours”.</p>`;
    return;
  }

  wrap.innerHTML = '';
  days.forEach((d, dayIdx) => {
    const block = document.createElement('div');
    block.style.border = '1px solid #eee';
    block.style.borderRadius = '10px';
    block.style.padding = '10px';
    block.style.marginBottom = '10px';
    block.style.background = '#fff';

    block.innerHTML = `
      <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
        <strong>${escapeHtml(frDateLabel(d.jour_date))}</strong>
        <span style="color:#666;">${escapeHtml(d.jour_date)}</span>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px;">
        <div>
          <div style="font-weight:600;margin-bottom:6px;">Matin (09:00–12:30)</div>
          ${renderSlotSelectHtml(idx, dayIdx, 'M1', '09:00–10:45')}
          ${renderSlotSelectHtml(idx, dayIdx, 'M2', '11:00–12:30')}
        </div>
        <div>
          <div style="font-weight:600;margin-bottom:6px;">Après-midi (13:30–17:00)</div>
          ${renderSlotSelectHtml(idx, dayIdx, 'A1', '13:30–15:15')}
          ${renderSlotSelectHtml(idx, dayIdx, 'A2', '15:30–17:00')}
        </div>
      </div>
    `;

    wrap.appendChild(block);

    // Remplir selects avec valeurs existantes si présentes
    fillSlotSelects(idx, dayIdx, d.sessions || buildDefaultSessions(d.jour_date));
  });
}

function renderSlotSelectHtml(weekIdx, dayIdx, slot, hoursLabel) {
  const idAff = `edt_${weekIdx}_${dayIdx}_${slot}_aff`;
  const idSalle = `edt_${weekIdx}_${dayIdx}_${slot}_salle`;
  return `
    <div style="display:grid; grid-template-columns:1fr 180px; gap:8px; align-items:end; margin-bottom:8px;">
      <div class="form-group" style="margin:0;">
        <label>${escapeHtml(hoursLabel)} — Cours/Enseignant</label>
        <select class="filter-select" id="${idAff}"></select>
      </div>
      <div class="form-group" style="margin:0;">
        <label>Salle</label>
        <select class="filter-select" id="${idSalle}"></select>
      </div>
    </div>
  `;
}

function fillSlotSelects(weekIdx, dayIdx, sessions) {
  // options affectations
  const affOptions = EDT_AFFECTATIONS.map(a => ({
    val: a.affectation_id || a.id,
    lab: `${a.cours_nom || a.nom_cours || 'Cours'} — ${a.enseignant_nom || a.nom_enseignant || 'Enseignant'}`
  }));

  // options salles
  const salles = (EDT_REF.salles || []).map(s => ({
    val: s.id,
    lab: `${s.batiment}${s.nom ? ' - ' + s.nom : ''} (${s.type})`
  }));

  const bySlot = {};
  (sessions || []).forEach(s => { bySlot[s.slot] = s; });

  ['M1','M2','A1','A2'].forEach(slot => {
    const selAff = document.getElementById(`edt_${weekIdx}_${dayIdx}_${slot}_aff`);
    const selSalle = document.getElementById(`edt_${weekIdx}_${dayIdx}_${slot}_salle`);
    if (!selAff || !selSalle) return;

    selAff.innerHTML = `<option value="">(vide)</option>` + affOptions.map(o => `<option value="${escapeHtml(o.val)}">${escapeHtml(o.lab)}</option>`).join('');
    selSalle.innerHTML = `<option value="">(vide)</option>` + salles.map(o => `<option value="${escapeHtml(o.val)}">${escapeHtml(o.lab)}</option>`).join('');

    const s = bySlot[slot] || {};
    if (s.affectation_id) selAff.value = String(s.affectation_id);
    if (s.salle_id) selSalle.value = String(s.salle_id);
  });
}

function collectWeekPayload(weekIdx, existingWeekId=null) {
  const p = edtParams();

  const label = document.getElementById(`edt_week_label_${weekIdx}`).value.trim();
  const date_debut = document.getElementById(`edt_week_start_${weekIdx}`).value;
  const date_fin = document.getElementById(`edt_week_end_${weekIdx}`).value;

  const week = EDT_LOADED_WEEKS[weekIdx];
  const days = week.days || [];

  // sessions -> tableau plat attendu par admin_edt_save.php
  const sessions = [];
  days.forEach((d, dayIdx) => {
    ['M1','M2','A1','A2'].forEach(slot => {
      const selAff = document.getElementById(`edt_${weekIdx}_${dayIdx}_${slot}_aff`);
      const selSalle = document.getElementById(`edt_${weekIdx}_${dayIdx}_${slot}_salle`);
      if (!selAff || !selSalle) return;

      const affectation_id = selAff.value ? Number(selAff.value) : null;
      const salle_id = selSalle.value ? Number(selSalle.value) : null;

      // on ne push que si au moins un champ est rempli
      if (affectation_id || salle_id) {
        sessions.push({
          jour_date: d.jour_date,
          slot,
          affectation_id,
          salle_id,
          notes: ''
        });
      }
    });
  });

  return {
    week_id: existingWeekId || null,
    filiere_id: Number(p.filiere_id),
    niveau_id: Number(p.niveau_id),
    annee_scolaire: p.annee_scolaire,
    groupe: p.groupe || null,
    mois: Number(p.mois),
    date_debut,
    date_fin,
    label,
    sessions
  };
}

async function saveWeek(existingWeekId, weekIdx) {
  setMsg('edtMsg','');

  const payload = collectWeekPayload(weekIdx, existingWeekId || null);

  if (!payload.date_debut || !payload.date_fin || !payload.label) {
    setMsg('edtMsg', 'Label + date début + date fin sont obligatoires.', false);
    return;
  }

  const fd = new FormData();
  fd.append('payload', JSON.stringify(payload));

  const res = await fetch('admin_edt_save.php', { method:'POST', body:fd, credentials:'same-origin' });
  const data = await res.json();

  if (!res.ok || !data.success) {
    setMsg('edtMsg', data.message || 'Erreur sauvegarde EDT', false);
    return;
  }

  setMsg('edtMsg', data.message || 'Semaine enregistrée.', true);
  await loadEdtMonth(); // refresh depuis base
}

async function deleteWeek(weekId) {
  if (!confirm('Supprimer cette semaine et toutes ses séances ?')) return;

  const fd = new FormData();
  fd.append('week_id', weekId);

  const res = await fetch('admin_edt_week_delete.php', { method:'POST', body:fd, credentials:'same-origin' });
  const data = await res.json();

  if (!res.ok || !data.success) {
    setMsg('edtMsg', data.message || 'Erreur suppression semaine', false);
    return;
  }

  setMsg('edtMsg', data.message || 'Semaine supprimée.', true);
  await loadEdtMonth();
}

/* ====================== FIN NOUVEAU : EMPLOI DU TEMPS (JS) ====================== */


/* ====================== FIN NOUVEAU ====================== */

window.addEventListener('click', (e) => {
    const modals = {
        studentModal: closeStudentModal,
        teacherModal: closeTeacherModal,
        confirmModal: closeConfirmModal,
        affectModal: closeAffectModal,
        rejectModal: closeRejectModal,
        validateReservationModal: closeValidateReservationModal,
        // NOUVEAU
        docRejectModal: closeDocRejectModal,
        docValidateModal: closeDocValidateModal
    };

    for (const [id, closeFn] of Object.entries(modals)) {
        if (e.target.id === id) closeFn();
    }
});

// ⚠️ ton code avait ce doublon, je le garde EXACTEMENT (logique existante)
async function initSalles() {
    await loadAdminReservations();
}

initSalles();
</script>

</body>
</html>
