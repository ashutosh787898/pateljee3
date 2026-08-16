<?php
require_once 'includes/config.php';
requireAuth();
$user    = currentUser();
$vip     = isVIP($user);
$payAmt  = (int)setting('payment_amount','798');
$vipDays = (int)setting('vip_days','17');
$tgLink  = setting('telegram_link','#');
$ytLink  = setting('youtube_link','#');
$waLink  = setting('whatsapp_link','#');
$jwLink  = setting('yaarwin_link','#');

$msgs = [
  'vip_activated'     => ['VIP ACTIVATED — '.$vipDays.' days unlocked ✓','success'],
  'payment_pending'   => ['Payment pending. Please wait and refresh.','info'],
  'payment_failed'    => ['Payment failed. Try again.','error'],
  'already_activated' => ['VIP already active.','info'],
  'order_not_found'   => ['Order not found. Contact support.','error'],
];
$flash=''; $ft='';
if (!empty($_GET['msg']) && isset($msgs[$_GET['msg']])) [$flash,$ft]=$msgs[$_GET['msg']];
$vipExpiry = $vip ? date('d M Y',strtotime($user['vip_expires_at'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="images/favicon.ico">
<link rel="icon" type="image/png"    href="images/favicon.png">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>JaiClub Analyser Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Nunito:wght@400;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="bg-field"></div>
<div class="ambient" id="ambient"></div>

<?php if($flash): ?>
<div class="toast toast-<?=$ft?>" id="flash-toast"><?=htmlspecialchars($flash)?></div>
<?php endif; ?>

<!-- ── Header ── -->
<header class="hud-header">
  <div class="header-brand">
    <div class="header-icon-wrap">
      <img src="images/logo.png" alt="" class="header-logo-img" id="header-logo-img">
      <span class="header-icon-emoji" style="display:none">🎯</span>
    </div>
    <span class="header-title"><span class="grad-text">JaiClub</span> Analyser Pro</span>
  </div>
  <div class="header-right">
    <?php if($vip): ?>
    <div class="vip-badge">👑 VIP <span class="vip-exp">· <?=$vipExpiry?></span></div>
    <?php else: ?>
    <div class="non-vip-badge">FREE</div>
    <?php endif; ?>
    <a href="logout.php" class="logout-btn">EXIT</a>
  </div>
</header>

<!-- ── Signal bar ── -->
<div class="signal-bar">
  <span class="sig-dot"></span>
  <span class="sig-label">SIGNAL ACTIVE</span>
  <span class="sig-sep">·</span>
  <span class="sig-name">JaiClub Win Go 1 Min</span>
</div>

<main class="page-body">

  <!-- ════ ROUND HUD — LED Countdown ════ -->
  <div class="round-hud">
    <span class="hud-br"></span>
    <div class="round-top">
      <div>
        <div class="round-lbl">ROUND ID</div>
        <div class="round-id-val" id="round-id">Syncing...</div>
      </div>
      <div class="time-digits" id="timer-disp">00:59</div>
    </div>
    <div class="seg-track" id="seg-track">
      <?php for($i=0;$i<30;$i++): ?>
      <span class="seg" data-i="<?=$i?>"></span>
      <?php endfor; ?>
    </div>
  </div>

  <!-- ════ PREDICTION ZONE ════ -->
  <div class="predict-zone">
    <span class="pz-br"></span>
    <div class="zone-header">
      <span class="zone-title">◈ PREDICTION SYSTEM</span>
      <?php if($vip): ?>
        <span class="zone-badge-vip">VIP · ACTIVE</span>
      <?php else: ?>
        <span class="zone-badge-lock">LOCKED</span>
      <?php endif; ?>
    </div>

    <div class="target-frame" id="target-frame">
      <span class="rc tl"></span>
      <span class="rc tr"></span>
      <span class="rc bl"></span>
      <span class="rc br"></span>

      <?php if($vip): ?>
      <div class="t-waiting" id="result-display">
        <div class="t-waiting-icon">◈</div>
        <div>Tap ENGAGE to initialise</div>
      </div>
      <?php else: ?>
      <div class="t-locked">
        <div class="lock-emoji">🔒</div>
        <div>VIP access required</div>
        <div style="font-size:0.72rem;margin-top:0.15rem;color:#6a3d8f">Tap ENGAGE to activate</div>
      </div>
      <?php endif; ?>
    </div>

    <button class="engage-btn" id="start-btn" onclick="handleStart()">
      ▶▶&nbsp; ENGAGE ANALYSER SYSTEM
    </button>
  </div>

  <!-- ════ LIVE DATA FEED (Ticker) ════ -->
  <div class="feed-section">
    <div class="feed-header">
      <span class="feed-dot"></span>
      LIVE DATA FEED &mdash; JAICLUB 1 MIN
    </div>
    <div class="ticker-outer">
      <div class="ticker-inner" id="ticker-inner">
        <span class="feed-loading"><span class="spinner"></span>&nbsp;Loading feed...</span>
      </div>
    </div>
  </div>

  <!-- ════ MISSION BUTTON ════ -->
  <a href="<?=htmlspecialchars($jwLink)?>" target="_blank" rel="noopener" class="mission-btn">
    <img src="images/jaiclub-icon.png" alt="" class="mission-icon"
         onerror="this.style.display='none';this.nextElementSibling.style.removeProperty('display')">
    <span class="mission-icon-emoji" style="display:none">🎮</span>
    <span class="mission-text">Register in JaiClub</span>
    <span class="mission-arrow">──→</span>
  </a>

</main>

<!-- Social float -->
<div class="social-float">
  <a href="<?=htmlspecialchars($tgLink)?>" target="_blank" rel="noopener" class="social-btn tg-btn" title="Telegram">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8l-1.7 8.02c-.12.57-.46.71-.93.44l-2.57-1.89-1.24 1.19c-.14.14-.26.26-.52.26l.19-2.62 4.8-4.34c.21-.18-.05-.29-.32-.1L7.8 15.18l-2.52-.79c-.55-.17-.56-.55.12-.82l9.84-3.79c.46-.17.86.11.4.82z"/></svg>
  </a>
  <a href="<?=htmlspecialchars($ytLink)?>" target="_blank" rel="noopener" class="social-btn yt-btn" title="YouTube">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M21.58 7.19c-.23-.86-.91-1.54-1.77-1.77C18.25 5 12 5 12 5s-6.25 0-7.81.42c-.86.23-1.54.91-1.77 1.77C2 8.75 2 12 2 12s0 3.25.42 4.81c.23.86.91 1.54 1.77 1.77C5.75 19 12 19 12 19s6.25 0 7.81-.42c.86-.23 1.54-.91 1.77-1.77C22 15.25 22 12 22 12s0-3.25-.42-4.81zM10 15V9l5.2 3-5.2 3z"/></svg>
  </a>
  <a href="<?=htmlspecialchars($waLink)?>" target="_blank" rel="noopener" class="social-btn wa-btn" title="WhatsApp">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
  </a>
</div>

<!-- VIP Modal -->
<div class="modal-overlay" id="vip-modal" style="display:none">
  <div class="modal-box">
    <div class="modal-icon">🔐</div>
    <h2 class="modal-title">UNLOCK VIP ACCESS</h2>
    <p class="modal-desc">Activate to receive colour predictions every round.<br>Valid for <strong><?=$vipDays?> days</strong>.</p>
    <div class="modal-price">₹<?=$payAmt?></div>
    <div class="modal-note">Small variable amount added per transaction</div>
    <button class="btn-pay" id="pay-btn" onclick="initPayment()">⚡ PAY & ACTIVATE VIP</button>
    <button class="btn-cancel" onclick="closeModal()">Cancel</button>
  </div>
</div>

<!-- Processing -->
<div class="processing-overlay" id="processing" style="display:none">
  <div class="big-spinner"></div>
  <p id="processing-text">REDIRECTING TO PAYMENT...</p>
</div>

<script>
const IS_VIP  = <?=$vip?'true':'false'?>;
const PAY_AMT = <?=$payAmt?>;
</script>
<script src="app.js"></script>
</body>
</html>
