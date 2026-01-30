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
    <button class="nav-btn" onclick="showMainSection('etudiants', event)">Étudiants Inscrits</button>
    <button class="nav-btn" onclick="showMainSection('cours', event)">Gestion Cours</button>
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

<div id="etudiants-section" style="display:none;">
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
    document.getElementById('etudiants-section').style.display = 'none';
    document.getElementById('cours-section').style.display = 'none';

    if (section === 'salles') {
        document.getElementById('salles-section').style.display = 'block';
        initSalles();
    }
    if (section === 'etudiants') {
        document.getElementById('etudiants-section').style.display = 'block';
        initStudents();
    }
    if (section === 'cours') {
        document.getElementById('cours-section').style.display = 'block';
        initCours();
    }
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

async function initSalles() {
    await loadAdminReservations();
}

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
                r.salle_nom, r.batiment, r.cours_nom, r.niveau, r.enseignant_nom, r.enseignant_email, r.motif, r.motif_autre
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
                <small>
                    Enseignant: ${escapeHtml(r.enseignant_nom || '-')} (${escapeHtml(r.enseignant_email || '-')})
                </small>
                ${r.rejet_motif ? `<br><small style="color:#b30000;">Motif rejet: ${escapeHtml(r.rejet_motif)}</small>` : ''}
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

window.addEventListener('click', (e) => {
    const modals = {
        studentModal: closeStudentModal,
        confirmModal: closeConfirmModal,
        affectModal: closeAffectModal,
        rejectModal: closeRejectModal,
        validateReservationModal: closeValidateReservationModal
    };

    for (const [id, closeFn] of Object.entries(modals)) {
        if (e.target.id === id) closeFn();
    }
});

async function initSalles() {
    await loadAdminReservations();
}

initSalles();
</script>

</body>
</html>