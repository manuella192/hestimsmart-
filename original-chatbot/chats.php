<?php
session_start();
if (!isset($_SESSION['enseignant_id']) && !isset($_SESSION['etudiant_id']) && !isset($_SESSION['admin_id'])) {
  // adapte selon ton système
  // header("Location: login.html"); exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Chat - Assistant</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg: #f5f7fb;
      --card: #ffffff;
      --text: #101828;
      --muted: #667085;
      --border: #e4e7ec;
      --brand: #2563eb;
      --brand-2: #1d4ed8;
      --bot: #f2f4f7;
      --user: #2563eb;
      --userText: #ffffff;
      --danger: #b42318;
      --shadow: 0 10px 30px rgba(16, 24, 40, .08);
      --radius: 18px;
    }

    *{ box-sizing:border-box; font-family: Inter, Arial, sans-serif; }
    body{
      margin:0;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    .page-wrap{
      max-width: 1050px;
      margin: 24px auto;
      padding: 0 16px;
    }

    .header{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:16px;
      margin-bottom: 14px;
    }

    .title{
      display:flex;
      flex-direction:column;
      gap:6px;
    }
    .title h2{
      margin:0;
      font-size: 1.6rem;
      font-weight: 600;
      letter-spacing: .2px;
    }
    .title p{
      margin:0;
      color: var(--muted);
      font-size: .95rem;
    }

    .card{
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow:hidden;
      display:flex;
      flex-direction:column;
      height: calc(100vh - 80px); /* ← fixe la hauteur pour scroll interne */
      max-height: 90vh;
    }

    .card-top{
      padding: 16px 18px;
      border-bottom: 1px solid var(--border);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      background: linear-gradient(180deg, #ffffff, #fbfcff);
    }

    .assistant-badge{
      display:flex;
      align-items:center;
      gap:10px;
    }

    .dot{
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #12b76a;
      box-shadow: 0 0 0 4px rgba(18,183,106,.12);
    }

    .assistant-badge strong{
      font-size: 1rem;
      font-weight: 600;
    }
    .assistant-badge span{
      display:block;
      color: var(--muted);
      font-size: .85rem;
      margin-top:2px;
    }

    .tools{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
    }

    .btn{
      border: 1px solid var(--border);
      background: #fff;
      color: var(--text);
      padding: 8px 12px;
      border-radius: 12px;
      cursor:pointer;
      font-weight: 500;
      transition: .15s ease;
      font-size: .92rem;
    }
    .btn:hover{ transform: translateY(-1px); box-shadow: 0 6px 16px rgba(16,24,40,.08); }
    .btn.primary{
      border-color: transparent;
      background: var(--brand);
      color: #fff;
    }
    .btn.primary:hover{ background: var(--brand-2); }

    .quick{
      padding: 10px 16px;
      border-bottom: 1px solid var(--border);
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      background: #fff;
    }

    .chip{
      background: #eef2ff;
      border: 1px solid #e0e7ff;
      color: #1e3a8a;
      padding: 8px 10px;
      border-radius: 999px;
      cursor:pointer;
      font-size: .9rem;
      transition: .15s ease;
      white-space:nowrap;
    }
    .chip:hover{ background:#e0e7ff; }

    .messages{
      flex: 1;
      padding: 18px;
      overflow-y: auto;           /* ← SCROLL INTERNE ACTIVÉ ICI */
      background: radial-gradient(1200px 500px at 10% 0%, #eef2ff 0%, rgba(238,242,255,0) 55%),
                  radial-gradient(1200px 500px at 90% 0%, #eff6ff 0%, rgba(239,246,255,0) 55%);
    }

    .row{
      display:flex;
      margin-bottom: 12px;
    }
    .row.user{ justify-content:flex-end; }
    .row.bot{ justify-content:flex-start; }

    .bubble{
      max-width: min(720px, 85%);
      border-radius: 16px;
      padding: 12px 14px;
      line-height: 1.45;
      font-size: .98rem;
      border: 1px solid transparent;
      white-space: pre-wrap;
      word-break: break-word;
    }
    .bubble.user{
      background: var(--user);
      color: var(--userText);
      border-bottom-right-radius: 6px;
    }
    .bubble.bot{
      background: var(--bot);
      border-color: var(--border);
      color: var(--text);
      border-bottom-left-radius: 6px;
    }

    .composer{
      padding: 14px;
      border-top: 1px solid var(--border);
      background: #fff;
      display:flex;
      gap:12px;
      align-items:flex-end;
    }

    .input{
      flex:1;
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px 12px;
      min-height: 48px;
      outline:none;
      font-size: 1rem;
      resize:none;
      line-height:1.4;
    }
    .input:focus{ border-color: #c7d2fe; box-shadow: 0 0 0 4px rgba(37,99,235,.12); }

    .send{
      width: 48px;
      height: 48px;
      border-radius: 14px;
      border: none;
      background: var(--brand);
      color:#fff;
      cursor:pointer;
      font-size: 1.1rem;
      transition:.15s ease;
    }
    .send:hover:not(:disabled){ background: var(--brand-2); transform: translateY(-1px); }
    .send:disabled{ background:#98a2b3; cursor:not-allowed; }

    .typing{
      display:inline-flex;
      gap:6px;
      align-items:center;
    }
    .dotty{
      width: 8px; height: 8px; border-radius:50%;
      background:#667085;
      opacity:.6;
      animation: bounce 1.2s infinite ease-in-out;
    }
    .dotty:nth-child(2){ animation-delay:.2s }
    .dotty:nth-child(3){ animation-delay:.4s }
    @keyframes bounce{
      0%, 60%, 100%{ transform: translateY(0); }
      30%{ transform: translateY(-6px); }
    }

    .error{
      color: var(--danger);
      font-weight: 600;
    }

    @media (max-width: 700px){
      .card{ min-height: 85vh; }
      .bubble{ max-width: 92%; }
    }
  </style>
</head>
<body>
  <div class="page-wrap">
    <div class="header">
      <div class="title">
        <h2>Chats</h2>
        <p>Posez une question. L’assistant répond en temps réel.</p>
      </div>
    </div>

    <div class="card">
      <div class="card-top">
        <div class="assistant-badge">
          <div class="dot" aria-hidden="true"></div>
          <div>
            <strong>Assistant pédagogique</strong>
            <span>Conseils, explications, aide sur le portail</span>
          </div>
        </div>

        <div class="tools">
          <button class="btn" id="btn-clear">Effacer</button>
          <button class="btn primary" id="btn-suggest">Suggestion</button>
        </div>
      </div>

      <div class="quick" id="quick">
        <button class="chip" data-q="Explique-moi comment utiliser la section Étudiants inscrits.">Étudiants inscrits</button>
        <button class="chip" data-q="Comment enregistrer une présence correctement ?">Présence</button>
        <button class="chip" data-q="Comment faire une réservation de salle ?">Réservation</button>
        <button class="chip" data-q="Je ne comprends pas une erreur, comment la diagnostiquer ?">Erreurs</button>
      </div>

      <div class="messages" id="messages">
        <div class="row bot">
          <div class="bubble bot">
Bonjour 👋  
Je suis votre assistant. Posez votre question (ex: présence, cours, réservation, portail).
          </div>
        </div>
      </div>

      <div class="composer">
        <textarea id="input" class="input" rows="1" placeholder="Écrivez votre message..." aria-label="Message"></textarea>
        <button id="send" class="send" aria-label="Envoyer" disabled>➤</button>
      </div>
    </div>
  </div>

  <script>
    const messagesEl = document.getElementById('messages');
    const inputEl = document.getElementById('input');
    const sendBtn = document.getElementById('send');
    const clearBtn = document.getElementById('btn-clear');
    const suggestBtn = document.getElementById('btn-suggest');

    function scrollBottom(){
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function addBubble(text, who='bot', isNode=false){
      const row = document.createElement('div');
      row.className = 'row ' + (who === 'user' ? 'user' : 'bot');

      const bubble = document.createElement('div');
      bubble.className = 'bubble ' + (who === 'user' ? 'user' : 'bot');

      if (isNode) bubble.appendChild(text);
      else bubble.textContent = text;

      row.appendChild(bubble);
      messagesEl.appendChild(row);
      scrollBottom();
      return row;
    }

    function typingNode(){
      const wrap = document.createElement('span');
      wrap.className = 'typing';
      wrap.innerHTML = '<span class="dotty"></span><span class="dotty"></span><span class="dotty"></span>';
      return wrap;
    }

    function setSendState(){
      sendBtn.disabled = inputEl.value.trim().length === 0;
    }

    // Correction clé : on appelle setSendState à chaque input + focus
    inputEl.addEventListener('input', () => {
      autoGrow(inputEl);
      setSendState();
    });

    inputEl.addEventListener('focus', setSendState);
    inputEl.addEventListener('blur', setSendState);

    function autoGrow(el){
      el.style.height = 'auto';
      el.style.height = Math.min(el.scrollHeight, 180) + 'px';
    }

    async function callChat(message){
      const res = await fetch('/api/chat_proxy.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ message })
      });

      const raw = await res.text();
      let data = null;
      try { data = JSON.parse(raw); } catch(e){ data = null; }

      if (!res.ok || !data) {
        return { ok:false, text: raw ? raw.slice(0, 800) : 'Erreur serveur.' };
      }
      if (data.error) {
        return { ok:false, text: data.response || 'Erreur.' };
      }
      return { ok:true, text: data.response || '' };
    }

    async function send(){
      const message = inputEl.value.trim();
      if (!message) return;

      addBubble(message, 'user');
      inputEl.value = '';
      autoGrow(inputEl);
      setSendState();

      const typingRow = addBubble(typingNode(), 'bot', true);

      const result = await callChat(message);

      typingRow.remove();
      if (!result.ok) {
        const row = addBubble('Erreur : ' + result.text, 'bot');
        row.querySelector('.bubble').classList.add('error');
        return;
      }
      addBubble(result.text, 'bot');
    }

    sendBtn.addEventListener('click', send);

    inputEl.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (!sendBtn.disabled) send();
      }
    });

    document.getElementById('quick').addEventListener('click', (e) => {
      const btn = e.target.closest('[data-q]');
      if (!btn) return;
      inputEl.value = btn.dataset.q;
      autoGrow(inputEl);
      setSendState();
      send();
    });

    clearBtn.addEventListener('click', () => {
      messagesEl.innerHTML = '';
      addBubble("Bonjour 👋\nJe suis votre assistant. Posez votre question (ex: présence, cours, réservation, portail).", 'bot');
    });

    suggestBtn.addEventListener('click', () => {
      const suggestions = [
        "Explique-moi la différence entre affectation, cours, filière et niveau.",
        "Donne-moi un checklist pour enregistrer la présence sans erreurs.",
        "Comment filtrer la liste des étudiants efficacement ?",
        "Quelles erreurs fréquentes et comment les corriger ?"
      ];
      inputEl.value = suggestions[Math.floor(Math.random()*suggestions.length)];
      autoGrow(inputEl);
      setSendState();
      inputEl.focus();
    });

    // Initialisation
    autoGrow(inputEl);
    setSendState();
    inputEl.focus();
  </script>
</body>
</html>