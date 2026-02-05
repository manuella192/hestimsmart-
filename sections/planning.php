<?php
// ===========================
// FICHIER: sections/planning.php  (ETUDIANT - VERSION ASYNC)
// ===========================
session_start();

if (!isset($_SESSION['etudiant_id'])) {
  http_response_code(401);
  echo "<p>Session expirée. Veuillez vous reconnecter.</p>";
  exit;
}

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Slots fixes
$days = ["Lundi","Mardi","Mercredi","Jeudi","Vendredi"];
$slots = [
  "M1" => ["label"=>"09:00 — 10:45"],
  "M2" => ["label"=>"11:00 — 12:30"],
  "A1" => ["label"=>"13:30 — 15:15"],
  "A2" => ["label"=>"15:30 — 17:00"],
];

// petite fonction pour retrouver index jour (Lun=0..Ven=4)
function dayIndex($isoDate){
  $n = (int)date('N', strtotime($isoDate)); // 1=lundi ... 7=dimanche
  return $n - 1;
}

// Choix semaine via ?week_id=...
$weekId = (int)($_GET['week_id'] ?? 0);

require_once __DIR__ . '/../db.php';

$etudiant_id = (int)$_SESSION['etudiant_id'];

// 1) inscription (filiere/niveau/annee)
$stmt = $conn->prepare("
  SELECT filiere_id, niveau_id, annee_scolaire
  FROM inscriptions
  WHERE etudiant_id=:eid
  ORDER BY date_inscription DESC
  LIMIT 1
");
$stmt->execute([':eid'=>$etudiant_id]);
$insc = $stmt->fetch(PDO::FETCH_ASSOC);

$week = null;
$sessions = [];
$prevWeekId = null;
$nextWeekId = null;

$weeksList = [];   // toutes les semaines dispo (pour dropdown)
$totalWeeks = 0;   // total
$currentIndex = 0; // position (1..total)

if ($insc) {
  $filiere_id = (int)$insc['filiere_id'];
  $niveau_id  = (int)$insc['niveau_id'];
  $annee      = trim($insc['annee_scolaire']);

  // 2) Charger semaine
  if ($weekId > 0) {
    // sécurité: week_id doit correspondre à filiere/niveau/annee
    $wStmt = $conn->prepare("
      SELECT *
      FROM edt_weeks
      WHERE id=:wid AND filiere_id=:f AND niveau_id=:n AND annee_scolaire=:a
      LIMIT 1
    ");
    $wStmt->execute([':wid'=>$weekId, ':f'=>$filiere_id, ':n'=>$niveau_id, ':a'=>$annee]);
    $week = $wStmt->fetch(PDO::FETCH_ASSOC);
    if (!$week) $weekId = 0;
  }

  if ($weekId <= 0) {
    // semaine courante
    $wStmt = $conn->prepare("
      SELECT *
      FROM edt_weeks
      WHERE filiere_id=:f AND niveau_id=:n AND annee_scolaire=:a
        AND CURDATE() BETWEEN date_debut AND date_fin
      ORDER BY date_debut DESC
      LIMIT 1
    ");
    $wStmt->execute([':f'=>$filiere_id, ':n'=>$niveau_id, ':a'=>$annee]);
    $week = $wStmt->fetch(PDO::FETCH_ASSOC);

    // fallback: dernière semaine existante
    if (!$week) {
      $wStmt2 = $conn->prepare("
        SELECT *
        FROM edt_weeks
        WHERE filiere_id=:f AND niveau_id=:n AND annee_scolaire=:a
        ORDER BY date_debut DESC
        LIMIT 1
      ");
      $wStmt2->execute([':f'=>$filiere_id, ':n'=>$niveau_id, ':a'=>$annee]);
      $week = $wStmt2->fetch(PDO::FETCH_ASSOC);
    }
  }

  // 3) Liste des semaines (dropdown + X/Y)
  $lStmt = $conn->prepare("
    SELECT id, label, date_debut, date_fin
    FROM edt_weeks
    WHERE filiere_id=:f AND niveau_id=:n AND annee_scolaire=:a
    ORDER BY date_debut ASC
  ");
  $lStmt->execute([':f'=>$filiere_id, ':n'=>$niveau_id, ':a'=>$annee]);
  $weeksList = $lStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  $totalWeeks = count($weeksList);

  if ($week && $totalWeeks > 0) {
    foreach ($weeksList as $idx => $w) {
      if ((int)$w['id'] === (int)$week['id']) {
        $currentIndex = $idx + 1; // 1..N
        break;
      }
    }
  }

  // 4) Sessions + prev/next
  if ($week) {
    $sStmt = $conn->prepare("
      SELECT
        s.jour_date, s.slot,
        c.nom AS cours_nom,
        CONCAT_WS(' ', e.PRENOM, e.NOM) AS enseignant_nom,
        sa.batiment, sa.nom AS salle_nom
      FROM edt_sessions s
      INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
      INNER JOIN cours c ON c.id = ea.cours_id
      INNER JOIN enseignant e ON e.`ID-ENSEIGNANT` = ea.enseignant_id
      INNER JOIN salles sa ON sa.id = s.salle_id
      WHERE s.week_id = :wid
      ORDER BY s.jour_date, FIELD(s.slot,'M1','M2','A1','A2')
    ");
    $sStmt->execute([':wid'=>(int)$week['id']]);
    $sessions = $sStmt->fetchAll(PDO::FETCH_ASSOC);

    // prev: date_fin < date_debut actuelle
    $pStmt = $conn->prepare("
      SELECT id
      FROM edt_weeks
      WHERE filiere_id=:f AND niveau_id=:n AND annee_scolaire=:a
        AND date_fin < :cur_start
      ORDER BY date_debut DESC
      LIMIT 1
    ");
    $pStmt->execute([
      ':f'=>$filiere_id, ':n'=>$niveau_id, ':a'=>$annee,
      ':cur_start'=>$week['date_debut']
    ]);
    $prevWeekId = $pStmt->fetchColumn() ?: null;

    // next: date_debut > date_fin actuelle
    $nStmt = $conn->prepare("
      SELECT id
      FROM edt_weeks
      WHERE filiere_id=:f AND niveau_id=:n AND annee_scolaire=:a
        AND date_debut > :cur_end
      ORDER BY date_debut ASC
      LIMIT 1
    ");
    $nStmt->execute([
      ':f'=>$filiere_id, ':n'=>$niveau_id, ':a'=>$annee,
      ':cur_end'=>$week['date_fin']
    ]);
    $nextWeekId = $nStmt->fetchColumn() ?: null;
  }
}

// 5) Construire $events[slot][dayIndex]
$events = [];
foreach ($slots as $k => $_) $events[$k] = array_fill(0, 5, null);

foreach ($sessions as $s) {
  $i = dayIndex($s['jour_date']);
  if ($i < 0 || $i > 4) continue;
  $slot = $s['slot'];
  if (!isset($events[$slot])) continue;

  $events[$slot][$i] = [
    'matiere' => $s['cours_nom'],
    'type'    => $slot,
    'prof'    => $s['enseignant_nom'],
    'salle'   => $s['batiment'] . ' - ' . $s['salle_nom'],
    'color'   => 'blue'
  ];
}

$weekLabel = $week
  ? ($week['label'] ?: ('Semaine du ' . $week['date_debut'] . ' au ' . $week['date_fin']))
  : "Aucune semaine trouvée";
?>

<link rel="stylesheet" href="css/planning.css">

<div class="header" style="margin-bottom: 16px; margin-top: 16px;">
  <h2 style="margin:0;">Planning</h2>
</div>

<section class="emploi-container">
  <div class="planning-topbar">
    <div>
      <div class="planning-week" id="weekLabel"><?= esc($weekLabel) ?></div>

      <div style="margin-top:6px;color:#666;font-size:0.95em;" id="weekCounter">
        <?php if ($totalWeeks > 0 && $week): ?>
          Semaine <?= (int)$currentIndex ?> / <?= (int)$totalWeeks ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="planning-actions" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
      <!-- PREV (AJAX) -->
      <button class="btn-ghost" type="button"
              id="btnPrev"
              data-week="<?= (int)($prevWeekId ?: 0) ?>"
              <?= $prevWeekId ? '' : 'disabled' ?>>
        ← Semaine précédente
      </button>

      <!-- NEXT (AJAX) -->
      <button class="btn-ghost" type="button"
              id="btnNext"
              data-week="<?= (int)($nextWeekId ?: 0) ?>"
              <?= $nextWeekId ? '' : 'disabled' ?>>
        Semaine suivante →
      </button>

      <!-- DROPDOWN (AJAX) -->
      <?php if ($totalWeeks > 0): ?>
        <select class="filter-select" id="weekSelect" style="min-width:260px;">
          <?php foreach ($weeksList as $w):
            $lab = $w['label'] ?: ('Semaine du '.$w['date_debut'].' au '.$w['date_fin']);
            $selected = ($week && (int)$w['id'] === (int)$week['id']) ? 'selected' : '';
          ?>
            <option value="<?= (int)$w['id'] ?>" <?= $selected ?>>
              <?= esc($lab) ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </div>
  </div>

  <div class="planning-scroll">
    <div class="planning-grid">
      <div class="grid-head grid-corner">Horaires</div>
      <?php foreach ($days as $d): ?>
        <div class="grid-head"><?= esc($d) ?></div>
      <?php endforeach; ?>

      <?php foreach ($slots as $slotKey => $slotInfo): ?>
        <div class="grid-time">
          <span class="time-chip"><?= esc($slotInfo['label']) ?></span>
        </div>

        <?php for($i=0;$i<5;$i++): $ev = $events[$slotKey][$i] ?? null; ?>
          <div class="grid-cell" data-slot="<?= esc($slotKey) ?>" data-day="<?= (int)$i ?>">
            <?php if($ev): ?>
              <div class="cours-cell cours-<?= esc($ev['color']) ?>">
                <div class="cours-info">
                  <div class="cours-matiere"><?= esc($ev['matiere']) ?></div>
                  <div class="cours-details">
                    <span><?= esc($ev['type']) ?></span>
                    <span><?= esc($ev['salle']) ?></span>
                  </div>
                  <div class="cours-prof">👤 <?= esc($ev['prof']) ?></div>
                </div>
              </div>
            <?php else: ?>
              <div class="empty-slot">—</div>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if(!$week): ?>
    <div style="padding:12px;color:#b30000;">Aucun emploi du temps enregistré pour ta filière/niveau.</div>
  <?php endif; ?>
</section>

<script>
(function(){
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const weekSelect = document.getElementById('weekSelect');
  const weekLabelEl = document.getElementById('weekLabel');
  const weekCounterEl = document.getElementById('weekCounter');

  function escHtml(str){
    return String(str ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function dayIndex(isoDate){
    // isoDate = "YYYY-MM-DD"
    const d = new Date(isoDate + 'T00:00:00');
    // JS: 0=dim ... 6=sam ; on veut Lun=0..Ven=4
    const n = d.getDay(); // lun=1 ... ven=5
    return n - 1;
  }

  function clearGrid(){
    document.querySelectorAll('.grid-cell').forEach(cell => {
      cell.innerHTML = `<div class="empty-slot">—</div>`;
    });
  }

  function renderEvent(slot, dayIdx, ev){
    const cell = document.querySelector(`.grid-cell[data-slot="${slot}"][data-day="${dayIdx}"]`);
    if(!cell) return;

    cell.innerHTML = `
      <div class="cours-cell cours-blue">
        <div class="cours-info">
          <div class="cours-matiere">${escHtml(ev.cours_nom)}</div>
          <div class="cours-details">
            <span>${escHtml(ev.slot)}</span>
            <span>${escHtml(ev.batiment)} - ${escHtml(ev.salle_nom)}</span>
          </div>
          <div class="cours-prof">👤 ${escHtml(ev.enseignant_nom)}</div>
        </div>
      </div>
    `;
  }

  function setNav(prevId, nextId){
    if(btnPrev){
      btnPrev.dataset.week = prevId ? String(prevId) : '';
      btnPrev.disabled = !prevId;
    }
    if(btnNext){
      btnNext.dataset.week = nextId ? String(nextId) : '';
      btnNext.disabled = !nextId;
    }
  }

  function computePrevNextFromSelect(currentWeekId){
    if(!weekSelect) return {prev:null, next:null, idx:0, total:0};

    const ids = Array.from(weekSelect.options).map(o => Number(o.value));
    const idx = ids.indexOf(Number(currentWeekId));
    const prev = (idx > 0) ? ids[idx-1] : null;
    const next = (idx >= 0 && idx < ids.length-1) ? ids[idx+1] : null;
    return {prev, next, idx: (idx>=0?idx:0), total: ids.length};
  }

  async function loadWeek(weekId, pushUrl=true){
    clearGrid();
    if(weekLabelEl) weekLabelEl.textContent = 'Chargement...';
    if(weekCounterEl) weekCounterEl.textContent = '';

    const url = new URL('etudiant_edt_week_get.php', window.location.href);
    if(weekId) url.searchParams.set('week_id', String(weekId));

    const res = await fetch(url.toString(), { credentials:'same-origin' });
    const raw = await res.text();

    let data;
    try { data = JSON.parse(raw); } catch(e){ data = null; }

    if(!res.ok || !data || !data.success){
      if(weekLabelEl) weekLabelEl.textContent = 'Erreur de chargement';
      console.error(raw);
      return;
    }

    const payload = data.data || {};
    const week = payload.week;
    const sessions = payload.sessions || [];

    if(!week){
      if(weekLabelEl) weekLabelEl.textContent = "Aucune semaine trouvée";
      setNav(null, null);
      return;
    }

    const label = week.label ? week.label : `Semaine du ${week.date_debut} au ${week.date_fin}`;
    if(weekLabelEl) weekLabelEl.textContent = label;

    if(weekSelect) weekSelect.value = String(week.id);

    // prev/next + compteur (calculé depuis le select existant)
    const nav = computePrevNextFromSelect(week.id);
    setNav(nav.prev, nav.next);
    if(weekCounterEl && nav.total){
      weekCounterEl.textContent = `Semaine ${nav.idx + 1} / ${nav.total}`;
    }

    // sessions
    sessions.forEach(s => {
      const i = dayIndex(s.jour_date);
      if(i < 0 || i > 4) return;
      renderEvent(s.slot, i, s);
    });

    // URL sans reload (pour copier/coller et navigation back/forward)
    if(pushUrl){
      const newUrl = new URL(window.location.href);
      newUrl.searchParams.set('week_id', String(week.id));
      history.pushState({week_id: Number(week.id)}, '', newUrl.toString());
    }
  }

  // UI events
  if(btnPrev){
    btnPrev.addEventListener('click', () => {
      const id = Number(btnPrev.dataset.week || 0);
      if(id) loadWeek(id);
    });
  }
  if(btnNext){
    btnNext.addEventListener('click', () => {
      const id = Number(btnNext.dataset.week || 0);
      if(id) loadWeek(id);
    });
  }
  if(weekSelect){
    weekSelect.addEventListener('change', () => {
      const id = Number(weekSelect.value || 0);
      if(id) loadWeek(id);
    });
  }

  // back/forward
  window.addEventListener('popstate', (e) => {
    const wid = (e.state && e.state.week_id) ? Number(e.state.week_id) : null;
    loadWeek(wid, false);
  });

  // Optionnel:
  // Si tu veux forcer un rendu AJAX au premier affichage (même si PHP a déjà rendu),
  // décommente la ligne ci-dessous :
  // const initId = Number(new URLSearchParams(window.location.search).get('week_id') || (weekSelect ? weekSelect.value : 0));
  // if(initId) loadWeek(initId, false);

})();
</script>
