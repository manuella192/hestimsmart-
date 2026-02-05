<?php
// ===========================
// FICHIER: sections/planning_enseignant.php (ou include dans portail_enseignant)
// VERSION ASYNC (sans reload) + affiche 👤 Nom enseignant (TOUJOURS, même en navigation)
// IMPORTANT: nécessite le backend JSON "enseignant_edt_week_get.php" qui renvoie aussi enseignant_nom
// ===========================

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['enseignant_id'])) {
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

function dayIndex($isoDate){
  $n = (int)date('N', strtotime($isoDate)); // 1=lun ... 7=dim
  return $n - 1;
}

// week_id initial (si présent)
$weekId = (int)($_GET['week_id'] ?? 0);

require_once __DIR__ . '/../db.php';

$enseignant_id = (int)$_SESSION['enseignant_id'];

// Nom enseignant initial (pour rendu SSR)
$enseignant_nom = trim(($_SESSION['enseignant_prenom'] ?? '') . ' ' . ($_SESSION['enseignant_nom'] ?? ''));
if ($enseignant_nom === '') {
  try {
    $t = $conn->prepare("SELECT CONCAT_WS(' ', PRENOM, NOM) AS nom FROM enseignant WHERE `ID-ENSEIGNANT`=:id LIMIT 1");
    $t->execute([':id'=>$enseignant_id]);
    $enseignant_nom = (string)($t->fetchColumn() ?: '');
  } catch (Throwable $e) {
    $enseignant_nom = '';
  }
}

$week = null;
$sessions = [];
$prevWeekId = null;
$nextWeekId = null;

$weeksList = [];
$totalWeeks = 0;
$currentIndex = 0;

/**
 * 1) Liste des semaines où cet enseignant a au moins 1 séance
 */
$lStmt = $conn->prepare("
  SELECT DISTINCT w.id, w.label, w.date_debut, w.date_fin
  FROM edt_weeks w
  INNER JOIN edt_sessions s ON s.week_id = w.id
  INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
  WHERE ea.enseignant_id = :eid
  ORDER BY w.date_debut ASC
");
$lStmt->execute([':eid'=>$enseignant_id]);
$weeksList = $lStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
$totalWeeks = count($weeksList);

$allowedWeekIds = array_map(fn($w)=> (int)$w['id'], $weeksList);

/**
 * 2) Choisir la semaine
 */
if ($totalWeeks > 0) {
  if ($weekId > 0 && in_array($weekId, $allowedWeekIds, true)) {
    $wStmt = $conn->prepare("SELECT * FROM edt_weeks WHERE id=:wid LIMIT 1");
    $wStmt->execute([':wid'=>$weekId]);
    $week = $wStmt->fetch(PDO::FETCH_ASSOC);
  }

  if (!$week) {
    // semaine courante accessible
    $wStmt = $conn->prepare("
      SELECT w.*
      FROM edt_weeks w
      INNER JOIN edt_sessions s ON s.week_id = w.id
      INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
      WHERE ea.enseignant_id = :eid
        AND CURDATE() BETWEEN w.date_debut AND w.date_fin
      ORDER BY w.date_debut DESC
      LIMIT 1
    ");
    $wStmt->execute([':eid'=>$enseignant_id]);
    $week = $wStmt->fetch(PDO::FETCH_ASSOC);

    // fallback: dernière accessible
    if (!$week) {
      $wStmt2 = $conn->prepare("
        SELECT w.*
        FROM edt_weeks w
        INNER JOIN edt_sessions s ON s.week_id = w.id
        INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
        WHERE ea.enseignant_id = :eid
        ORDER BY w.date_debut DESC
        LIMIT 1
      ");
      $wStmt2->execute([':eid'=>$enseignant_id]);
      $week = $wStmt2->fetch(PDO::FETCH_ASSOC);
    }
  }
}

/**
 * 3) Index + prev/next DANS la liste accessible (weeksList)
 */
if ($week && $totalWeeks > 0) {
  foreach ($weeksList as $idx => $w) {
    if ((int)$w['id'] === (int)$week['id']) {
      $currentIndex = $idx + 1;
      $prevWeekId = ($idx > 0) ? (int)$weeksList[$idx-1]['id'] : null;
      $nextWeekId = ($idx < $totalWeeks-1) ? (int)$weeksList[$idx+1]['id'] : null;
      break;
    }
  }
}

/**
 * 4) Sessions filtrées pour CET enseignant + semaine choisie
 */
if ($week) {
  $sStmt = $conn->prepare("
    SELECT
      s.jour_date, s.slot,
      c.nom AS cours_nom,
      sa.batiment, sa.nom AS salle_nom
    FROM edt_sessions s
    INNER JOIN enseignant_affectations ea ON ea.id = s.affectation_id
    INNER JOIN cours c ON c.id = ea.cours_id
    INNER JOIN salles sa ON sa.id = s.salle_id
    WHERE s.week_id = :wid
      AND ea.enseignant_id = :eid
    ORDER BY s.jour_date, FIELD(s.slot,'M1','M2','A1','A2')
  ");
  $sStmt->execute([':wid'=>(int)$week['id'], ':eid'=>$enseignant_id]);
  $sessions = $sStmt->fetchAll(PDO::FETCH_ASSOC);
}

// 5) Construire events[slot][dayIndex]
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
    'prof'    => $enseignant_nom, // 👤 Nom enseignant (SSR)
    'salle'   => $s['batiment'] . ' - ' . $s['salle_nom'],
    'color'   => 'blue'
  ];
}

$weekLabel = $week
  ? ($week['label'] ?: ('Semaine du ' . $week['date_debut'] . ' au ' . $week['date_fin']))
  : "Aucune semaine trouvée";
?>

<link rel="stylesheet" href="css/planning.css">

<section class="emploi-container" id="planningEnseignantRoot">
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
                  <?php if(!empty($ev['prof'])): ?>
                    <div class="cours-prof">👤 <?= esc($ev['prof']) ?></div>
                  <?php endif; ?>
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
    <div style="padding:12px;color:#b30000;">Aucun emploi du temps enregistré pour vous.</div>
  <?php endif; ?>
</section>

<script>
(function(){
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const weekSelect = document.getElementById('weekSelect');
  const weekLabelEl = document.getElementById('weekLabel');
  const weekCounterEl = document.getElementById('weekCounter');

  // ✅ garde une "source de vérité" du nom enseignant (SSR -> puis mise à jour via API)
  let TEACHER_NAME = <?= json_encode($enseignant_nom, JSON_UNESCAPED_UNICODE) ?>;

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
    const n = d.getDay(); // 0=dim..6=sam ; lun=1..ven=5
    return n - 1;         // lun=>0 .. ven=>4
  }

  function clearGrid(){
    document.querySelectorAll('#planningEnseignantRoot .grid-cell').forEach(cell => {
      cell.innerHTML = `<div class="empty-slot">—</div>`;
    });
  }

  function renderEvent(slot, dayIdx, s){
    const cell = document.querySelector(`#planningEnseignantRoot .grid-cell[data-slot="${slot}"][data-day="${dayIdx}"]`);
    if(!cell) return;

    const profName = (s.enseignant_nom && String(s.enseignant_nom).trim()) ? s.enseignant_nom : TEACHER_NAME;

    cell.innerHTML = `
      <div class="cours-cell cours-blue">
        <div class="cours-info">
          <div class="cours-matiere">${escHtml(s.cours_nom)}</div>
          <div class="cours-details">
            <span>${escHtml(s.slot)}</span>
            <span>${escHtml(s.batiment)} - ${escHtml(s.salle_nom)}</span>
          </div>
          ${profName ? `<div class="cours-prof">👤 ${escHtml(profName)}</div>` : ``}
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

    const url = new URL('enseignant_edt_week_get.php', window.location.href);
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

    // ✅ IMPORTANT: le backend renvoie enseignant_nom -> on le conserve
    if (payload.enseignant_nom && String(payload.enseignant_nom).trim()) {
      TEACHER_NAME = String(payload.enseignant_nom).trim();
    }

    if(!week){
      if(weekLabelEl) weekLabelEl.textContent = "Aucune semaine trouvée";
      setNav(null, null);
      return;
    }

    const label = week.label ? week.label : `Semaine du ${week.date_debut} au ${week.date_fin}`;
    if(weekLabelEl) weekLabelEl.textContent = label;

    if(weekSelect) weekSelect.value = String(week.id);

    // prev/next + compteur (calculé depuis le select)
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

    // URL sans reload
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
})();
</script>
