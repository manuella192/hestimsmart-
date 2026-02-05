<?php
// ===========================
// FICHIER: portail_enseignant.php
// ===========================
session_start();
if (!isset($_SESSION['enseignant_id'])) {
    header("Location: login-enseignant.html");
    exit;
}

$enseignantId = (int)$_SESSION['enseignant_id'];
$prenom = $_SESSION['enseignant_prenom'] ?? '';
$nom = $_SESSION['enseignant_nom'] ?? '';
$fullName = trim($prenom . ' ' . $nom);

// Année scolaire utilisée côté requêtes (tu peux la mettre en session)
$anneeScolaire = $_SESSION['annee_scolaire'] ?? '2025-2026';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portail Enseignant</title>
    <link rel="stylesheet" href="portails.css">
</head>
<body>

<header class="header">
    <img src="images/logo-hestim.png" class="logo" alt="logo">
    <h1>Bienvenue sur le portail enseignant<?= $fullName ? ' : ' . htmlspecialchars($fullName) : '' ?></h1>
    <button class="logout-btn" onclick="location.href='deconnexion.php'">Déconnexion</button>
</header>

<nav class="navbar">
    <button class="nav-btn active" onclick="showMainSection('emploi', event)">Salle / Emploi du Temps</button>
    <button class="nav-btn" onclick="showMainSection('etudiants', event)">Étudiants Inscrits</button>
</nav>

<!-- ==========================
     SECTION EMPLOI / RESERVATIONS
=========================== -->
<div id="emploi-section">
    <div class="sub-menu">
        <button class="sub-btn active" onclick="showSubSection('emploi-temps', event)">Mon emploi du temps</button>
        <button class="sub-btn" onclick="showSubSection('reservations', event)">Réservation des salles</button>
    </div>

    <!-- RESERVATIONS (ENSEIGNANT) -->
    <section class="reservations-container" id="reservations-section" style="display: none;">
        <div class="reservations-header">
            <h2>Mes Réservations</h2>
            <button class="btn-add-reservation" onclick="openReservationModal()">+ Nouvelle Réservation</button>
        </div>
        <div id="reservations-list" class="reservations-list"></div>
    </section>

    <!-- EMPLOI DU TEMPS -->
    <section class="emploi-container" id="emploi-temps-section">
        <?php include __DIR__ . '/sections/planning_enseignant.php'; ?>
    </section>
</div>

<!-- ==========================
     SECTION ETUDIANTS INSCRITS (dynamique)
=========================== -->
<div id="etudiants-section" style="display: none;">
    <section class="content">
        <div class="page-header">
            <h2>Mes Étudiants Inscrits</h2>

            <!-- Sous-menu: Liste / Présence -->
            <div class="sub-menu" style="margin-top:10px;">
                <button class="sub-btn active" onclick="showEtudiantsSubSection('liste', event)">Liste</button>
                <button class="sub-btn" onclick="showEtudiantsSubSection('presence', event)">Présence</button>
            </div>

            <div class="search-filter-bar" style="margin-top:10px;">
                <input type="text" id="searchEtudiant" class="search-input" placeholder="Rechercher un étudiant...">

                <!-- On remplit cette liste avec les affectations réelles -->
                <select id="filterCours" class="filter-select">
                    <option value="">Chargement des cours...</option>
                </select>
            </div>

            <div id="etudiantsMsg" style="margin-top:10px;"></div>
        </div>

        <!-- Vue LISTE -->
        <div id="etudiants-liste-view">
            <div class="students-table-container">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Cours</th>
                            <th>Filière</th>
                            <th>Niveau</th>
                            <th>Groupe</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="etudiantsTableBody">
                        <tr><td colspan="7" style="text-align:center;padding:16px;color:#666;">Sélectionnez un cours pour afficher les étudiants.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vue PRESENCE -->
        <div id="etudiants-presence-view" style="display:none;">
            <div style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; margin: 10px 0;">
                <div class="form-group" style="min-width:260px;">
                    <label>Séance (date/heure) *</label>
                    <input type="datetime-local" id="presence_datetime" required>
                    <small id="presenceDateErr" style="display:none;margin-top:6px;color:#b30000;"></small>
                </div>

                <div class="form-group" style="min-width:220px;">
                    <label>Soumission</label>
                    <button class="btn-add-reservation" type="button" onclick="submitPresence()">Enregistrer la présence</button>
                </div>
            </div>

            <div id="presenceMsg" style="margin:10px 0;"></div>

            <div class="students-table-container">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Cours</th>
                            <th>Filière</th>
                            <th>Niveau</th>
                            <th>Groupe</th>
                            <th>Présence</th>
                        </tr>
                    </thead>
                    <tbody id="presenceTableBody">
                        <tr><td colspan="7" style="text-align:center;padding:16px;color:#666;">Sélectionnez un cours pour afficher les étudiants.</td></tr>
                    </tbody>
                </table>
            </div>

            <small style="display:block;margin-top:8px;color:#666;">
                Astuce : vous pouvez changer Présent/Absent autant de fois que nécessaire avant de soumettre.
            </small>
        </div>
    </section>
</div>

<!-- ==========================
     MODAL INFOS ETUDIANT (remplace l'alerte)
=========================== -->
<div id="studentInfoModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Informations de l'étudiant</h3>
            <span class="close-modal" onclick="closeStudentInfoModal()">&times;</span>
        </div>

        <div style="padding: 10px 0;">
            <div style="display:grid;grid-template-columns: 1fr 2fr;gap:8px 12px;">
                <strong>Nom complet</strong><span id="info_nom_complet">-</span>
                <strong>Email</strong><span id="info_email">-</span>
                <strong>Cours</strong><span id="info_cours">-</span>
                <strong>Filière</strong><span id="info_filiere">-</span>
                <strong>Niveau</strong><span id="info_niveau">-</span>
                <strong>Groupe</strong><span id="info_groupe">-</span>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn-cancel" onclick="closeStudentInfoModal()">Fermer</button>
            <button type="button" class="btn-submit" onclick="contactFromInfoModal()">Contacter</button>
        </div>
    </div>
</div>

<!-- ==========================
     MODAL RESERVATION (MODIFIÉ)
=========================== -->
<div id="reservationModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Nouvelle Réservation</h3>
            <span class="close-modal" onclick="closeReservationModal()">&times;</span>
        </div>

        <form id="reservationForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Bâtiment *</label>
                    <select id="batiment" required>
                        <option value="">Sélectionnez</option>
                        <option value="A">Bâtiment A</option>
                        <option value="B">Bâtiment B</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Type *</label>
                    <select id="typeSalle" required>
                        <option value="">Sélectionnez</option>
                        <option value="amphi">Amphi</option>
                        <option value="salle">Salle</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Salle/Amphi *</label>
                    <select id="salle_id" required>
                        <option value="">Sélectionnez d'abord bâtiment + type</option>
                    </select>
                    <small id="salleInfo" style="display:block; margin-top:6px; color:#666;"></small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nom du cours *</label>
                    <!-- Auto depuis l'affectation (désactivé) -->
                    <input type="text" id="cours_nom" required disabled>
                    <small id="coursHelp" style="display:block; margin-top:6px; color:#666;">
                        Le cours est automatiquement défini selon votre affectation.
                    </small>
                </div>

                <div class="form-group">
                    <label>Niveau étudiants *</label>
                    <!-- Liste plus sûre -->
                    <select id="niveau" required>
                        <option value="">Sélectionnez</option>
                        <option value="1ère année">1ère année</option>
                        <option value="2ème année">2ème année</option>
                        <option value="3ème année">3ème année</option>
                        <option value="4ème année">4ème année</option>
                        <option value="5ème année">5ème année</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Effectif *</label>
                    <input type="number" id="effectif" required min="1" max="300">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Motif *</label>
                    <select id="motif" required>
                        <option value="cours">Cours</option>
                        <option value="td">TD</option>
                        <option value="tp">TP</option>
                        <option value="reunion">Réunion</option>
                        <option value="examen">Examen</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="form-group" id="motifAutreWrap" style="display:none;">
                    <label>Motif (Autre) *</label>
                    <input type="text" id="motif_autre" placeholder="Précisez le motif">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Date/heure début *</label>
                    <input type="datetime-local" id="date_debut" required>
                    <small id="dateDebutErr" style="display:none;margin-top:6px;color:#b30000;"></small>
                </div>
                <div class="form-group">
                    <label>Date/heure fin *</label>
                    <input type="datetime-local" id="date_fin" required>
                    <small id="dateFinErr" style="display:none;margin-top:6px;color:#b30000;"></small>
                </div>
            </div>

            <div id="reservationMsg" style="margin:10px 0;"></div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeReservationModal()">Annuler</button>
                <button type="submit" class="btn-submit">Envoyer</button>
            </div>
        </form>
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
    <div class="footer-bottom"></div>
</footer>

<script>
/* ========= CONFIG ========= */
const ANNEE_SCOLAIRE = <?= json_encode($anneeScolaire) ?>;
const ENSEIGNANT_ID = <?= (int)$enseignantId ?>;

/* ========= NAVIGATION ========= */
function showMainSection(section, event) {
    document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
    if (event?.target) event.target.classList.add('active');

    document.getElementById('emploi-section').style.display = 'none';
    document.getElementById('etudiants-section').style.display = 'none';

    if (section === 'emploi') document.getElementById('emploi-section').style.display = 'block';
    if (section === 'etudiants') {
        document.getElementById('etudiants-section').style.display = 'block';
        initEtudiantsSection();
    }
}

function showSubSection(subsection, event) {
    document.querySelectorAll('.sub-btn').forEach(btn => btn.classList.remove('active'));
    if (event?.target) event.target.classList.add('active');

    if (subsection === 'reservations') {
        document.getElementById('reservations-section').style.display = 'block';
        document.getElementById('emploi-temps-section').style.display = 'none';
        loadMyReservations();
    } else {
        document.getElementById('reservations-section').style.display = 'none';
        document.getElementById('emploi-temps-section').style.display = 'block';
    }
}

/* ========= Sous-sections Etudiants ========= */
let ETUDIANTS_VIEW = 'liste'; // 'liste' | 'presence'
function showEtudiantsSubSection(sub, event) {
    document.querySelectorAll('#etudiants-section .sub-menu .sub-btn').forEach(btn => btn.classList.remove('active'));
    if (event?.target) event.target.classList.add('active');

    ETUDIANTS_VIEW = sub;

    document.getElementById('etudiants-liste-view').style.display = (sub === 'liste') ? 'block' : 'none';
    document.getElementById('etudiants-presence-view').style.display = (sub === 'presence') ? 'block' : 'none';

    // Rendu selon la vue actuelle
    renderEtudiants();
}

/* ========= ETUDIANTS INSCRITS ========= */
let AFFECTATIONS = [];      // affectations (cours + filiere + niveau + groupe + affectation_id + (id cours si dispo))
let ETUDIANTS_CACHE = [];   // derniers étudiants chargés
let CURRENT_AFFECTATION_ID = null;
let CURRENT_COURS_ID = null;

// Brouillon de présence (modifiable avant soumission)
// Map: etudiant_id => 1/0
let PRESENCE_DRAFT = new Map();

// Pour modal infos
let CURRENT_INFO_EMAIL = '';

async function initEtudiantsSection() {
    const select = document.getElementById('filterCours');
    const msg = document.getElementById('etudiantsMsg');
    msg.textContent = '';
    msg.style.color = '#666';

    if (AFFECTATIONS.length) return;

    select.innerHTML = `<option value="">Chargement...</option>`;

    const res = await fetch('enseignant_cours_list.php?annee_scolaire=' + encodeURIComponent(ANNEE_SCOLAIRE), {
        credentials: 'same-origin'
    });
    const data = await res.json();

    if (!res.ok || !data.success) {
        select.innerHTML = `<option value="">Erreur de chargement</option>`;
        msg.style.color = '#b30000';
        msg.textContent = data.message || "Impossible de charger vos cours.";
        return;
    }

    AFFECTATIONS = data.data || [];

    if (!AFFECTATIONS.length) {
        select.innerHTML = `<option value="">Aucun cours affecté</option>`;
        msg.style.color = '#b30000';
        msg.textContent = "Aucune affectation trouvée. Demandez à l'admin de vous affecter à un cours.";
        setEmptyEtudiantsTables("Aucun étudiant à afficher.");
        return;
    }

    select.innerHTML = `<option value="">Sélectionnez un cours</option>`;
    AFFECTATIONS.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.affectation_id;
        opt.textContent = `${a.cours_nom} — ${a.filiere_nom} / ${a.niveau_libelle}${a.groupe ? ' (' + a.groupe + ')' : ''}`;
        select.appendChild(opt);
    });

    // Auto: première affectation
    select.value = String(AFFECTATIONS[0].affectation_id);
    await loadEtudiantsByAffectation(select.value);

    // Init date/heure de séance par défaut = maintenant (local)
    initPresenceDatetimeDefault();
}

document.getElementById('filterCours').addEventListener('change', async (e) => {
    const affectId = e.target.value;
    if (!affectId) {
        setEmptyEtudiantsTables("Sélectionnez un cours pour afficher les étudiants.");
        ETUDIANTS_CACHE = [];
        PRESENCE_DRAFT = new Map();
        CURRENT_AFFECTATION_ID = null;
        CURRENT_COURS_ID = null;
        return;
    }
    await loadEtudiantsByAffectation(affectId);
});

async function loadEtudiantsByAffectation(affectationId) {
    CURRENT_AFFECTATION_ID = String(affectationId);

    // récupérer cours_id si l'API enseignant_cours_list.php le fournit (sinon backend le résoudra)
    const a = AFFECTATIONS.find(x => String(x.affectation_id) === String(CURRENT_AFFECTATION_ID));
    CURRENT_COURS_ID = a?.cours_id ? String(a.cours_id) : null;

    const tbody = document.getElementById('etudiantsTableBody');
    const pbody = document.getElementById('presenceTableBody');
    const msg = document.getElementById('etudiantsMsg');

    msg.textContent = '';
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:16px;color:#666;">Chargement...</td></tr>`;
    pbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:16px;color:#666;">Chargement...</td></tr>`;

    const url = new URL('enseignant_etudiants_list.php', window.location.href);
    url.searchParams.set('affectation_id', affectationId);
    url.searchParams.set('annee_scolaire', ANNEE_SCOLAIRE);

    const res = await fetch(url.toString(), { credentials: 'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        const err = escapeHtml(data.message || 'Erreur de chargement');
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:16px;color:#b30000;">${err}</td></tr>`;
        pbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:16px;color:#b30000;">${err}</td></tr>`;
        ETUDIANTS_CACHE = [];
        PRESENCE_DRAFT = new Map();
        return;
    }

    const rows = data.data || [];
    ETUDIANTS_CACHE = rows;

    // (Re)initialise le draft de présence sur les étudiants chargés
    PRESENCE_DRAFT = new Map();
    rows.forEach(r => {
        const sid = getStudentId(r);
        if (sid !== null) PRESENCE_DRAFT.set(String(sid), 0); // défaut absent (plus sûr)
    });

    renderEtudiants();
}

document.getElementById('searchEtudiant')?.addEventListener('input', renderEtudiants);

function setEmptyEtudiantsTables(text) {
    document.getElementById('etudiantsTableBody').innerHTML =
        `<tr><td colspan="7" style="text-align:center;padding:16px;color:#666;">${escapeHtml(text)}</td></tr>`;
    document.getElementById('presenceTableBody').innerHTML =
        `<tr><td colspan="7" style="text-align:center;padding:16px;color:#666;">${escapeHtml(text)}</td></tr>`;
}

function getFilteredEtudiants() {
    const term = (document.getElementById('searchEtudiant').value || '').toLowerCase().trim();
    let rows = ETUDIANTS_CACHE.slice();

    if (term) {
        rows = rows.filter(r => {
            const blob = `${r.nom_complet||''} ${r.email||''} ${r.cours||''} ${r.filiere||''} ${r.niveau||''} ${r.groupe||''}`.toLowerCase();
            return blob.includes(term);
        });
    }
    return rows;
}

function renderEtudiants() {
    // si pas de données
    if (!ETUDIANTS_CACHE.length) {
        setEmptyEtudiantsTables("Sélectionnez un cours pour afficher les étudiants.");
        return;
    }

    const rows = getFilteredEtudiants();

    renderEtudiantsListe(rows);
    renderEtudiantsPresence(rows);
}

function renderEtudiantsListe(rows) {
    const tbody = document.getElementById('etudiantsTableBody');

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:16px;color:#666;">Aucun étudiant trouvé</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    rows.forEach(r => {
        const tr = document.createElement('tr');
        const sid = getStudentId(r); // peut être null si l'API ne renvoie pas l'ID

        tr.innerHTML = `
            <td>${escapeHtml(r.nom_complet)}</td>
            <td>${escapeHtml(r.email)}</td>
            <td>${escapeHtml(r.cours)}</td>
            <td>${escapeHtml(r.filiere)}</td>
            <td>${escapeHtml(r.niveau)}</td>
            <td>${escapeHtml(r.groupe || '-')}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-view" onclick="openStudentInfoModal(${JSON.stringify(safeStudentForModal(r)).replaceAll('"', '&quot;')})">Voir</button>
                    <button class="btn-edit" onclick="contacterEtudiant('${escapeJs(r.email)}')">Contacter</button>
                </div>
                ${sid === null ? `<small style="display:block;margin-top:6px;color:#b30000;">ID étudiant manquant (présence non disponible)</small>` : ``}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderEtudiantsPresence(rows) {
    const tbody = document.getElementById('presenceTableBody');

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:16px;color:#666;">Aucun étudiant trouvé</td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    rows.forEach(r => {
        const tr = document.createElement('tr');
        const sid = getStudentId(r);

        // si pas d'ID -> on bloque la saisie de présence (sinon on ne peut pas enregistrer)
        if (sid === null) {
            tr.innerHTML = `
                <td>${escapeHtml(r.nom_complet)}</td>
                <td>${escapeHtml(r.email)}</td>
                <td>${escapeHtml(r.cours)}</td>
                <td>${escapeHtml(r.filiere)}</td>
                <td>${escapeHtml(r.niveau)}</td>
                <td>${escapeHtml(r.groupe || '-')}</td>
                <td><small style="color:#b30000;">ID manquant</small></td>
            `;
            tbody.appendChild(tr);
            return;
        }

        const key = String(sid);
        const current = PRESENCE_DRAFT.has(key) ? PRESENCE_DRAFT.get(key) : 0;

        tr.innerHTML = `
            <td>${escapeHtml(r.nom_complet)}</td>
            <td>${escapeHtml(r.email)}</td>
            <td>${escapeHtml(r.cours)}</td>
            <td>${escapeHtml(r.filiere)}</td>
            <td>${escapeHtml(r.niveau)}</td>
            <td>${escapeHtml(r.groupe || '-')}</td>
            <td>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button"
                            class="btn-submit"
                            style="padding:6px 10px; ${current === 1 ? '' : 'opacity:0.55;'}"
                            onclick="setPresence('${escapeJs(key)}', 1)">
                        Présent
                    </button>
                    <button type="button"
                            class="btn-cancel"
                            style="padding:6px 10px; ${current === 0 ? '' : 'opacity:0.55;'}"
                            onclick="setPresence('${escapeJs(key)}', 0)">
                        Absent
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function setPresence(studentId, value) {
    PRESENCE_DRAFT.set(String(studentId), Number(value));
    // re-render seulement la vue présence (rapide et fiable)
    renderEtudiantsPresence(getFilteredEtudiants());
}

/* ========= MODAL INFOS ========= */
function safeStudentForModal(r) {
    return {
        nom_complet: r.nom_complet || '',
        email: r.email || '',
        cours: r.cours || '',
        filiere: r.filiere || '',
        niveau: r.niveau || '',
        groupe: r.groupe || ''
    };
}

function openStudentInfoModal(studentObj) {
    // studentObj arrive déjà “safe”
    document.getElementById('info_nom_complet').textContent = studentObj.nom_complet || '-';
    document.getElementById('info_email').textContent = studentObj.email || '-';
    document.getElementById('info_cours').textContent = studentObj.cours || '-';
    document.getElementById('info_filiere').textContent = studentObj.filiere || '-';
    document.getElementById('info_niveau').textContent = studentObj.niveau || '-';
    document.getElementById('info_groupe').textContent = studentObj.groupe || '-';

    CURRENT_INFO_EMAIL = studentObj.email || '';
    document.getElementById('studentInfoModal').style.display = 'flex';
}

function closeStudentInfoModal() {
    document.getElementById('studentInfoModal').style.display = 'none';
}

function contactFromInfoModal() {
    if (!CURRENT_INFO_EMAIL) return;
    window.location.href = `mailto:${CURRENT_INFO_EMAIL}`;
}

/* ========= PRESENCE (soumission) ========= */
function initPresenceDatetimeDefault() {
    // valeur par défaut = now local (arrondie à la minute)
    const el = document.getElementById('presence_datetime');
    if (!el) return;
    if (el.value) return;

    const now = new Date();
    now.setSeconds(0, 0);
    el.value = toDatetimeLocalValue(now);
}

function toDatetimeLocalValue(d) {
    // "YYYY-MM-DDTHH:MM"
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function parseLocalDatetime(value) {
    if (!value) return null;
    const d = new Date(value);
    return isNaN(d.getTime()) ? null : d;
}

function setPresenceMsg(text, ok=false) {
    const el = document.getElementById('presenceMsg');
    el.style.color = ok ? 'green' : '#b30000';
    el.textContent = text || '';
}

function setPresenceDateError(message) {
    const el = document.getElementById('presenceDateErr');
    if (!message) {
        el.style.display = 'none';
        el.textContent = '';
        return;
    }
    el.style.display = 'block';
    el.textContent = message;
}

function validatePresenceDatetime() {
    setPresenceDateError('');

    const v = document.getElementById('presence_datetime').value;
    const d = parseLocalDatetime(v);
    if (!d) {
        setPresenceDateError("Veuillez sélectionner une date/heure valide pour la séance.");
        return false;
    }
    return true;
}

async function submitPresence() {
    setPresenceMsg('');
    if (!CURRENT_AFFECTATION_ID) {
        setPresenceMsg("Veuillez d'abord sélectionner un cours.");
        return;
    }
    if (!ETUDIANTS_CACHE.length) {
        setPresenceMsg("Aucun étudiant à enregistrer.");
        return;
    }
    if (!validatePresenceDatetime()) {
        setPresenceMsg("Veuillez corriger la date/heure de la séance.");
        return;
    }

    // Construire la liste
    const payloadRows = [];
    let missingIds = 0;

    ETUDIANTS_CACHE.forEach(r => {
        const sid = getStudentId(r);
        if (sid === null) { missingIds++; return; }
        const key = String(sid);
        const present = PRESENCE_DRAFT.has(key) ? PRESENCE_DRAFT.get(key) : 0;
        payloadRows.push({ etudiant_id: Number(sid), present: Number(present) });
    });

    if (missingIds > 0) {
        setPresenceMsg("Impossible d'enregistrer : certains étudiants n'ont pas d'ID (vérifier l'API enseignant_etudiants_list.php).");
        return;
    }

    if (!payloadRows.length) {
        setPresenceMsg("Aucune ligne de présence à enregistrer.");
        return;
    }

    const seance = document.getElementById('presence_datetime').value;

    // Envoi JSON
    const body = {
        affectation_id: Number(CURRENT_AFFECTATION_ID),
        cours_id: CURRENT_COURS_ID ? Number(CURRENT_COURS_ID) : null,
        annee_scolaire: ANNEE_SCOLAIRE,
        presence_at: seance, // "YYYY-MM-DDTHH:MM"
        rows: payloadRows
    };

    const res = await fetch('enseignant_presence_submit.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
    credentials: 'same-origin'
    });

    // Lire en texte d'abord pour ne pas perdre les erreurs PHP (HTML/warnings)
    const rawText = await res.text();

    let data = null;
    try {
        data = JSON.parse(rawText);
    } catch (e) {
        data = null;
    }

    if (!res.ok || !data || !data.success) {
        const msgErr = (data && data.message)
            ? data.message
            : (rawText ? rawText.substring(0, 800) : "Erreur lors de l'enregistrement de la présence.");
        setPresenceMsg(msgErr);
        return;
    }

    setPresenceMsg(data.message || "Présence enregistrée avec succès.", true);

    }

/* ========= IMPORTANT: extraction ID étudiant depuis l'API =========
   L'API enseignant_etudiants_list.php doit renvoyer l'id étudiant.
   On accepte plusieurs clés possibles pour être tolérant.
*/
function getStudentId(row) {
    // Ton API renvoie "etudiant_id" => c'est la voie normale.
    // On garde aussi une compatibilité avec d'autres clés, y compris celles avec tiret.
    const v =
        (row && (
            row.etudiant_id ??
            row.id_etudiant ??
            row.student_id ??
            row.id ??
            row['ID-ETUDIANT'] ??   // clé potentielle avec tiret
            row.ID_ETUDIANT
        )) ?? null;

    if (v === null || v === undefined || v === '') return null;

    const n = Number(v);
    return Number.isFinite(n) ? n : null;
}


/* ========= Réservations ========= */
let SALLES = [];

function getCurrentCourseNameForReservation() {
    if (CURRENT_AFFECTATION_ID) {
        const a = AFFECTATIONS.find(x => String(x.affectation_id) === String(CURRENT_AFFECTATION_ID));
        if (a?.cours_nom) return a.cours_nom;
    }
    if (AFFECTATIONS.length && AFFECTATIONS[0]?.cours_nom) return AFFECTATIONS[0].cours_nom;
    return '';
}

async function ensureAffectationsLoaded() {
  if (AFFECTATIONS.length) return true;

  try {
    const res = await fetch(
      'enseignant_cours_list.php?annee_scolaire=' + encodeURIComponent(ANNEE_SCOLAIRE),
      { credentials: 'same-origin' }
    );

    const raw = await res.text();
    let data = null;
    try { data = JSON.parse(raw); } catch(e) { data = null; }

    if (!res.ok || !data || !data.success) {
      console.error('enseignant_cours_list.php error:', raw);
      return false;
    }

    AFFECTATIONS = data.data || [];

    // Si rien sélectionné, on prend la 1ère affectation (utile pour réservation)
    if (!CURRENT_AFFECTATION_ID && AFFECTATIONS.length) {
      CURRENT_AFFECTATION_ID = String(AFFECTATIONS[0].affectation_id);
      CURRENT_COURS_ID = AFFECTATIONS[0].cours_id ? String(AFFECTATIONS[0].cours_id) : null;
    }

    return true;
  } catch (e) {
    console.error(e);
    return false;
  }
}


async function openReservationModal() {
  document.getElementById('reservationMsg').textContent = '';
  document.getElementById('reservationMsg').style.color = '#b30000';

  clearDateErrors();

  document.getElementById('reservationForm').reset();
  document.getElementById('motifAutreWrap').style.display = 'none';
  document.getElementById('salle_id').innerHTML = `<option value="">Sélectionnez d'abord bâtiment + type</option>`;
  document.getElementById('salleInfo').textContent = '';

  // ✅ IMPORTANT: charger les affectations même si on n'a jamais ouvert "Etudiants"
  const ok = await ensureAffectationsLoaded();

  const courseName = ok ? getCurrentCourseNameForReservation() : '';
  document.getElementById('cours_nom').value = courseName;

  if (!courseName) {
    document.getElementById('coursHelp').style.color = '#b30000';
    document.getElementById('coursHelp').textContent =
      ok
        ? "Aucune affectation trouvée. Demandez à l'admin de vous affecter à un cours."
        : "Impossible de charger vos affectations (vérifiez l'API enseignant_cours_list.php).";
  } else {
    document.getElementById('coursHelp').style.color = '#666';
    document.getElementById('coursHelp').textContent =
      "Le cours est automatiquement défini selon votre affectation.";
  }

  document.getElementById('niveau').value = '';

  document.getElementById('reservationModal').style.display = 'flex';

  // salles
  await ensureSallesLoaded();
}


function closeReservationModal() {
    document.getElementById('reservationModal').style.display = 'none';
}

async function ensureSallesLoaded() {
    if (SALLES.length) return;
    const res = await fetch('salles_list.php', { credentials: 'same-origin' });
    const data = await res.json();
    if (data.success) SALLES = data.data;
}

function renderSallesSelect() {
    const bat = document.getElementById('batiment').value;
    const typ = document.getElementById('typeSalle').value;
    const select = document.getElementById('salle_id');

    select.innerHTML = `<option value="">Sélectionnez</option>`;
    document.getElementById('salleInfo').textContent = '';

    if (!bat || !typ) {
        select.innerHTML = `<option value="">Sélectionnez d'abord bâtiment + type</option>`;
        return;
    }

    const filtered = SALLES.filter(s => s.batiment === bat && s.type === typ);
    filtered.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `${s.nom} (Bât. ${s.batiment})`;
        opt.dataset.info = `Étage: ${s.etage} | Taille: ${s.taille} | Places: ${s.capacite}`;
        select.appendChild(opt);
    });

    if (filtered.length === 0) select.innerHTML = `<option value="">Aucune salle trouvée</option>`;
}

document.getElementById('batiment').addEventListener('change', renderSallesSelect);
document.getElementById('typeSalle').addEventListener('change', renderSallesSelect);

document.getElementById('salle_id').addEventListener('change', (e) => {
    const opt = e.target.selectedOptions[0];
    document.getElementById('salleInfo').textContent = opt?.dataset?.info || '';
});

document.getElementById('motif').addEventListener('change', () => {
    const v = document.getElementById('motif').value;
    const wrap = document.getElementById('motifAutreWrap');
    const input = document.getElementById('motif_autre');
    if (v === 'autre') { wrap.style.display = 'block'; input.required = true; }
    else { wrap.style.display = 'none'; input.required = false; input.value = ''; }
});

/* ===== VALIDATION DATES (front) ===== */
const dateDebutEl = document.getElementById('date_debut');
const dateFinEl = document.getElementById('date_fin');
dateDebutEl.addEventListener('change', validateDatesLive);
dateFinEl.addEventListener('change', validateDatesLive);

function clearDateErrors() {
    const a = document.getElementById('dateDebutErr');
    const b = document.getElementById('dateFinErr');
    a.style.display = 'none'; a.textContent = '';
    b.style.display = 'none'; b.textContent = '';
}

function setDateError(which, message) {
    const el = (which === 'debut') ? document.getElementById('dateDebutErr') : document.getElementById('dateFinErr');
    el.style.display = 'block';
    el.textContent = message;
}

function validateDatesLive() {
    clearDateErrors();

    const d1 = parseLocalDatetime(dateDebutEl.value);
    const d2 = parseLocalDatetime(dateFinEl.value);

    if (!d1 || !d2) return true;

    if (d2.getTime() <= d1.getTime()) {
        setDateError('fin', "La date/heure de fin doit être postérieure à la date/heure de début.");
        return false;
    }
    return true;
}

document.getElementById('reservationForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const msg = document.getElementById('reservationMsg');
    msg.style.color = '#b30000';
    msg.textContent = '';

    const coursNom = document.getElementById('cours_nom').value.trim();
    if (!coursNom) {
        msg.textContent = "Impossible d'envoyer : aucun cours n'est associé à votre compte (affectation manquante).";
        return;
    }

    if (!validateDatesLive()) {
        msg.textContent = "Veuillez corriger les dates avant d'envoyer la demande.";
        return;
    }

    const payload = new FormData();
    payload.append('salle_id', document.getElementById('salle_id').value);
    payload.append('cours_nom', coursNom);
    payload.append('niveau', document.getElementById('niveau').value);
    payload.append('effectif', document.getElementById('effectif').value);
    payload.append('motif', document.getElementById('motif').value);
    payload.append('motif_autre', document.getElementById('motif_autre').value);
    payload.append('date_debut', document.getElementById('date_debut').value);
    payload.append('date_fin', document.getElementById('date_fin').value);

    const res = await fetch('enseignant_reservation_create.php', {
        method: 'POST',
        body: payload,
        credentials: 'same-origin'
    });

    const data = await res.json();

    if (!res.ok || !data.success) {
        msg.textContent = data.message || "Erreur lors de l'envoi.";
        return;
    }

    msg.style.color = 'green';
    msg.textContent = data.message || 'Demande envoyée.';

    closeReservationModal();
    await loadMyReservations();
});

/* ========= LISTE RESERVATIONS ========= */
function statusUi(statut) {
    if (statut === 'validee') return {label: 'Confirmée', cls: 'status-confirmed'};
    if (statut === 'en_attente') return {label: 'En attente', cls: 'status-pending'};
    if (statut === 'rejetee') return {label: 'Rejetée', cls: 'status-cancelled'};
    if (statut === 'annulee') return {label: 'Annulée', cls: 'status-cancelled'};
    return {label: statut, cls: 'status-pending'};
}

function fmtDateTime(sqlDt) {
    const d = new Date(String(sqlDt).replace(' ', 'T'));
    return d.toLocaleString('fr-FR', { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit' });
}

async function loadMyReservations() {
    const list = document.getElementById('reservations-list');
    list.innerHTML = '<p style="padding:10px;color:#666;">Chargement...</p>';

    const res = await fetch('enseignant_reservations_list.php', { credentials: 'same-origin' });
    const data = await res.json();

    if (!res.ok || !data.success) {
        list.innerHTML = `<p style="padding:10px;color:#b30000;">${escapeHtml(data.message || 'Erreur de chargement')}</p>`;
        return;
    }

    if (!data.data.length) {
        list.innerHTML = '<p style="text-align:center; padding: 20px; color: #666;">Aucune réservation pour le moment</p>';
        return;
    }

    list.innerHTML = '';
    data.data.forEach(r => {
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
                ${r.rejet_motif ? `<br><small style="color:#b30000;">Motif rejet: ${escapeHtml(r.rejet_motif)}</small>` : ''}
            </div>
            <div class="reservation-actions">
                ${r.statut === 'en_attente' ? `<button class="btn-delete" onclick="cancelReservation(${r.id})">Annuler</button>` : ''}
            </div>
        `;
        list.appendChild(item);
    });
}

async function cancelReservation(id) {
    if (!confirm('Annuler cette demande ?')) return;
    const fd = new FormData();
    fd.append('id', id);

    const res = await fetch('enseignant_reservation_cancel.php', { method:'POST', body: fd, credentials:'same-origin' });
    const data = await res.json();
    if (!res.ok || !data.success) {
        alert(data.message || "Erreur lors de l'annulation.");
        return;
    }
    await loadMyReservations();
}

/* ========= UTILITAIRES ========= */
function escapeHtml(str) {
    return String(str ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}
function escapeJs(str) {
    return String(str ?? '').replaceAll("\\", "\\\\").replaceAll("'", "\\'");
}

/* ========= ACTIONS ========= */
function contacterEtudiant(email) { window.location.href = `mailto:${email}`; }

/* ========= Fermer modals en cliquant dehors ========= */
window.addEventListener('click', (e) => {
    const modalRes = document.getElementById('reservationModal');
    const modalInfo = document.getElementById('studentInfoModal');
    if (e.target === modalRes) closeReservationModal();
    if (e.target === modalInfo) closeStudentInfoModal();
});
</script>

</body>
</html>
