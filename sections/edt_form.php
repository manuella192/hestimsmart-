<?php
// sections/edt_form.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
  echo "<p style='color:#b30000'>Session admin expirée</p>";
  return;
}
?>

<link rel="stylesheet" href="css/planning.css">
<!-- Option : crée un edt_admin.css pour regrouper les styles ci-dessous -->

<div id="edt-section" style="display:none;">
  <div id="edt-admin-wrap" class="emploi-container" style="margin-top:20px; max-width:1600px; margin-left:auto; margin-right:auto;">

    <div class="planning-topbar">
      <div>
        <h2 class="planning-title" style="margin:0;color:#0b2d72;">Gestion Emploi du temps</h2>
        <div class="planning-week" style="margin-top:6px;">Créer/éditer les semaines d’un mois (slots M1/M2/A1/A2)</div>
      </div>
    </div>

    <div class="search-filter-bar" style="align-items:flex-end; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
      <div class="form-group" style="min-width:220px;">
        <label>Filière *</label>
        <select id="edt_filiere" class="filter-select"></select>
      </div>
      <div class="form-group" style="min-width:220px;">
        <label>Niveau *</label>
        <select id="edt_niveau" class="filter-select"></select>
      </div>
      <div class="form-group" style="min-width:170px;">
        <label>Année scolaire *</label>
        <input id="edt_annee" class="search-input" value="2025-2026" />
      </div>
      <div class="form-group" style="min-width:150px;">
        <label>Mois *</label>
        <select id="edt_mois" class="filter-select">
          <option value="1">Janvier</option><option value="2">Février</option><option value="3">Mars</option>
          <option value="4">Avril</option><option value="5">Mai</option><option value="6">Juin</option>
          <option value="7">Juillet</option><option value="8">Août</option><option value="9">Septembre</option>
          <option value="10">Octobre</option><option value="11">Novembre</option><option value="12">Décembre</option>
        </select>
      </div>
      <button class="btn-add-reservation" type="button" onclick="EDT.loadMonth()">Charger</button>
      <button class="btn-add-reservation" type="button" onclick="EDT.addWeek()">+ Ajouter semaine</button>
    </div>

    <div id="edtMsg" style="margin:12px 0; min-height:24px;"></div>

    <!-- Conteneur principal des semaines (vertical) -->
    <div id="edtWeeksWrap" style="
      display: flex;
      flex-direction: column;
      gap: 20px;
      margin-top: 8px;
    "></div>

  </div>
</div>

<script>
// ────────────────────────────────────────────────
const EDT = {
  ref: { filieres:[], niveaux:[], salles:[] },
  affectations: [],
  weeks: [],
  monthSessionsFlat: [],

  esc(s){ return String(s??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;"); },

  setMsg(txt, ok=false){
    const el = document.getElementById('edtMsg');
    el.style.color = ok ? '#006600' : '#b30000';
    el.textContent = txt || '';
  },

  qs(params){
    const u = new URLSearchParams();
    Object.entries(params).forEach(([k,v]) => u.append(k, v ?? ''));
    return u.toString();
  },

  params(){
    return {
      filiere_id: document.getElementById('edt_filiere').value,
      niveau_id: document.getElementById('edt_niveau').value,
      annee_scolaire: document.getElementById('edt_annee').value.trim(),
      mois: document.getElementById('edt_mois').value
    };
  },

  async init(){
    this.setMsg('');
    const res = await fetch('admin_edt_referentials.php', { credentials:'same-origin' });
    const data = await res.json().catch(()=>null);
    if (!res.ok || !data?.success){
      this.setMsg(data?.message || 'Erreur chargement référentiels');
      return;
    }
    this.ref = data.data || this.ref;
    this.fillSelect('edt_filiere', this.ref.filieres, x=>x.id, x=>x.nom, 'Choisir filière');
    this.fillSelect('edt_niveau', this.ref.niveaux, x=>x.id, x=>x.libelle, 'Choisir niveau');

    const now = new Date();
    document.getElementById('edt_mois').value = String(now.getMonth()+1);

    document.getElementById('edt_filiere').onchange = () => this.clear();
    document.getElementById('edt_niveau').onchange = () => this.clear();
  },

  fillSelect(id, items, getVal, getLab, placeholder){
    const sel = document.getElementById(id);
    sel.innerHTML = `<option value="">${placeholder}</option>` + (items||[]).map(it =>
      `<option value="${this.esc(getVal(it))}">${this.esc(getLab(it))}</option>`
    ).join('');
  },

  clear(){
    this.weeks = [];
    this.monthSessionsFlat = [];
    document.getElementById('edtWeeksWrap').innerHTML = '';
  },

  buildDays(dateDebut, dateFin){
    const iso = d => {
      const pad=n=>String(n).padStart(2,'0');
      return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    };
    const d0 = new Date(dateDebut+'T00:00:00');
    const d1 = new Date(dateFin+'T00:00:00');
    const days = [];
    let cur = new Date(d0);
    while(cur <= d1 && days.length < 5){
      days.push({ jour_date: iso(cur), sessions: this.defaultSessions(iso(cur)) });
      cur.setDate(cur.getDate()+1);
    }
    return days;
  },

  defaultSessions(dayIso){
    return ['M1','M2','A1','A2'].map(slot => ({
      jour_date: dayIso, slot, affectation_id:'', salle_id:'', notes:''
    }));
  },

  hydrateWeeks(weeks, sessions){
    const map = {};
    (sessions||[]).forEach(s=>{
      const k = `${s.week_id}|${s.jour_date}|${s.slot}`;
      map[k] = s;
    });
    return (weeks||[]).map(w=>{
      const days = this.buildDays(w.date_debut, w.date_fin).map(d=>{
        const sessionsSlots = ['M1','M2','A1','A2'].map(slot=>{
          const k = `${w.id}|${d.jour_date}|${slot}`;
          const s = map[k];
          return {
            jour_date: d.jour_date,
            slot,
            affectation_id: s ? String(s.affectation_id) : '',
            salle_id: s ? String(s.salle_id) : '',
            notes: s?.notes || ''
          };
        });
        return { jour_date: d.jour_date, sessions: sessionsSlots };
      });
      return { id: w.id, label: w.label || '', date_debut: w.date_debut, date_fin: w.date_fin, mois: w.mois, days };
    });
  },

  async loadAffectations(){
    const p = this.params();
    if(!p.filiere_id || !p.niveau_id || !p.annee_scolaire) return [];
    const res = await fetch('admin_edt_affectations_list.php?' + this.qs(p), { credentials:'same-origin' });
    const data = await res.json().catch(()=>null);
    return (res.ok && data?.success) ? (data.data||[]) : [];
  },

  async loadMonth(){
    this.setMsg('');
    const p = this.params();
    if(!p.filiere_id || !p.niveau_id || !p.annee_scolaire || !p.mois){
      this.setMsg('Veuillez choisir Filière + Niveau + Année scolaire + Mois');
      return;
    }
    this.affectations = await this.loadAffectations();
    const res = await fetch('admin_edt_month_get.php?' + this.qs(p), { credentials:'same-origin' });
    const data = await res.json().catch(()=>null);
    if(!res.ok || !data?.success){
      this.clear();
      this.setMsg(data?.message || 'Erreur chargement mois');
      return;
    }
    this.weeks = this.hydrateWeeks(data.data?.weeks || [], data.data?.sessions || []);
    this.monthSessionsFlat = data.data?.sessions || [];
    this.renderWeeks();
    this.setMsg('Mois chargé.', true);
  },

  addWeek(){
    const p = this.params();
    if(!p.filiere_id || !p.niveau_id || !p.annee_scolaire || !p.mois){
      this.setMsg('Choisis Filière + Niveau + Année scolaire + Mois, puis clique sur Charger.');
      return;
    }
    this.weeks.push({
      id: null,
      label: '',
      date_debut: '',
      date_fin: '',
      mois: Number(p.mois),
      days: []
    });
    this.renderWeeks();
  },

  frLabel(isoDate){
    const d = new Date(isoDate+'T00:00:00');
    return d.toLocaleDateString('fr-FR', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
  },

  renderWeeks(){
    const wrap = document.getElementById('edtWeeksWrap');
    wrap.innerHTML = '';
    if(!this.weeks.length){
      wrap.innerHTML = `<div style="padding:24px; color:#777; text-align:center; font-size:1.1em;">
        Aucune semaine pour le moment. Clique sur « + Ajouter semaine »
      </div>`;
      return;
    }
    this.weeks.forEach((w, idx) => {
      wrap.appendChild(this.buildWeekCard(w, idx));
    });
  },

  buildWeekCard(week, idx){
    const card = document.createElement('div');
    card.style.cssText = `
      background: #ffffff;
      border: 1px solid #e0e0e0;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
      overflow: hidden;
    `;

    card.innerHTML = `
      <div style="padding:16px 20px; background:#f9f9fb; border-bottom:1px solid #e8e8e8;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div>
            <strong style="font-size:1.15em;">Semaine ${idx+1}</strong>
            ${week.id ? `<span style="margin-left:14px; color:#555; font-size:0.95em;">#${this.esc(week.id)}</span>` : `<span style="margin-left:14px; color:#777; font-size:0.95em;">(nouvelle)</span>`}
          </div>
          <div style="display:flex; gap:12px;">
            <button class="btn-edit" type="button" onclick="EDT.saveWeek(${idx})">Enregistrer</button>
            ${week.id ? `<button class="btn-delete" type="button" onclick="EDT.deleteWeek(${week.id})">Supprimer</button>` : ''}
          </div>
        </div>

        <div style="display:flex; gap:14px; margin-top:14px; flex-wrap:wrap;">
          <div class="form-group" style="flex:2; min-width:300px;">
            <label>Label semaine *</label>
            <input class="search-input" id="edt_week_label_${idx}" value="${this.esc(week.label||'')}" placeholder="Ex: Semaine du 03 au 07 Février 2026" />
          </div>
          <div class="form-group" style="min-width:180px;">
            <label>Date début (lundi) *</label>
            <input type="date" class="search-input" id="edt_week_start_${idx}" value="${this.esc(week.date_debut||'')}"/>
          </div>
          <div class="form-group" style="min-width:180px;">
            <label>Date fin (vendredi) *</label>
            <input type="date" class="search-input" id="edt_week_end_${idx}" value="${this.esc(week.date_fin||'')}"/>
          </div>
          <div style="align-self:flex-end; padding-bottom:2px;">
            <button class="btn-add-reservation" type="button" onclick="EDT.genWeekDays(${idx})">Générer Lun→Ven + slots</button>
          </div>
        </div>
      </div>

      <!-- ZONE SCROLLABLE HORIZONTALE → seulement pour les jours -->
      <div style="
        overflow-x: auto;
        overflow-y: hidden;
        padding: 0 20px 20px 20px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
      ">
        <div id="edt_week_days_${idx}" style="
          display: inline-flex;
          flex-direction: row;
          gap: 16px;
          min-width: 100%;
          padding: 20px 0 4px 0;
        "></div>
      </div>
    `;

    this.renderDays(idx);
    return card;
  },

  genWeekDays(idx){
    this.setMsg('');
    const start = document.getElementById(`edt_week_start_${idx}`).value;
    const end   = document.getElementById(`edt_week_end_${idx}`).value;
    const label = document.getElementById(`edt_week_label_${idx}`).value.trim();

    if(!label || !start || !end){
      this.setMsg('Label + date début + date fin obligatoires.');
      return;
    }

    const d0 = new Date(start+'T00:00:00');
    const d1 = new Date(end+'T00:00:00');
    if(d1 < d0){
      this.setMsg('Date fin doit être >= date début.');
      return;
    }

    this.weeks[idx].label      = label;
    this.weeks[idx].date_debut = start;
    this.weeks[idx].date_fin   = end;
    this.weeks[idx].days       = this.buildDays(start, end);
    this.renderDays(idx);
    this.setMsg('Jours et créneaux générés.', true);
  },

  renderDays(weekIdx){
    const wrap = document.getElementById(`edt_week_days_${weekIdx}`);
    if(!wrap) return;

    const week = this.weeks[weekIdx];
    const days = week.days || [];

    if(!days.length){
      wrap.innerHTML = `<div style="padding:20px; color:#777; text-align:center; min-width:600px;">
        Clique sur « Générer Lun→Ven + slots »
      </div>`;
      return;
    }

    const affOptions = (this.affectations||[]).map(a => ({
      val: String(a.affectation_id || a.id),
      lab: `${a.cours_nom || 'Cours'} — ${a.enseignant_nom || 'Enseignant'}`
    }));

    const salleOptions = (this.ref.salles||[]).map(s => ({
      val: String(s.id),
      lab: `${s.batiment} - ${s.nom} (${s.type})`
    }));

    wrap.innerHTML = '';
    days.forEach((d, dayIdx) => {
      const block = document.createElement('div');
      block.style.cssText = `
        min-width: 380px;
        max-width: 420px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 14px;
        background: #fdfdfd;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
      `;

      block.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
          <strong style="font-size:1.05em;">${this.esc(this.frLabel(d.jour_date))}</strong>
          <span style="color:#555; font-size:0.9em;">${d.jour_date}</span>
        </div>

        <div style="display:grid; grid-template-columns:1fr; gap:16px;">
          <div>
            <div style="font-weight:600; margin-bottom:8px; color:#333;">Matin</div>
            ${this.slotRowHtml(weekIdx, dayIdx, 'M1', '09:00–10:45')}
            ${this.slotRowHtml(weekIdx, dayIdx, 'M2', '11:00–12:30')}
          </div>
          <div>
            <div style="font-weight:600; margin-bottom:8px; color:#333;">Après-midi</div>
            ${this.slotRowHtml(weekIdx, dayIdx, 'A1', '13:30–15:15')}
            ${this.slotRowHtml(weekIdx, dayIdx, 'A2', '15:30–17:00')}
          </div>
        </div>
      `;

      wrap.appendChild(block);

      const bySlot = {};
      (d.sessions||[]).forEach(s => bySlot[s.slot] = s);

      ['M1','M2','A1','A2'].forEach(slot => {
        const selAff  = document.getElementById(`edt_${weekIdx}_${dayIdx}_${slot}_aff`);
        const selSalle = document.getElementById(`edt_${weekIdx}_${dayIdx}_${slot}_salle`);
        if(!selAff || !selSalle) return;

        selAff.innerHTML  = `<option value="">(vide)</option>` + affOptions.map(o=>`<option value="${this.esc(o.val)}">${this.esc(o.lab)}</option>`).join('');
        selSalle.innerHTML = `<option value="">(vide)</option>` + salleOptions.map(o=>`<option value="${this.esc(o.val)}">${this.esc(o.lab)}</option>`).join('');

        const s = bySlot[slot] || {};
        if(s.affectation_id) selAff.value  = String(s.affectation_id);
        if(s.salle_id)       selSalle.value = String(s.salle_id);
      });
    });
  },

  slotRowHtml(weekIdx, dayIdx, slot, hoursLabel){
    const idAff  = `edt_${weekIdx}_${dayIdx}_${slot}_aff`;
    const idSalle = `edt_${weekIdx}_${dayIdx}_${slot}_salle`;

    return `
      <div style="margin-bottom:12px;">
        <div style="font-size:0.92em; color:#444; margin-bottom:4px;">${this.esc(hoursLabel)}</div>
        <div class="form-group" style="margin:0 0 8px 0;">
          <label style="font-size:0.9em;">Cours / Enseignant</label>
          <select class="filter-select" id="${idAff}"></select>
        </div>
        <div class="form-group" style="margin:0;">
          <label style="font-size:0.9em;">Salle</label>
          <select class="filter-select" id="${idSalle}"></select>
        </div>
      </div>
    `;
  },

  collectPayload(weekIdx){
    const p = this.params();
    const label = document.getElementById(`edt_week_label_${weekIdx}`).value.trim();
    const date_debut = document.getElementById(`edt_week_start_${weekIdx}`).value;
    const date_fin   = document.getElementById(`edt_week_end_${weekIdx}`).value;

    const week = this.weeks[weekIdx];
    const sessions = [];

    (week.days||[]).forEach((d, dayIdx)=>{
      ['M1','M2','A1','A2'].forEach(slot=>{
        const selAff  = document.getElementById(`edt_${weekIdx}_${dayIdx}_${slot}_aff`);
        const selSalle = document.getElementById(`edt_${weekIdx}_${dayIdx}_${slot}_salle`);
        if(!selAff || !selSalle) return;

        const affectation_id = selAff.value  ? Number(selAff.value)  : null;
        const salle_id       = selSalle.value ? Number(selSalle.value) : null;

        if(affectation_id || salle_id){
          sessions.push({ jour_date: d.jour_date, slot, affectation_id, salle_id, notes:'' });
        }
      });
    });

    return {
      week_id: week.id || null,
      filiere_id: Number(p.filiere_id),
      niveau_id: Number(p.niveau_id),
      annee_scolaire: p.annee_scolaire,
      groupe: null,
      mois: Number(p.mois),
      date_debut,
      date_fin,
      label,
      sessions
    };
  },

  async saveWeek(weekIdx){
    this.setMsg('');
    const payload = this.collectPayload(weekIdx);

    if(!payload.label || !payload.date_debut || !payload.date_fin){
      this.setMsg('Label + dates début/fin obligatoires.');
      return;
    }
    if(!payload.sessions.length){
      this.setMsg('Ajoute au moins une séance (cours + salle).');
      return;
    }

    const fd = new FormData();
    fd.append('payload', JSON.stringify(payload));
    fd.append('sync', '1');

    const res = await fetch('admin_edt_save.php', { method:'POST', body: fd, credentials:'same-origin' });
    const data = await res.json().catch(()=>null);

    if(!res.ok || !data?.success){
      this.setMsg(data?.message || 'Erreur sauvegarde');
      return;
    }

    this.setMsg(data.message || 'Semaine enregistrée.', true);
    await this.loadMonth();
  },

  async deleteWeek(weekId){
    if(!confirm('Supprimer cette semaine et toutes ses séances ?')) return;

    const fd = new FormData();
    fd.append('week_id', weekId);

    const res = await fetch('admin_edt_week_delete.php', { method:'POST', body: fd, credentials:'same-origin' });
    const data = await res.json().catch(()=>null);

    if(!res.ok || !data?.success){
      this.setMsg(data?.message || 'Erreur suppression');
      return;
    }

    this.setMsg(data.message || 'Semaine supprimée.', true);
    await this.loadMonth();
  }
};

EDT.init();
</script>