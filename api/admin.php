<?php
require_once 'includes/config.php';
if (isAdminLoggedIn()) { header('Location: admin-panel.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – WinGo Analyser Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Nunito:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#060810;--orange:#ff6a00;--cyan:#00f5ff;--text:#e8eaf6;--dim:#8892b0;--red:#ff4757;--green:#00e676}
body{font-family:'Nunito',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;
  display:flex;align-items:center;justify-content:center;
  background-image:radial-gradient(ellipse 60% 50% at 50% 50%,rgba(255,106,0,0.06) 0%,transparent 70%)}
.card{background:rgba(13,17,36,0.95);border:1px solid rgba(255,106,0,0.2);border-radius:16px;
  padding:2.5rem 2rem;width:100%;max-width:380px;
  box-shadow:0 0 40px rgba(255,106,0,0.06)}
.admin-logo{text-align:center;margin-bottom:2rem}
.admin-badge{display:inline-block;padding:0.3rem 0.8rem;background:rgba(255,106,0,0.1);
  border:1px solid rgba(255,106,0,0.3);border-radius:20px;
  font-size:0.7rem;color:var(--orange);letter-spacing:0.1em;text-transform:uppercase;font-weight:700;margin-bottom:0.75rem}
h1{font-family:'Rajdhani',sans-serif;font-size:1.7rem;font-weight:700}
.grad-text{background:linear-gradient(90deg,#ff6a00,#00f5ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.form-group{margin-bottom:1.25rem}
label{display:block;font-size:0.78rem;color:var(--dim);font-weight:600;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.4rem}
input{width:100%;padding:0.75rem 1rem;background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,106,0,0.15);border-radius:10px;color:var(--text);
  font-family:'Nunito',sans-serif;font-size:0.95rem;outline:none;transition:all 0.2s}
input:focus{border-color:rgba(255,106,0,0.5);box-shadow:0 0 0 3px rgba(255,106,0,0.1)}
input::placeholder{color:rgba(136,146,176,0.4)}
.btn{width:100%;padding:0.85rem;border:none;border-radius:10px;cursor:pointer;
  background:linear-gradient(135deg,#ff6a00,#ff9a00);color:#000;
  font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:700;
  letter-spacing:0.05em;text-transform:uppercase;transition:all 0.25s;
  box-shadow:0 4px 20px rgba(255,106,0,0.3)}
.btn:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(255,106,0,0.45)}
.btn:disabled{opacity:0.6;cursor:not-allowed;transform:none}
.msg{margin-top:1rem;padding:0.65rem 1rem;border-radius:8px;font-size:0.87rem;font-weight:600;text-align:center;display:none}
.msg.error{display:block;background:rgba(255,71,87,0.1);border:1px solid rgba(255,71,87,0.3);color:var(--red)}
.back-link{text-align:center;margin-top:1.25rem}
.back-link a{color:var(--dim);font-size:0.82rem;text-decoration:none}
.back-link a:hover{color:var(--orange)}
</style>
</head>
<body>
<div class="card">
  <div class="admin-logo">
    <div class="admin-badge">⚙️ Admin Access</div>
    <h1><span class="grad-text">WinGo</span> Admin</h1>
  </div>
  <div class="form-group">
    <label>Admin Password</label>
    <input type="password" id="admin-pass" placeholder="Enter admin password" autocomplete="current-password">
  </div>
  <button class="btn" id="login-btn" onclick="doAdminLogin()">Access Dashboard</button>
  <div class="msg" id="msg"></div>
  <div class="back-link"><a href="index.php">← Back to main site</a></div>
</div>

<script>
async function doAdminLogin() {
  const pass = document.getElementById('admin-pass').value;
  if (!pass) return;
  const btn = document.getElementById('login-btn');
  btn.disabled = true; btn.textContent = 'Verifying...';
  try {
    const r = await fetch('admin-api.php', {method:'POST',headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'admin_login', password:pass})});
    const d = await r.json();
    if (d.success) {
      location.href = 'admin-panel.php';
    } else {
      const m = document.getElementById('msg');
      m.textContent = d.message || 'Invalid password';
      m.className = 'msg error';
      btn.disabled = false; btn.textContent = 'Access Dashboard';
    }
  } catch(e) {
    document.getElementById('msg').className = 'msg error';
    document.getElementById('msg').textContent = 'Network error';
    btn.disabled = false; btn.textContent = 'Access Dashboard';
  }
}
document.getElementById('admin-pass').addEventListener('keydown', e => {
  if (e.key === 'Enter') doAdminLogin();
});
</script>
</body>
</html>
