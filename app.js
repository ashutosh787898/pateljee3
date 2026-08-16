/* ============================================================
   JaiClub Analyser Pro — SIGNAL Edition JS
   LED countdown · Glitch reveal · Ticker history
   ============================================================ */
'use strict';

const SEGS       = 30;
const BALL_DATA  = {
  0:{bg:'#b71c1c',glow:'#ff1744',txt:'Red+Violet', cls:'rv'},
  1:{bg:'#1b5e20',glow:'#00e676',txt:'Green',      cls:'green'},
  2:{bg:'#b71c1c',glow:'#ff1744',txt:'Red',        cls:'red'},
  3:{bg:'#1b5e20',glow:'#00e676',txt:'Green',      cls:'green'},
  4:{bg:'#b71c1c',glow:'#ff1744',txt:'Red',        cls:'red'},
  5:{bg:'#4a148c',glow:'#e040fb',txt:'Green+Violet',cls:'violet'},
  6:{bg:'#b71c1c',glow:'#ff1744',txt:'Red',        cls:'red'},
  7:{bg:'#1b5e20',glow:'#00e676',txt:'Green',      cls:'green'},
  8:{bg:'#b71c1c',glow:'#ff1744',txt:'Red',        cls:'red'},
  9:{bg:'#1b5e20',glow:'#00e676',txt:'Green',      cls:'green'},
};

let analyserOn = false;
let spinning   = false;
let lastRound  = null;

/* ── 1-Min Timer + LED Segments ── */
function tick() {
  const now   = new Date();
  const total = now.getUTCHours()*3600 + now.getUTCMinutes()*60 + now.getUTCSeconds();

  const rem  = 60 - (total % 60);
  const disp = Math.max(0, rem - 1);

  // Clock display
  const el = document.getElementById('timer-disp');
  if (el) el.textContent = `00:${disp.toString().padStart(2,'0')}`;

  // LED segments
  const active = Math.round((disp / 59) * SEGS);
  const warn   = disp <= 10;
  document.querySelectorAll('.seg').forEach((s, i) => {
    s.classList.toggle('on', i < active);
    s.classList.toggle('warn', i < active && warn);
  });

  // Round ID
  const dateStr  = now.toISOString().slice(0,10).replace(/-/g,'');
  const roundNum = 10001 + Math.floor(total / 60);
  const period   = dateStr + '1000' + roundNum;
  const rEl = document.getElementById('round-id');
  if (rEl) rEl.textContent = period;

  // New round triggers
  if (rem === 60) {
    if (IS_VIP && analyserOn && !spinning) triggerSpin();
    setTimeout(fetchHistory, 5000); // 5s delay for API to publish
  }

  lastRound = roundNum;
}

/* ── Slot Machine Reveal ── */
function triggerSpin() {
  const frame = document.getElementById('target-frame');
  const disp  = document.getElementById('result-display');
  if (!frame || !disp || spinning) return;
  spinning = true;

  const finalNum  = Math.floor(Math.random() * 10);
  const finalBall = BALL_DATA[finalNum];

  // Lock-on animation
  frame.classList.add('locking');

  // Show spinning state
  disp.innerHTML = `
    <div class="t-spinning">
      <div class="spin-lbl">ANALYSING SIGNAL...</div>
      <div class="spin-strip" id="spin-strip">
        ${[0,1,2].map(()=>`<div class="spin-num" style="background:#1a0035">?</div>`).join('')}
      </div>
    </div>`;

  let spins = 0;
  const maxSpins = 20;

  const iv = setInterval(() => {
    spins++;
    const strip = document.getElementById('spin-strip');
    if (strip) {
      strip.innerHTML = [0,1,2].map(() => {
        const n = Math.floor(Math.random()*10);
        const b = BALL_DATA[n];
        return `<div class="spin-num" style="background:${b.bg}">${n}</div>`;
      }).join('');
    }
    if (spins >= maxSpins) {
      clearInterval(iv);
      showResult(frame, disp, finalNum, finalBall);
    }
  }, spins < 12 ? 80 : 140);
}

function showResult(frame, disp, num, ball) {
  const size = num >= 5 ? 'BIG' : 'SMALL';

  frame.classList.remove('locking');
  frame.classList.remove('glow-red','glow-green','glow-violet');
  const glowMap = {red:'glow-red',green:'glow-green',violet:'glow-violet',rv:'glow-red',gv:'glow-green'};
  frame.classList.add(glowMap[ball.cls] || 'glow-violet');

  disp.innerHTML = `
    <div class="t-result">
      <div class="result-ball-big"
           style="background:${ball.bg};box-shadow:0 0 30px ${ball.glow},0 0 60px ${ball.glow}44;color:${ball.glow}">
        ${num}
      </div>
      <div class="glitch-label" style="color:${ball.glow}">
        ${size} · ${ball.txt}
      </div>
      <div class="result-sub">Round prediction locked</div>
    </div>`;

  // Ambient glow
  setAmbient(ball.cls);
  spinning = false;
}

function setAmbient(cls) {
  const map = {
    red:'rgba(255,23,68,0.16)',green:'rgba(0,230,118,0.14)',
    violet:'rgba(180,0,255,0.16)',rv:'rgba(255,23,68,0.14)',gv:'rgba(0,230,118,0.14)'
  };
  const layer = document.getElementById('ambient');
  if (!layer) return;
  const c = map[cls]||'rgba(180,79,255,0.12)';
  layer.style.background = `radial-gradient(ellipse 60% 45% at 50% 35%,${c} 0%,transparent 70%)`;
}

/* ── Engage / Start ── */
function handleStart() {
  if (IS_VIP) {
    analyserOn = true;
    const btn = document.getElementById('start-btn');
    btn.disabled = true;
    btn.textContent = '✓  SYSTEM ENGAGED — MONITORING';
    if (!spinning) triggerSpin();
    fetchHistory();
    showToast('SYSTEM ENGAGED — auto-updates every round', 'info');
  } else {
    openModal();
  }
}

/* ── Live History Ticker ── */
async function fetchHistory() {
  const inner = document.getElementById('ticker-inner');
  if (!inner) return;

  try {
    const res  = await fetch('jalwa-history.php', { cache:'no-store', credentials:'same-origin' });
    const data = await res.json();

    if (!data.success || !data.data?.length) {
      inner.innerHTML = `<span class="feed-error">${data.message||'No feed data'}</span>`;
      return;
    }

    // Build ticker pills
    const items = data.data.map(row => {
      const num    = parseInt(row.number);
      const colour = (row.colour || row.color || '').toLowerCase();
      const size   = num >= 5 ? 'BIG' : 'SM';
      const period = String(row.issueNumber||'').slice(-5);

      let ballBg = '#b71c1c';
      let colShort = 'R';
      if (colour.includes('green') && colour.includes('violet')) { ballBg='#1b5e20'; colShort='G+V'; }
      else if (colour.includes('red') && colour.includes('violet')) { ballBg='#b71c1c'; colShort='R+V'; }
      else if (colour.includes('green'))  { ballBg='#1b5e20'; colShort='G'; }
      else if (colour.includes('violet')) { ballBg='#4a148c'; colShort='V'; }

      const textCol = ballBg==='#1b5e20' ? '#69f0ae' : ballBg==='#4a148c' ? '#ea80fc' : '#ff8a80';

      return `<span class="tick-pill">
        <span class="tick-ball" style="background:${ballBg}">${num}</span>
        <span class="tick-info" style="color:${textCol}">${size}·${colShort}</span>
        <span class="tick-period">#${period}</span>
      </span>`;
    });

    // Duplicate for seamless loop
    const html = items.join('<span class="tick-sep">·</span>');
    inner.innerHTML = html + '<span class="tick-sep" style="opacity:.3">⬡</span>' + html;

    // Adjust speed based on item count
    inner.style.animationDuration = Math.max(12, data.data.length * 2) + 's';

  } catch(e) {
    if (inner) inner.innerHTML = '<span class="feed-error">Connection error</span>';
  }
}

/* ── Logo fallback ── */
function initLogo() {
  const alts = ['images/logo.png','images/Logo.png','images/logo.jpg','images/logo.jpeg','images/logo.webp'];
  const img  = document.getElementById('header-logo-img');
  if (!img) return;
  let i = 0;
  img.onerror = function next() {
    i++;
    if (i >= alts.length) {
      img.style.display='none';
      const fb = img.nextElementSibling;
      if (fb) fb.style.removeProperty('display');
      return;
    }
    img.src = alts[i]; img.onerror = next;
  };
}

/* ── Modal ── */
function openModal() {
  document.getElementById('vip-modal').style.display='flex';
  document.body.style.overflow='hidden';
}
function closeModal() {
  document.getElementById('vip-modal').style.display='none';
  document.body.style.overflow='';
}

/* ── Payment ── */
async function initPayment() {
  const btn = document.getElementById('pay-btn');
  btn.disabled=true; btn.textContent='PROCESSING...';
  try {
    const r = await fetch('payment.php',{
      method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({action:'create'}),
    });
    const d = await r.json();
    if (d.success && d.payment_url) {
      document.getElementById('vip-modal').style.display='none';
      document.getElementById('processing').style.display='flex';
      if (d.amount) {
        document.getElementById('processing-text').textContent=
          `REDIRECTING... AMOUNT: ₹${parseFloat(d.amount).toFixed(2)}`;
      }
      setTimeout(()=>{ window.location.href=d.payment_url; }, 700);
    } else {
      showToast(d.message||'Payment failed','error');
      btn.disabled=false; btn.textContent='⚡ PAY & ACTIVATE VIP';
    }
  } catch(e) {
    showToast('Network error. Try again.','error');
    btn.disabled=false; btn.textContent='⚡ PAY & ACTIVATE VIP';
  }
}

/* ── Toast ── */
function showToast(text, type='info') {
  document.querySelector('.toast.dyn')?.remove();
  const el = Object.assign(document.createElement('div'),{
    className:`toast toast-${type} dyn`,textContent:text
  });
  document.body.appendChild(el);
  setTimeout(()=>el.remove(), 4000);
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('vip-modal');
  if (overlay) overlay.addEventListener('click', e=>{ if(e.target===overlay) closeModal(); });

  document.getElementById('flash-toast') &&
    setTimeout(()=>document.getElementById('flash-toast')?.remove(), 4000);

  tick();
  setInterval(tick, 1000);
  initLogo();
  fetchHistory(); // load history immediately on page open
});
