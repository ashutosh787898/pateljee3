<?php
require_once 'includes/config.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="images/favicon.ico">
<link rel="icon" type="image/png"    href="images/favicon.png">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>JaiClub Analyser Pro — Access Terminal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Share+Tech+Mono&family=Nunito:wght@400;600&display=swap" rel="stylesheet">
<style>
/* ── Reset ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  --bg:#06000e;
  --purple:#b44fff;
  --magenta:#e040fb;
  --violet:#7c4dff;
  --text:#f3e5f5;
  --dim:#9575cd;
  --dimmer:#5e3f7a;
  --border:rgba(180,79,255,0.22);
  --green:#00e676;
  --red:#ff4757;
}

html,body{
  min-height:100vh;background:var(--bg);color:var(--text);
  font-family:'Share Tech Mono',monospace;
  overflow-x:hidden;
}

/* CRT scanlines */
body::after{
  content:'';position:fixed;inset:0;pointer-events:none;z-index:9999;
  background:repeating-linear-gradient(
    0deg,transparent,transparent 3px,rgba(0,0,0,0.04) 3px,rgba(0,0,0,0.04) 4px
  );
}

/* ── Background ── */
.bg-field{
  position:fixed;inset:0;z-index:0;pointer-events:none;
  background:
    radial-gradient(ellipse 65% 55% at 15% 20%,rgba(124,77,255,0.1) 0%,transparent 55%),
    radial-gradient(ellipse 55% 45% at 85% 80%,rgba(180,79,255,0.08) 0%,transparent 55%);
}
.bg-field::before{
  content:'';position:absolute;inset:0;
  background-image:
    linear-gradient(rgba(180,79,255,0.035) 1px,transparent 1px),
    linear-gradient(90deg,rgba(180,79,255,0.035) 1px,transparent 1px);
  background-size:40px 40px;
  animation:gridDrift 40s linear infinite;
}
@keyframes gridDrift{to{background-position:40px 40px}}

/* Ambient pulse */
.bg-amb{
  position:fixed;inset:0;z-index:0;pointer-events:none;
  background:radial-gradient(ellipse 50% 35% at 50% 50%,rgba(124,77,255,0.08) 0%,transparent 70%);
  animation:ambPulse 5s ease-in-out infinite;
}
@keyframes ambPulse{0%,100%{opacity:0.6}50%{opacity:1}}

/* ── Page layout ── */
.page-wrap{
  position:relative;z-index:1;
  min-height:100vh;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:1.5rem 1rem;
  animation:pageIn 0.6s ease forwards;
}
@keyframes pageIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}

.terminal-shell{
  width:100%;max-width:420px;
  display:flex;flex-direction:column;gap:0;
}

/* ── Brand block ── */
.brand-block{
  position:relative;
  background:rgba(10,0,24,0.9);
  border:1px solid var(--border);
  border-bottom:none;
  border-radius:14px 14px 0 0;
  padding:1.75rem 1.5rem 1.5rem;
  backdrop-filter:blur(16px);
  overflow:hidden;
}
/* Corner brackets on brand block */
.brand-block::before,.brand-block::after{
  content:'';position:absolute;width:14px;height:14px;
  border-color:var(--purple);border-style:solid;
}
.brand-block::before{top:-1px;left:-1px;border-width:2px 0 0 2px;border-radius:2px 0 0 0}
.brand-block::after{top:-1px;right:-1px;border-width:2px 2px 0 0;border-radius:0 2px 0 0}

/* Scan sweep animation */
.brand-block .scan-sweep{
  position:absolute;top:0;left:-100%;width:50%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(180,79,255,0.12),transparent);
  animation:scanSweep 4s ease-in-out infinite;pointer-events:none;
}
@keyframes scanSweep{0%{left:-50%}60%{left:150%}100%{left:150%}}

.brand-inner{
  display:flex;flex-direction:column;align-items:center;
  text-align:center;gap:0.55rem;position:relative;z-index:1;
}

/* Logo */
.logo-ring{
  width:80px;height:80px;border-radius:50%;overflow:hidden;
  border:2px solid rgba(180,79,255,0.45);
  box-shadow:0 0 20px rgba(180,79,255,0.25),inset 0 0 20px rgba(180,79,255,0.05);
  background:#0a0018;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;position:relative;
}
.logo-ring img{width:100%;height:100%;object-fit:cover;display:block}
.logo-ring .fallback{font-size:2rem;display:none}

/* Rotating ring around logo */
.logo-ring::after{
  content:'';position:absolute;inset:-4px;border-radius:50%;
  border:1px dashed rgba(180,79,255,0.3);
  animation:ringRotate 12s linear infinite;
}
@keyframes ringRotate{to{transform:rotate(360deg)}}

/* Brand text */
.brand-name{
  font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;
  letter-spacing:0.04em;line-height:1.1;
}
.grad-text{
  background:linear-gradient(90deg,#b44fff,#e040fb,#7c4dff);
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
.brand-tagline{
  font-size:0.62rem;color:var(--dim);letter-spacing:0.14em;text-transform:uppercase;
}
.brand-status{
  display:flex;align-items:center;gap:0.4rem;
  font-size:0.6rem;color:var(--dimmer);letter-spacing:0.08em;
}
.status-led{
  width:5px;height:5px;border-radius:50%;
  background:var(--green);box-shadow:0 0 5px var(--green);
  animation:ledBlink 2s ease-in-out infinite;flex-shrink:0;
}
@keyframes ledBlink{0%,100%{opacity:1}50%{opacity:0.4}}

/* ── Separator ── */
.term-sep{
  display:flex;align-items:center;
  background:rgba(10,0,24,0.9);
  border-left:1px solid var(--border);
  border-right:1px solid var(--border);
  padding:0 1.5rem;
}
.sep-line{flex:1;height:1px;background:linear-gradient(90deg,transparent,rgba(180,79,255,0.35),transparent)}
.sep-diamond{color:var(--purple);font-size:0.65rem;margin:0 0.6rem;animation:diamondPulse 2s ease-in-out infinite}
@keyframes diamondPulse{0%,100%{text-shadow:0 0 4px var(--purple)}50%{text-shadow:0 0 12px var(--purple),0 0 20px var(--magenta)}}

/* ── Access Terminal (form area) ── */
.access-terminal{
  background:rgba(10,0,24,0.9);
  border:1px solid var(--border);
  border-top:none;
  border-radius:0 0 14px 14px;
  padding:1.4rem 1.5rem 1.6rem;
  backdrop-filter:blur(16px);
  box-shadow:0 20px 60px rgba(0,0,0,0.6),0 0 40px rgba(124,77,255,0.12);
  position:relative;
}
/* Corner brackets on terminal */
.access-terminal::before,.access-terminal::after{
  content:'';position:absolute;width:14px;height:14px;
  border-color:var(--magenta);border-style:solid;
}
.access-terminal::before{bottom:-1px;left:-1px;border-width:0 0 2px 2px;border-radius:0 0 0 2px}
.access-terminal::after{bottom:-1px;right:-1px;border-width:0 2px 2px 0;border-radius:0 0 2px 0}

/* ── Mode tabs ── */
.mode-tabs{
  display:flex;gap:4px;margin-bottom:1.4rem;
  border-bottom:1px solid rgba(180,79,255,0.12);
  padding-bottom:0;
}
.mode-tab{
  flex:1;padding:0.5rem 0.5rem 0.6rem;
  border:none;border-bottom:2px solid transparent;
  background:transparent;color:var(--dimmer);
  font-family:'Share Tech Mono',monospace;
  font-size:0.65rem;letter-spacing:0.1em;text-transform:uppercase;
  cursor:pointer;transition:all 0.2s;text-align:center;
}
.mode-tab:hover{color:var(--dim)}
.mode-tab.active{
  color:var(--magenta);
  border-bottom-color:var(--magenta);
  text-shadow:0 0 8px rgba(224,64,251,0.5);
}

/* ── Form panes ── */
.tab-pane{display:none;animation:paneIn 0.25s ease}
.tab-pane.active{display:block}
@keyframes paneIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}

/* ── Terminal-style fields ── */
.term-field{position:relative;margin-bottom:1.4rem}

.term-label{
  display:flex;align-items:center;gap:0.4rem;
  font-size:0.58rem;color:var(--dim);
  letter-spacing:0.12em;text-transform:uppercase;
  margin-bottom:0.4rem;
}
.term-label .prompt{color:var(--purple);font-size:0.75rem}

.term-input{
  width:100%;
  background:rgba(180,79,255,0.03);
  border:none;
  border-bottom:1px solid rgba(180,79,255,0.2);
  padding:0.5rem 0.5rem 0.5rem 0;
  color:var(--text);
  font-family:'Share Tech Mono',monospace;
  font-size:0.88rem;
  outline:none;
  letter-spacing:0.04em;
  transition:border-color 0.2s,background 0.2s;
  border-radius:0;
  -webkit-appearance:none;
}
.term-input:focus{
  border-bottom-color:var(--purple);
  background:rgba(180,79,255,0.05);
}
.term-input::placeholder{color:rgba(149,117,205,0.25);font-size:0.78rem}

/* Focus glow line */
.term-focus-line{
  position:absolute;bottom:0;left:0;height:1px;width:0;
  background:linear-gradient(90deg,var(--violet),var(--purple),var(--magenta));
  transition:width 0.3s ease;
  box-shadow:0 0 6px var(--purple);
}
.term-input:focus + .term-focus-line{width:100%}

/* ── Auth button ── */
.auth-btn{
  width:100%;padding:0.85rem;margin-top:0.25rem;
  border:none;border-radius:6px;cursor:pointer;
  background:linear-gradient(90deg,#3d0066,#7c4dff,#b44fff,#e040fb,#b44fff,#7c4dff,#3d0066);
  background-size:300%;color:#fff;
  font-family:'Share Tech Mono',monospace;
  font-size:0.82rem;font-weight:400;letter-spacing:0.14em;text-transform:uppercase;
  animation:bgShift 4s linear infinite;
  box-shadow:0 0 18px rgba(180,79,255,0.3);
  transition:box-shadow 0.25s,transform 0.2s;
  position:relative;overflow:hidden;
}
@keyframes bgShift{0%{background-position:0%}100%{background-position:300%}}
.auth-btn::after{
  content:'';position:absolute;top:0;left:-80%;width:40%;height:100%;
  background:rgba(255,255,255,0.08);transform:skewX(-20deg);
  animation:btnSheen 3s ease-in-out infinite;
}
@keyframes btnSheen{0%,100%{left:-80%}50%{left:130%}}
.auth-btn:hover{box-shadow:0 0 30px rgba(180,79,255,0.5);transform:translateY(-1px)}
.auth-btn:disabled{opacity:0.4;cursor:not-allowed;transform:none;animation:none;background:#2a0044}

/* ── Message ── */
.auth-msg{
  margin-top:1rem;padding:0.55rem 0.9rem;border-radius:5px;
  font-size:0.68rem;letter-spacing:0.06em;text-align:center;
  display:none;
}
.auth-msg.success{display:block;background:rgba(0,230,118,0.08);border:1px solid rgba(0,230,118,0.25);color:var(--green)}
.auth-msg.error  {display:block;background:rgba(255,71,87,0.08);border:1px solid rgba(255,71,87,0.25);color:var(--red)}
.auth-msg.info   {display:block;background:rgba(180,79,255,0.08);border:1px solid rgba(180,79,255,0.22);color:var(--dim)}

/* ── Footer ── */
.terminal-footer{
  margin-top:1.1rem;text-align:center;
  font-size:0.56rem;color:rgba(149,117,205,0.35);letter-spacing:0.1em;
  text-transform:uppercase;
}
</style>
</head>
<body>

<div class="bg-field"></div>
<div class="bg-amb"></div>

<div class="page-wrap">
  <div class="terminal-shell">

    <!-- ── Brand block ── -->
    <div class="brand-block">
      <div class="scan-sweep"></div>
      <div class="brand-inner">
        <div class="logo-ring">
          <img src="images/logo.png" id="login-logo" alt="">
          <div class="fallback" id="logo-fallback">🎯</div>
        </div>
        <h1 class="brand-name"><span class="grad-text">JaiClub</span> Analyser Pro</h1>
        <div class="brand-tagline">Identity Verification Terminal</div>
        <div class="brand-status">
          <span class="status-led"></span>
          <span>Signal Secure · Encrypted Connection</span>
        </div>
      </div>
    </div>

    <!-- Separator -->
    <div class="term-sep">
      <span class="sep-line"></span>
      <span class="sep-diamond">◈</span>
      <span class="sep-line"></span>
    </div>

    <!-- ── Access Terminal ── -->
    <div class="access-terminal">

      <div class="mode-tabs">
        <button class="mode-tab active" onclick="switchTab('login',this)">▶ Existing User</button>
        <button class="mode-tab" onclick="switchTab('signup',this)">+ New Operative</button>
      </div>

      <!-- Login -->
      <div id="login-tab" class="tab-pane active">
        <div class="term-field">
          <div class="term-label"><span class="prompt">›</span> Email Address</div>
          <input type="email" class="term-input" id="l-email"
                 placeholder="your@email.com" autocomplete="email">
          <div class="term-focus-line"></div>
        </div>
        <div class="term-field">
          <div class="term-label"><span class="prompt">›</span> Password</div>
          <input type="password" class="term-input" id="l-pass"
                 placeholder="••••••••" autocomplete="current-password">
          <div class="term-focus-line"></div>
        </div>
        <button class="auth-btn" id="login-btn" onclick="doLogin()">
          ▶▶ Authenticate
        </button>
      </div>

      <!-- Sign Up -->
      <div id="signup-tab" class="tab-pane">
        <div class="term-field">
          <div class="term-label"><span class="prompt">›</span> Email Address</div>
          <input type="email" class="term-input" id="r-email"
                 placeholder="your@email.com" autocomplete="email">
          <div class="term-focus-line"></div>
        </div>
        <div class="term-field">
          <div class="term-label"><span class="prompt">›</span> Create Password <small style="color:var(--dimmer)">(min 6)</small></div>
          <input type="password" class="term-input" id="r-pass"
                 placeholder="••••••••" autocomplete="new-password">
          <div class="term-focus-line"></div>
        </div>
        <div class="term-field">
          <div class="term-label"><span class="prompt">›</span> Confirm Password</div>
          <input type="password" class="term-input" id="r-confirm"
                 placeholder="••••••••" autocomplete="new-password">
          <div class="term-focus-line"></div>
        </div>
        <button class="auth-btn" id="signup-btn" onclick="doSignup()">
          + Create Access
        </button>
      </div>

      <div class="auth-msg" id="auth-msg"></div>
    </div><!-- /access-terminal -->

    <div class="terminal-footer">
      JaiClub Analyser Pro v4.0 · Signal Edition · All rights reserved
    </div>

  </div><!-- /terminal-shell -->
</div><!-- /page-wrap -->

<script>
/* ── Tab switching ── */
function switchTab(tab, btn) {
  document.querySelectorAll('.mode-tab').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById(tab + '-tab').classList.add('active');
  hideMsg();
}

/* ── Messages ── */
function showMsg(text, type) {
  const el = document.getElementById('auth-msg');
  el.textContent = text;
  el.className = 'auth-msg ' + type;
}
function hideMsg() {
  document.getElementById('auth-msg').className = 'auth-msg';
}

/* ── Login ── */
async function doLogin() {
  const email = document.getElementById('l-email').value.trim();
  const pass  = document.getElementById('l-pass').value;
  if (!email || !pass) { showMsg('All fields required', 'error'); return; }
  const btn = document.getElementById('login-btn');
  btn.disabled = true; btn.textContent = '▷ Verifying Identity...';
  try {
    const r = await fetch('auth.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'login', email, password:pass })
    });
    const d = await r.json();
    if (d.success) {
      showMsg('► Access granted. Redirecting...', 'success');
      setTimeout(() => location.href = 'dashboard.php', 700);
    } else {
      showMsg(d.message || 'Authentication failed', 'error');
      btn.disabled = false; btn.textContent = '▶▶ Authenticate';
    }
  } catch(e) {
    showMsg('Signal lost. Try again.', 'error');
    btn.disabled = false; btn.textContent = '▶▶ Authenticate';
  }
}

/* ── Sign Up ── */
async function doSignup() {
  const email   = document.getElementById('r-email').value.trim();
  const pass    = document.getElementById('r-pass').value;
  const confirm = document.getElementById('r-confirm').value;
  if (!email || !pass || !confirm) { showMsg('All fields required', 'error'); return; }
  if (pass.length < 6) { showMsg('Password min 6 characters', 'error'); return; }
  if (pass !== confirm) { showMsg('Passwords do not match', 'error'); return; }
  const btn = document.getElementById('signup-btn');
  btn.disabled = true; btn.textContent = '▷ Creating Account...';
  try {
    const r = await fetch('auth.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'signup', email, password:pass })
    });
    const d = await r.json();
    if (d.success) {
      showMsg('► Account created. Welcome operative.', 'success');
      setTimeout(() => location.href = 'dashboard.php', 700);
    } else {
      showMsg(d.message || 'Registration failed', 'error');
      btn.disabled = false; btn.textContent = '+ Create Access';
    }
  } catch(e) {
    showMsg('Signal lost. Try again.', 'error');
    btn.disabled = false; btn.textContent = '+ Create Access';
  }
}

/* ── Enter key ── */
document.addEventListener('keydown', e => {
  if (e.key !== 'Enter') return;
  const active = document.querySelector('.tab-pane.active').id;
  active === 'login-tab' ? doLogin() : doSignup();
});

/* ── Logo fallback chain ── */
(function() {
  const img = document.getElementById('login-logo');
  const fb  = document.getElementById('logo-fallback');
  if (!img) return;
  const alts = ['images/logo.png','images/Logo.png','images/logo.jpg','images/logo.jpeg','images/logo.webp'];
  let i = 0;
  img.onerror = function next() {
    i++;
    if (i >= alts.length) {
      img.style.display = 'none';
      if (fb) fb.style.display = 'flex';
      return;
    }
    img.src = alts[i]; img.onerror = next;
  };
})();
</script>
</body>
</html>
