<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel – WinGo Analyser Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin-style.css">
</head>
<body>
<header class="admin-header">
  <div class="admin-brand">
    <span class="admin-badge-sm">⚙️ Admin</span>
    <span class="admin-title"><span class="grad-text">WinGo</span> Analyser Pro</span>
  </div>
  <a href="admin-logout.php" class="admin-logout">Logout</a>
</header>

<div class="admin-layout">
  <!-- Sidebar -->
  <nav class="admin-nav">
    <button class="nav-item active" data-tab="stats">📊 Statistics</button>
    <button class="nav-item" data-tab="users">👥 Users & VIP</button>
    <button class="nav-item" data-tab="orders">💳 Orders</button>
    <button class="nav-item" data-tab="settings">⚙️ Settings</button>
  </nav>

  <!-- Content -->
  <div class="admin-main">

    <!-- ===== STATS TAB ===== -->
    <div class="tab-content active" id="tab-stats">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
        <h2 class="section-title" style="margin-bottom:0">Dashboard Statistics</h2>
        <button class="btn-orange" style="padding:0.45rem 1.1rem;font-size:0.82rem" onclick="loadStats(this)">
          🔄 Refresh
        </button>
      </div>
      <div class="stats-grid" id="stats-grid">
        <div class="stat-card loading"><div class="spinner-sm"></div></div>
        <div class="stat-card loading"><div class="spinner-sm"></div></div>
        <div class="stat-card loading"><div class="spinner-sm"></div></div>
        <div class="stat-card loading"><div class="spinner-sm"></div></div>
      </div>
    </div>

    <!-- ===== USERS TAB ===== -->
    <div class="tab-content" id="tab-users">
      <h2 class="section-title">User Management</h2>

      <!-- Manual VIP Activation -->
      <div class="panel glass-panel">
        <h3 class="panel-title">🔑 Manual VIP Activation</h3>
        <p class="panel-desc">Activate VIP for a user manually. Use this when payment succeeds but VIP wasn't applied.</p>
        <div class="form-row">
          <div class="form-group">
            <label>User Email</label>
            <input type="email" id="vip-email" placeholder="user@email.com">
          </div>
          <div class="form-group" style="max-width:140px">
            <label>Days</label>
            <input type="number" id="vip-days" value="17" min="1" max="365">
          </div>
        </div>
        <div class="btn-row">
          <button class="btn-orange" onclick="activateVIP()">✅ Activate VIP</button>
          <button class="btn-outline" onclick="revokeVIP()">❌ Revoke VIP</button>
        </div>
        <div class="action-msg" id="vip-msg"></div>
      </div>

      <!-- Search Users -->
      <div class="panel glass-panel" style="margin-top:1.5rem">
        <h3 class="panel-title">🔍 Search Users</h3>
        <div class="form-row">
          <div class="form-group" style="flex:1">
            <input type="email" id="search-email" placeholder="Search by email...">
          </div>
          <button class="btn-orange" style="margin-top:0;align-self:flex-end" onclick="searchUsers()">Search</button>
        </div>
        <div id="search-results" class="user-table-wrap"></div>
      </div>

      <!-- All Users -->
      <div class="panel glass-panel" style="margin-top:1.5rem">
        <h3 class="panel-title">📋 All Users</h3>
        <div id="users-table" class="user-table-wrap">
          <div class="loading-text">Loading users...</div>
        </div>
        <div class="pagination" id="users-pagination"></div>
      </div>
    </div>

    <!-- ===== ORDERS TAB ===== -->
    <div class="tab-content" id="tab-orders">
      <h2 class="section-title">Order History</h2>
      <div class="panel glass-panel">
        <div id="orders-table" class="user-table-wrap">
          <div class="loading-text">Loading orders...</div>
        </div>
        <div class="pagination" id="orders-pagination"></div>
      </div>
    </div>

    <!-- ===== SETTINGS TAB ===== -->
    <div class="tab-content" id="tab-settings">
      <h2 class="section-title">Site Settings</h2>

      <div class="panel glass-panel">
        <h3 class="panel-title">💰 Payment Settings</h3>
        <div class="form-group">
          <label>Payment Amount (₹)</label>
          <input type="number" id="s-payment_amount" min="1" placeholder="e.g. 1">
        </div>
        <div class="form-group">
          <label>AllAPI Token</label>
          <input type="text" id="s-allapi_token" placeholder="Your AllAPI token">
          <small class="help-text">Get from allapi.in · You can store multiple tokens here</small>
        </div>
        <div class="form-group">
          <label>VIP Validity (days)</label>
          <input type="number" id="s-vip_days" min="1" placeholder="17">
        </div>
      </div>

      <div class="panel glass-panel" style="margin-top:1.25rem">
        <h3 class="panel-title">🔗 Social & Links</h3>
        <div class="form-group">
          <label>Telegram Link</label>
          <input type="url" id="s-telegram_link" placeholder="https://t.me/yourchannel">
        </div>
        <div class="form-group">
          <label>YouTube Link</label>
          <input type="url" id="s-youtube_link" placeholder="https://youtube.com/@channel">
        </div>
        <div class="form-group">
          <label>WhatsApp Link</label>
          <input type="url" id="s-whatsapp_link" placeholder="https://wa.me/91XXXXXXXXXX">
        </div>
        <div class="form-group">
          <label>Register in YaarWin Link</label>
          <input type="url" id="s-yaarwin_link" placeholder="https://yaarwin.com/ref/xxx">
        </div>
      </div>

      <div class="panel glass-panel" style="margin-top:1.25rem">
        <h3 class="panel-title">🔒 Admin Password</h3>
        <div class="form-group">
          <label>New Admin Password <small style="color:var(--dim);font-weight:400">(leave blank to keep current)</small></label>
          <input type="password" id="s-admin_password" placeholder="Min 6 characters">
        </div>
      </div>

      <button class="btn-orange" style="margin-top:1rem;padding:0.85rem 2.5rem" onclick="saveSettings()">
        💾 Save All Settings
      </button>
      <div class="action-msg" id="settings-msg"></div>
    </div>

  </div><!-- /admin-main -->
</div><!-- /admin-layout -->

<script src="admin-app.js"></script>
</body>
</html>
