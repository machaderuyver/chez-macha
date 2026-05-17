<?php
session_start();
define('ADMIN_PASSWORD', 'macha2026');

/* ── Auth ── */
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin-sondage-dangagnon.php'); exit; }

if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) $_SESSION['admin_sondage'] = true;
    else $login_error = true;
}

if (empty($_SESSION['admin_sondage'])) { ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Admin sondage — Chez Macha</title>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;700;900&family=Didact+Gothic&display=swap" rel="stylesheet" />
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{background:#071326;color:#F6F1E8;font-family:'Didact Gothic',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;}
    .box{background:#0c1d33;border:1px solid rgba(244,194,26,.25);border-radius:16px;padding:2.5rem 2rem;width:100%;max-width:380px;}
    h1{font-family:'Jost',sans-serif;font-size:1.2rem;font-weight:900;color:#F4C21A;margin-bottom:.2rem;}
    p{color:#8a94a8;font-size:.85rem;margin-bottom:1.75rem;}
    label{font-family:'Jost',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#8a94a8;display:block;margin-bottom:.4rem;}
    input{width:100%;background:#060d1c;border:1px solid rgba(244,194,26,.2);color:#F6F1E8;border-radius:8px;padding:.65em 1em;font-size:.95rem;outline:none;}
    input:focus{border-color:#F4C21A;}
    button{width:100%;margin-top:1rem;background:#F4C21A;color:#060917;font-family:'Jost',sans-serif;font-weight:900;font-size:.85rem;letter-spacing:.1em;text-transform:uppercase;border:none;border-radius:8px;padding:.8em;cursor:pointer;}
    button:hover{background:#ffd84a;}
    .err{color:#D71920;font-size:.83rem;margin-top:.75rem;}
  </style>
</head>
<body>
  <div class="box">
    <h1>Chez Macha · Admin</h1>
    <p>Sondage Dan Gagnon</p>
    <form method="POST">
      <label>Mot de passe</label>
      <input type="password" name="password" autofocus />
      <button type="submit">Connexion</button>
      <?php if (!empty($login_error)): ?><p class="err">Mot de passe incorrect.</p><?php endif; ?>
    </form>
  </div>
</body></html>
<?php exit; }

/* ── Chargement données ── */
$json_file = __DIR__ . '/reponses-sondage-dan-gagnon.json';
$reponses  = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
if (!is_array($reponses)) $reponses = [];

/* Tri : plus récent en premier */
usort($reponses, fn($a, $b) => strcmp($b['date'], $a['date']));
$total = count($reponses);

/* ── Export CSV ── */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="sondage-dan-gagnon-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 pour Excel
    fputcsv($out, ['Date', 'Tu connais Dan Gagnon ?', 'Tu viendrais le voir ?', 'Déjà chez Macha ?', 'Disponibilité', 'Email'], ';');
    foreach (array_reverse($reponses) as $r) {
        fputcsv($out, [
            $r['date'],
            $r['q1_label'] ?? $r['q1'],
            $r['q2_label'] ?? $r['q2'],
            $r['q3_label'] ?? ($r['q3'] ?: '—'),
            $r['q4_label'] ?? ($r['q4'] ?: '—'),
            $r['email'] ?: '—',
        ], ';');
    }
    fclose($out);
    exit;
}

/* ── Stats agrégées ── */
function compte($reponses, $champ, $val) {
    return count(array_filter($reponses, fn($r) => ($r[$champ] ?? '') === $val));
}
$stats_q1 = [
    'fan'     => compte($reponses, 'q1', 'fan'),
    'connais' => compte($reponses, 'q1', 'connais'),
    'non'     => compte($reponses, 'q1', 'non'),
];
$stats_q2 = [
    'oui-direct' => compte($reponses, 'q2', 'oui-direct'),
    'oui-prix'   => compte($reponses, 'q2', 'oui-prix'),
    'peut-etre'  => compte($reponses, 'q2', 'peut-etre'),
    'non'        => compte($reponses, 'q2', 'non'),
];
$nb_emails = count(array_filter($reponses, fn($r) => !empty($r['email'])));
$pct = fn($n) => $total > 0 ? round($n / $total * 100) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Admin sondage Dan Gagnon — Chez Macha</title>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;600;700;800;900&family=Didact+Gothic&display=swap" rel="stylesheet" />
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{background:#071326;color:#F6F1E8;font-family:'Didact Gothic',sans-serif;font-size:15px;line-height:1.6;min-height:100vh;}
    a{color:#F4C21A;text-decoration:none;}

    /* Header */
    .hdr{background:#0c1d33;border-bottom:1px solid rgba(244,194,26,.25);padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
    .hdr h1{font-family:'Jost',sans-serif;font-size:.95rem;font-weight:900;color:#F4C21A;text-transform:uppercase;letter-spacing:.08em;}
    .hdr-actions{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;}
    .btn-export{font-family:'Jost',sans-serif;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;background:#F4C21A;color:#060917;border:none;border-radius:6px;padding:.45em 1em;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:.35em;transition:background .2s;}
    .btn-export:hover{background:#ffd84a;}
    .btn-logout{font-family:'Jost',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#8a94a8;border:1px solid rgba(255,255,255,.1);border-radius:6px;padding:.4em .9em;transition:color .2s,border-color .2s;}
    .btn-logout:hover{color:#F6F1E8;border-color:rgba(255,255,255,.25);}

    /* Layout */
    .content{max-width:1100px;margin:0 auto;padding:2rem;}

    /* Stat cards */
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;}
    .stat{background:#0c1d33;border:1px solid rgba(244,194,26,.2);border-radius:12px;padding:1.25rem;}
    .stat-lbl{font-family:'Jost',sans-serif;font-size:.63rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#8a94a8;margin-bottom:.4rem;}
    .stat-val{font-family:'Jost',sans-serif;font-size:2rem;font-weight:900;color:#F4C21A;line-height:1;}
    .stat-sub{font-size:.78rem;color:#8a94a8;margin-top:.25rem;}

    /* Barres stats */
    .charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:2rem;}
    .chart-card{background:#0c1d33;border:1px solid rgba(244,194,26,.15);border-radius:12px;padding:1.25rem;}
    .chart-title{font-family:'Jost',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#8a94a8;margin-bottom:1rem;}
    .bar-row{display:flex;flex-direction:column;gap:.25rem;margin-bottom:.75rem;}
    .bar-row:last-child{margin-bottom:0;}
    .bar-label{font-size:.82rem;color:#F6F1E8;display:flex;justify-content:space-between;}
    .bar-label span{color:#8a94a8;font-size:.78rem;}
    .bar-track{height:6px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden;}
    .bar-fill{height:100%;background:#F4C21A;border-radius:3px;transition:width .4s;}
    .bar-fill.green{background:#22c55e;}
    .bar-fill.red{background:#D71920;}
    .bar-fill.muted{background:#8a94a8;}

    /* Section label */
    .section-lbl{font-family:'Jost',sans-serif;font-size:.68rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#8a94a8;margin-bottom:.75rem;}

    /* Tableau */
    .table-wrap{overflow-x:auto;background:#0c1d33;border:1px solid rgba(244,194,26,.12);border-radius:12px;}
    table{width:100%;border-collapse:collapse;min-width:700px;}
    th{font-family:'Jost',sans-serif;font-size:.62rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#8a94a8;text-align:left;padding:.65rem 1rem;border-bottom:1px solid rgba(244,194,26,.1);white-space:nowrap;}
    td{padding:.8rem 1rem;border-bottom:1px solid rgba(244,194,26,.05);font-size:.83rem;vertical-align:middle;}
    tr:last-child td{border-bottom:none;}
    tr:hover td{background:rgba(244,194,26,.03);}
    .td-date{color:#8a94a8;font-size:.78rem;white-space:nowrap;}
    .td-email{color:#F4C21A;font-size:.8rem;}
    .chip{display:inline-block;font-family:'Jost',sans-serif;font-size:.6rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;border-radius:5px;padding:.2em .55em;white-space:nowrap;}
    .chip-fan{background:rgba(244,194,26,.15);color:#F4C21A;border:1px solid rgba(244,194,26,.3);}
    .chip-connais{background:rgba(34,197,94,.1);color:#22c55e;border:1px solid rgba(34,197,94,.3);}
    .chip-non{background:rgba(138,148,168,.1);color:#8a94a8;border:1px solid rgba(138,148,168,.25);}
    .chip-oui-direct{background:rgba(34,197,94,.12);color:#22c55e;border:1px solid rgba(34,197,94,.3);}
    .chip-oui-prix{background:rgba(244,194,26,.12);color:#F4C21A;border:1px solid rgba(244,194,26,.3);}
    .chip-peut-etre{background:rgba(251,146,60,.1);color:#fb923c;border:1px solid rgba(251,146,60,.3);}
    .chip-non-q2{background:rgba(215,25,32,.12);color:#D71920;border:1px solid rgba(215,25,32,.3);}
    .empty{color:#8a94a8;text-align:center;padding:3rem;font-size:.9rem;}

    /* Filtre email */
    .filter-row{display:flex;gap:.5rem;margin-bottom:1rem;align-items:center;flex-wrap:wrap;}
    .filter-input{background:#060d1c;border:1px solid rgba(244,194,26,.2);color:#F6F1E8;border-radius:7px;padding:.45em .9em;font-size:.82rem;outline:none;font-family:'Didact Gothic',sans-serif;width:220px;}
    .filter-input:focus{border-color:#F4C21A;}
    .filter-count{font-size:.8rem;color:#8a94a8;margin-left:.25rem;}

    @media(max-width:700px){
      .stats-grid{grid-template-columns:1fr 1fr;}
      .charts-grid{grid-template-columns:1fr;}
      .content{padding:1rem;}
    }
  </style>
</head>
<body>
  <div class="hdr">
    <h1>🎤 Sondage Dan Gagnon — <?= $total ?> réponse<?= $total > 1 ? 's' : '' ?></h1>
    <div class="hdr-actions">
      <?php if ($total > 0): ?>
      <a href="?export=csv" class="btn-export">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exporter CSV
      </a>
      <?php endif; ?>
      <a href="?logout=1" class="btn-logout">Déconnexion</a>
    </div>
  </div>

  <div class="content">

    <!-- Stats globales -->
    <div class="stats-grid">
      <div class="stat">
        <p class="stat-lbl">Total réponses</p>
        <p class="stat-val"><?= $total ?></p>
      </div>
      <div class="stat">
        <p class="stat-lbl">Veulent venir</p>
        <p class="stat-val"><?= $stats_q2['oui-direct'] + $stats_q2['oui-prix'] ?></p>
        <p class="stat-sub"><?= $pct($stats_q2['oui-direct'] + $stats_q2['oui-prix']) ?>% des répondants</p>
      </div>
      <div class="stat">
        <p class="stat-lbl">Fans / Connaissent</p>
        <p class="stat-val"><?= $stats_q1['fan'] + $stats_q1['connais'] ?></p>
        <p class="stat-sub"><?= $pct($stats_q1['fan'] + $stats_q1['connais']) ?>% connaissent Dan</p>
      </div>
      <div class="stat">
        <p class="stat-lbl">Emails collectés</p>
        <p class="stat-val"><?= $nb_emails ?></p>
        <p class="stat-sub"><?= $pct($nb_emails) ?>% ont laissé un email</p>
      </div>
    </div>

    <!-- Graphiques -->
    <?php if ($total > 0): ?>
    <div class="charts-grid">
      <div class="chart-card">
        <p class="chart-title">Tu connais Dan Gagnon ?</p>
        <?php foreach (['fan' => 'Oui, je suis fan', 'connais' => 'Oui, je le connais', 'non' => 'Non, pas encore'] as $val => $lbl): ?>
        <div class="bar-row">
          <div class="bar-label"><?= $lbl ?> <span><?= $stats_q1[$val] ?> (<?= $pct($stats_q1[$val]) ?>%)</span></div>
          <div class="bar-track"><div class="bar-fill <?= $val === 'non' ? 'muted' : '' ?>" style="width:<?= $pct($stats_q1[$val]) ?>%"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="chart-card">
        <p class="chart-title">Tu viendrais voir Dan Gagnon Chez Macha ?</p>
        <?php $fills = ['oui-direct' => 'green', 'oui-prix' => '', 'peut-etre' => '', 'non' => 'red'];
        $labels = ['oui-direct' => 'Oui, je réserve direct !', 'oui-prix' => 'Oui, si le prix est correct', 'peut-etre' => 'Peut-être', 'non' => 'Non'];
        foreach ($labels as $val => $lbl): ?>
        <div class="bar-row">
          <div class="bar-label"><?= $lbl ?> <span><?= $stats_q2[$val] ?> (<?= $pct($stats_q2[$val]) ?>%)</span></div>
          <div class="bar-track"><div class="bar-fill <?= $fills[$val] ?>" style="width:<?= $pct($stats_q2[$val]) ?>%"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Tableau détaillé -->
    <div class="section-lbl">Toutes les réponses</div>

    <div class="filter-row">
      <input type="text" class="filter-input" id="filterInput" placeholder="Filtrer par email, réponse…" oninput="filtrerTableau(this.value)" />
      <span class="filter-count" id="filterCount"><?= $total ?> résultat<?= $total > 1 ? 's' : '' ?></span>
    </div>

    <?php if ($total === 0): ?>
      <p class="empty">Aucune réponse pour l'instant. Le fichier JSON sera créé dès la première soumission.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table id="reponsesTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Connaît Dan ?</th>
            <th>Viendrait ?</th>
            <th>Déjà Chez Macha ?</th>
            <th>Dispo</th>
            <th>Email</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reponses as $i => $r):
            $chip_q1 = match($r['q1']) { 'fan' => 'chip-fan', 'connais' => 'chip-connais', default => 'chip-non' };
            $chip_q2 = match($r['q2']) { 'oui-direct' => 'chip-oui-direct', 'oui-prix' => 'chip-oui-prix', 'peut-etre' => 'chip-peut-etre', default => 'chip-non-q2' };
          ?>
          <tr>
            <td style="color:#555e70;font-size:.75rem;"><?= $total - $i ?></td>
            <td class="td-date"><?= htmlspecialchars(substr($r['date'], 0, 16)) ?></td>
            <td><span class="chip <?= $chip_q1 ?>"><?= htmlspecialchars($r['q1_label'] ?? $r['q1']) ?></span></td>
            <td><span class="chip <?= $chip_q2 ?>"><?= htmlspecialchars($r['q2_label'] ?? $r['q2']) ?></span></td>
            <td style="color:#8a94a8;font-size:.8rem;"><?= htmlspecialchars($r['q3_label'] ?? ($r['q3'] ? $r['q3'] : '—')) ?></td>
            <td style="color:#8a94a8;font-size:.8rem;"><?= htmlspecialchars($r['q4_label'] ?? ($r['q4'] ? $r['q4'] : '—')) ?></td>
            <td><?php if (!empty($r['email'])): ?><a href="mailto:<?= htmlspecialchars($r['email']) ?>" class="td-email"><?= htmlspecialchars($r['email']) ?></a><?php else: ?><span style="color:#555e70;">—</span><?php endif; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>

  <script>
    function filtrerTableau(val) {
      var rows = document.querySelectorAll('#reponsesTable tbody tr');
      var q = val.toLowerCase();
      var count = 0;
      rows.forEach(function(row) {
        var match = row.textContent.toLowerCase().includes(q);
        row.style.display = match ? '' : 'none';
        if (match) count++;
      });
      var fc = document.getElementById('filterCount');
      if (fc) fc.textContent = count + ' résultat' + (count > 1 ? 's' : '');
    }
  </script>
</body>
</html>
