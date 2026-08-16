/* ============================================================
   WinGo Analyser – Admin Panel JS
   ============================================================ */

'use strict';

/* ── Tab Navigation ── */
document.addEventListener('DOMContentLoaded', () => {
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.tab;
      navItems.forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('tab-' + tab).classList.add('active');

      // Lazy load data
      if (tab === 'stats')    loadStats();
      if (tab === 'users')    loadUsers();
      if (tab === 'orders')   loadOrders();
      if (tab === 'settings') loadSettings();
    });
  });

  // Load stats by default
  loadStats();
});

/* ── API helper ── */
async function api(data) {
  const res = await fetch('admin-api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
  return res.json();
}

/* ── Stats ── */
async function loadStats(btn) {
  const grid = document.getElementById('stats-grid');
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Loading...'; }
  grid.innerHTML = '<div class="stat-card loading"><div class="spinner-sm"></div></div>'.repeat(4);
  try {
    const d = await api({ action: 'get_stats' });
    if (!d.success) { grid.innerHTML = '<p style="color:var(--red)">Failed to load stats</p>'; return; }
    const now = new Date().toLocaleTimeString();
    grid.innerHTML = `
      <div class="stat-card"><div class="stat-icon">👥</div>
        <div class="stat-label">Total Users</div>
        <div class="stat-value">${d.total}</div></div>
      <div class="stat-card"><div class="stat-icon">👑</div>
        <div class="stat-label">Active VIP</div>
        <div class="stat-value">${d.vip}</div></div>
      <div class="stat-card"><div class="stat-icon">✅</div>
        <div class="stat-label">Paid Orders</div>
        <div class="stat-value">${d.orders}</div></div>
      <div class="stat-card"><div class="stat-icon">💰</div>
        <div class="stat-label">Revenue (₹)</div>
        <div class="stat-value">${parseFloat(d.revenue).toFixed(2)}</div></div>
    `;
    // Show last updated time
    const existing = document.getElementById('stats-updated');
    if (existing) existing.remove();
    const info = document.createElement('p');
    info.id = 'stats-updated';
    info.style.cssText = 'color:var(--dim);font-size:0.75rem;margin-top:0.75rem';
    info.textContent = '⏱ Last updated: ' + now;
    grid.after(info);
  } catch(e) {
    grid.innerHTML = '<p style="color:var(--red)">Network error</p>';
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = '🔄 Refresh'; }
  }
}

/* ── Users ── */
let userPage = 1;
async function loadUsers(page = 1) {
  userPage = page;
  const wrap = document.getElementById('users-table');
  wrap.innerHTML = '<div class="loading-text">Loading users...</div>';
  try {
    const d = await api({ action: 'get_users', page });
    wrap.innerHTML = buildUserTable(d.users);
    buildPagination('users-pagination', d.total, page, 20, loadUsers);
  } catch(e) {
    wrap.innerHTML = '<div class="loading-text" style="color:var(--red)">Error loading users</div>';
  }
}

function buildUserTable(users) {
  if (!users || !users.length) return '<div class="loading-text">No users found</div>';
  const rows = users.map(u => {
    const isVIP = u.vip_expires_at && new Date(u.vip_expires_at) > new Date();
    const vipLabel = isVIP
      ? `<span class="badge badge-vip">👑 VIP until ${u.vip_expires_at?.slice(0,10)}</span>`
      : '<span class="badge badge-free">Free</span>';
    return `<tr>
      <td>${u.id}</td>
      <td>${escHtml(u.email)}</td>
      <td>${vipLabel}</td>
      <td>${u.created_at?.slice(0,10) || '-'}</td>
    </tr>`;
  }).join('');
  return `<table><thead><tr><th>ID</th><th>Email</th><th>Status</th><th>Joined</th></tr></thead>
  <tbody>${rows}</tbody></table>`;
}

async function searchUsers() {
  const email = document.getElementById('search-email').value.trim();
  if (!email) return;
  const wrap = document.getElementById('search-results');
  wrap.innerHTML = '<div class="loading-text">Searching...</div>';
  try {
    const d = await api({ action: 'search_user', email });
    wrap.innerHTML = buildUserTable(d.users);
  } catch(e) {
    wrap.innerHTML = '<div class="loading-text" style="color:var(--red)">Error</div>';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('search-email');
  if (searchInput) {
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') searchUsers(); });
  }
});

/* ── VIP Activation ── */
async function activateVIP() {
  const email = document.getElementById('vip-email').value.trim();
  const days  = parseInt(document.getElementById('vip-days').value) || 17;
  const msg   = document.getElementById('vip-msg');
  if (!email) { showMsg(msg, 'Email required', 'error'); return; }
  try {
    const d = await api({ action: 'activate_vip', email, days });
    showMsg(msg, d.message, d.success ? 'success' : 'error');
    if (d.success) loadUsers();
  } catch(e) {
    showMsg(msg, 'Network error', 'error');
  }
}

async function revokeVIP() {
  const email = document.getElementById('vip-email').value.trim();
  const msg   = document.getElementById('vip-msg');
  if (!email) { showMsg(msg, 'Email required', 'error'); return; }
  if (!confirm(`Revoke VIP from ${email}?`)) return;
  try {
    const d = await api({ action: 'revoke_vip', email });
    showMsg(msg, d.message, d.success ? 'success' : 'error');
    if (d.success) loadUsers();
  } catch(e) {
    showMsg(msg, 'Network error', 'error');
  }
}

/* ── Orders ── */
let orderPage = 1;
async function loadOrders(page = 1) {
  orderPage = page;
  const wrap = document.getElementById('orders-table');
  wrap.innerHTML = '<div class="loading-text">Loading orders...</div>';
  try {
    const d = await api({ action: 'get_orders', page });
    if (!d.orders?.length) { wrap.innerHTML = '<div class="loading-text">No orders yet</div>'; return; }
    const rows = d.orders.map(o => `<tr>
      <td>${o.order_id}</td>
      <td>${escHtml(o.email || '—')}</td>
      <td>₹${parseFloat(o.amount).toFixed(2)}</td>
      <td><span class="badge badge-${o.status}">${o.status.toUpperCase()}</span></td>
      <td>${o.txn_id || '—'}</td>
      <td>${o.created_at?.slice(0,10) || '—'}</td>
    </tr>`).join('');
    wrap.innerHTML = `<table><thead><tr>
      <th>Order ID</th><th>User</th><th>Amount</th><th>Status</th><th>TXN ID</th><th>Date</th>
    </tr></thead><tbody>${rows}</tbody></table>`;
  } catch(e) {
    wrap.innerHTML = '<div class="loading-text" style="color:var(--red)">Error loading orders</div>';
  }
}

/* ── Settings ── */
async function loadSettings() {
  try {
    const d = await api({ action: 'get_settings' });
    if (!d.success) return;
    const s = d.settings;
    const keys = ['payment_amount','allapi_token','telegram_link','youtube_link','whatsapp_link','yaarwin_link','vip_days'];
    keys.forEach(k => {
      const el = document.getElementById('s-' + k);
      if (el) el.value = s[k] || '';
    });
  } catch(e) {}
}

async function saveSettings() {
  const keys = ['payment_amount','allapi_token','telegram_link','youtube_link','whatsapp_link','yaarwin_link','vip_days','admin_password'];
  const payload = { action: 'save_settings' };
  keys.forEach(k => {
    const el = document.getElementById('s-' + k);
    if (el) payload[k] = el.value.trim();
  });
  const msg = document.getElementById('settings-msg');
  try {
    const d = await api(payload);
    showMsg(msg, d.message, d.success ? 'success' : 'error');
    if (d.success && payload.admin_password) {
      document.getElementById('s-admin_password').value = '';
    }
  } catch(e) {
    showMsg(msg, 'Network error', 'error');
  }
}

/* ── Pagination helper ── */
function buildPagination(id, total, current, perPage, callback) {
  const wrap = document.getElementById(id);
  if (!wrap) return;
  const pages = Math.ceil(total / perPage);
  if (pages <= 1) { wrap.innerHTML = ''; return; }
  let html = '';
  for (let i = 1; i <= pages; i++) {
    html += `<button class="page-btn${i === current ? ' active' : ''}" onclick="${callback.name}(${i})">${i}</button>`;
  }
  wrap.innerHTML = html;
}

/* ── Helpers ── */
function showMsg(el, text, type) {
  el.textContent = text;
  el.className   = 'action-msg ' + type;
  setTimeout(() => { el.className = 'action-msg'; }, 4000);
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}
