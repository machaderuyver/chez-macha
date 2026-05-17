<?php
session_start();

/* ── Auth ── */
define('ADMIN_PASSWORD', 'macha2026'); // À changer !

$logout = isset($_GET['logout']);
if ($logout) { session_destroy(); header('Location: admin-bons.php'); exit; }

$error = false;
if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_bons'] = true;
    } else {
        $error = true;
    }
}
if (!isset($_SESSION['admin_bons']) || !$_SESSION['admin_bons']) {
    ?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin — Chez Macha</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;700;900&family=Didact+Gothic&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #071326; color: #F6F1E8; font-family: 'Didact Gothic', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .login-box { background: #0c1d33; border: 1px solid rgba(244,194,26,0.25); border-radius: 16px; padding: 2.5rem 2rem; width: 100%; max-width: 380px; }
    .login-box h1 { font-family: 'Jost', sans-serif; font-size: 1.3rem; font-weight: 900; color: #F4C21A; margin-bottom: 0.25rem; }
    .login-box p { color: #8a94a8; font-size: 0.85rem; margin-bottom: 1.75rem; }
    label { font-family: 'Jost', sans-serif; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #8a94a8; display: block; margin-bottom: 0.4rem; }
    input[type="password"] { width: 100%; background: #060d1c; border: 1px solid rgba(244,194,26,0.2); color: #F6F1E8; border-radius: 8px; padding: 0.65em 1em; font-family: 'Didact Gothic', sans-serif; font-size: 0.95rem; outline: none; }
    input[type="password"]:focus { border-color: #F4C21A; }
    .btn-login { width: 100%; margin-top: 1rem; background: #F4C21A; color: #060917; font-family: 'Jost', sans-serif; font-weight: 900; font-size: 0.85rem; letter-spacing: 0.1em; text-transform: uppercase; border: none; border-radius: 8px; padding: 0.8em; cursor: pointer; transition: background 0.2s; }
    .btn-login:hover { background: #ffd84a; }
    .error { color: #D71920; font-size: 0.83rem; margin-top: 0.75rem; }
  </style>
</head>
<body>
  <div class="login-box">
    <h1>Chez Macha · Admin</h1>
    <p>Gestion des bons cadeaux</p>
    <form method="POST">
      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" autofocus />
      <button type="submit" class="btn-login">Connexion</button>
      <?php if ($error): ?><p class="error">Mot de passe incorrect.</p><?php endif; ?>
    </form>
  </div>
</body>
</html><?php
    exit;
}

/* ── Chargement des commandes ── */
$json_file = __DIR__ . '/commandes-bons.json';
$commandes = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
if (!is_array($commandes)) $commandes = [];

/* Tri : plus récent en premier */
usort($commandes, fn($a, $b) => strcmp($b['date'], $a['date']));

$nb_attente   = count(array_filter($commandes, fn($c) => $c['statut'] === 'en_attente'));
$nb_confirme  = count(array_filter($commandes, fn($c) => $c['statut'] === 'confirme'));
$total_confirme = array_sum(array_map(fn($c) => $c['statut'] === 'confirme' ? $c['montant'] : 0, $commandes));
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Bons cadeaux — Chez Macha</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;600;700;800;900&family=Didact+Gothic&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #071326; color: #F6F1E8; font-family: 'Didact Gothic', sans-serif; font-size: 15px; line-height: 1.6; min-height: 100vh; }
    a { color: #F4C21A; text-decoration: none; }

    /* Header */
    .admin-header { background: #0c1d33; border-bottom: 1px solid rgba(244,194,26,0.25); padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .admin-header h1 { font-family: 'Jost', sans-serif; font-size: 1rem; font-weight: 900; color: #F4C21A; text-transform: uppercase; letter-spacing: 0.08em; }
    .logout-btn { font-family: 'Jost', sans-serif; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #8a94a8; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 0.4em 0.9em; text-decoration: none; transition: color 0.2s, border-color 0.2s; }
    .logout-btn:hover { color: #F6F1E8; border-color: rgba(255,255,255,0.25); }

    /* Content */
    .content { max-width: 960px; margin: 0 auto; padding: 2rem; }

    /* Stats */
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; }
    .stat-card { background: #0c1d33; border: 1px solid rgba(244,194,26,0.2); border-radius: 12px; padding: 1.25rem; }
    .stat-label { font-family: 'Jost', sans-serif; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #8a94a8; margin-bottom: 0.4rem; }
    .stat-val { font-family: 'Jost', sans-serif; font-size: 1.8rem; font-weight: 900; color: #F4C21A; line-height: 1; }
    .stat-val.red { color: #D71920; }

    /* Filtre */
    .filter-row { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; }
    .filter-btn { font-family: 'Jost', sans-serif; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; padding: 0.4em 1em; border-radius: 6px; cursor: pointer; border: 1px solid rgba(244,194,26,0.25); background: transparent; color: #8a94a8; transition: all 0.2s; }
    .filter-btn.active, .filter-btn:hover { background: rgba(244,194,26,0.12); border-color: #F4C21A; color: #F6F1E8; }

    /* Table */
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th { font-family: 'Jost', sans-serif; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #8a94a8; text-align: left; padding: 0.6rem 1rem; border-bottom: 1px solid rgba(244,194,26,0.12); white-space: nowrap; }
    td { padding: 0.9rem 1rem; border-bottom: 1px solid rgba(244,194,26,0.06); font-size: 0.88rem; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(244,194,26,0.03); }
    .td-id { font-family: 'Didact Gothic', monospace; font-size: 0.8rem; color: #F4C21A; white-space: nowrap; }
    .td-montant { font-family: 'Jost', sans-serif; font-weight: 900; font-size: 1.05rem; color: #F4C21A; white-space: nowrap; }
    .td-date { color: #8a94a8; font-size: 0.8rem; white-space: nowrap; }
    .td-dest { color: #8a94a8; font-size: 0.82rem; }

    /* Badges */
    .badge-attente { display: inline-block; font-family: 'Jost', sans-serif; font-size: 0.62rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; background: rgba(215,25,32,0.15); color: #D71920; border: 1px solid rgba(215,25,32,0.3); border-radius: 5px; padding: 0.2em 0.55em; }
    .badge-confirme { display: inline-block; font-family: 'Jost', sans-serif; font-size: 0.62rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; background: rgba(34,197,94,0.12); color: #22c55e; border: 1px solid rgba(34,197,94,0.3); border-radius: 5px; padding: 0.2em 0.55em; }

    /* Bouton paiement */
    .btn-confirm { font-family: 'Jost', sans-serif; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; background: #22c55e; color: #060917; border: none; border-radius: 6px; padding: 0.45em 0.9em; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35em; transition: all 0.2s; white-space: nowrap; }
    .btn-confirm:hover { background: #16a34a; transform: translateY(-1px); }
    .btn-confirm:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

    /* Toast */
    #toast { position: fixed; bottom: 1.5rem; right: 1.5rem; background: #22c55e; color: #060917; font-family: 'Jost', sans-serif; font-weight: 800; font-size: 0.85rem; padding: 0.75rem 1.25rem; border-radius: 8px; display: none; z-index: 9999; box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
    #toast.error { background: #D71920; color: #F6F1E8; }

    .empty { color: #8a94a8; font-size: 0.9rem; text-align: center; padding: 3rem; }

    /* Message perso expand */
    .msg-toggle { background: none; border: none; color: #8a94a8; font-size: 0.75rem; cursor: pointer; text-decoration: underline; padding: 0; font-family: 'Didact Gothic', sans-serif; }
    .msg-toggle:hover { color: #F4C21A; }

    @media (max-width: 700px) {
      .stats-grid { grid-template-columns: 1fr 1fr; }
      .content { padding: 1rem; }
      td, th { padding: 0.7rem 0.6rem; }
    }
  </style>
</head>
<body>
  <div class="admin-header">
    <h1>🎭 Admin — Bons cadeaux</h1>
    <a href="?logout=1" class="logout-btn">Déconnexion</a>
  </div>

  <div class="content">
    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <p class="stat-label">En attente</p>
        <p class="stat-val red"><?= $nb_attente ?></p>
      </div>
      <div class="stat-card">
        <p class="stat-label">Confirmés</p>
        <p class="stat-val"><?= $nb_confirme ?></p>
      </div>
      <div class="stat-card">
        <p class="stat-label">Total encaissé</p>
        <p class="stat-val"><?= $total_confirme ?>€</p>
      </div>
    </div>

    <!-- Filtre -->
    <div class="filter-row">
      <button class="filter-btn active" onclick="filtrer('tous', this)">Tous (<?= count($commandes) ?>)</button>
      <button class="filter-btn" onclick="filtrer('en_attente', this)">En attente (<?= $nb_attente ?>)</button>
      <button class="filter-btn" onclick="filtrer('confirme', this)">Confirmés (<?= $nb_confirme ?>)</button>
    </div>

    <!-- Tableau -->
    <?php if (empty($commandes)): ?>
      <p class="empty">Aucune commande pour l'instant.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table id="commandesTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Montant</th>
            <th>Acheteur</th>
            <th>Email</th>
            <th>Destinataire</th>
            <th>Statut</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($commandes as $c): ?>
          <tr data-statut="<?= htmlspecialchars($c['statut']) ?>" data-id="<?= htmlspecialchars($c['id']) ?>">
            <td class="td-id"><?= htmlspecialchars($c['id']) ?></td>
            <td class="td-date"><?= htmlspecialchars(substr($c['date'], 0, 16)) ?></td>
            <td class="td-montant"><?= (int)$c['montant'] ?>€</td>
            <td><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></td>
            <td><a href="mailto:<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></a></td>
            <td class="td-dest">
              <?= $c['prenom_destinataire'] ? htmlspecialchars($c['prenom_destinataire']) : '<span style="color:#555e70;">—</span>' ?>
              <?php if (!empty($c['message'])): ?>
                <br><button class="msg-toggle" onclick="toggleMsg(this)" data-msg="<?= htmlspecialchars($c['message']) ?>">Voir message</button>
                <span class="msg-text" style="display:none;color:#8a94a8;font-style:italic;font-size:0.8rem;display:block;"></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($c['statut'] === 'en_attente'): ?>
                <span class="badge-attente">En attente</span>
              <?php else: ?>
                <span class="badge-confirme">Confirmé ✓</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($c['statut'] === 'en_attente'): ?>
                <button class="btn-confirm" onclick="confirmerPaiement('<?= htmlspecialchars($c['id']) ?>', this)">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  Paiement reçu
                </button>
              <?php else: ?>
                <span style="color:#555e70;font-size:0.8rem;">Bon envoyé</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div id="toast"></div>

  <script>
    function filtrer(statut, btn) {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('#commandesTable tbody tr').forEach(function(row) {
        row.style.display = (statut === 'tous' || row.dataset.statut === statut) ? '' : 'none';
      });
    }

    function confirmerPaiement(id, btn) {
      if (!confirm('Confirmer le paiement et envoyer le bon cadeau PDF à l\'acheteur ?')) return;
      btn.disabled = true;
      btn.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border:2px solid rgba(6,9,23,0.3);border-top-color:#060917;border-radius:50%;animation:spin 0.7s linear infinite;"></span> Envoi…';

      fetch('confirmer-paiement.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.ok) {
          showToast('Bon cadeau envoyé à ' + data.email + ' !');
          var row = btn.closest('tr');
          row.querySelector('td:nth-child(7)').innerHTML = '<span class="badge-confirme">Confirmé ✓</span>';
          row.querySelector('td:nth-child(8)').innerHTML = '<span style="color:#555e70;font-size:0.8rem;">Bon envoyé</span>';
          row.dataset.statut = 'confirme';
        } else {
          showToast('Erreur : ' + (data.error || 'inconnue'), true);
          btn.disabled = false;
          btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Réessayer';
        }
      })
      .catch(function() {
        showToast('Erreur réseau.', true);
        btn.disabled = false;
        btn.innerHTML = 'Réessayer';
      });
    }

    function showToast(msg, isError) {
      var t = document.getElementById('toast');
      t.textContent = msg;
      t.className = isError ? 'error' : '';
      t.style.display = 'block';
      setTimeout(function() { t.style.display = 'none'; }, 4000);
    }

    function toggleMsg(btn) {
      var span = btn.nextElementSibling;
      if (span.style.display === 'none' || !span.style.display) {
        span.textContent = btn.dataset.msg;
        span.style.display = 'block';
        btn.textContent = 'Masquer';
      } else {
        span.style.display = 'none';
        btn.textContent = 'Voir message';
      }
    }
  </script>
  <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
</body>
</html>
