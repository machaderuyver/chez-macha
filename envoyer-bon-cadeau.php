<?php
header('Content-Type: application/json; charset=utf-8');

/* ── Validation ── */
$montants_ok = [30, 50, 100];
$montant     = isset($_POST['montant']) ? (int)$_POST['montant'] : 0;
$prenom      = isset($_POST['prenom'])  ? trim(strip_tags($_POST['prenom']))  : '';
$nom         = isset($_POST['nom'])     ? trim(strip_tags($_POST['nom']))     : '';
$email       = isset($_POST['email'])   ? trim($_POST['email'])               : '';
$prenom_dest = isset($_POST['prenom_destinataire']) ? trim(strip_tags($_POST['prenom_destinataire'])) : '';
$message     = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

if (!in_array($montant, $montants_ok))       { http_response_code(400); echo json_encode(['error' => 'Montant invalide']);  exit; }
if (empty($prenom) || empty($nom))           { http_response_code(400); echo json_encode(['error' => 'Prénom/nom requis']); exit; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['error' => 'Email invalide']); exit; }

/* ── Génération ID unique ── */
$id            = 'BON-' . date('Y') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
$communication = "Bon cadeau {$montant}€ {$prenom}";
$date_now      = date('Y-m-d H:i:s');

/* ── Sauvegarde dans commandes-bons.json ── */
$json_file  = __DIR__ . '/commandes-bons.json';
$commandes  = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
if (!is_array($commandes)) $commandes = [];

$commandes[] = [
    'id'                  => $id,
    'statut'              => 'en_attente',
    'date'                => $date_now,
    'montant'             => $montant,
    'prenom'              => $prenom,
    'nom'                 => $nom,
    'email'               => $email,
    'prenom_destinataire' => $prenom_dest,
    'message'             => $message,
];

file_put_contents($json_file, json_encode($commandes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

$from        = 'noreply@chezmacha.be';
$headers_html = "From: Chez Macha <{$from}>\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
$admin_url   = 'https://www.chezmacha.be/admin-bons.php';

/* ════════════════════════════════════════════
   EMAIL 1 — Notification interne
   ════════════════════════════════════════════ */
$dest_ligne_admin = $prenom_dest ? "<tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.85rem;width:38%;'>Destinataire</td><td style='padding:0.5rem 0;'>{$prenom_dest}</td></tr>" : '';
$msg_ligne_admin  = $message     ? "<tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.85rem;'>Message</td><td style='padding:0.5rem 0;font-style:italic;'>" . htmlspecialchars($message) . "</td></tr>" : '';

$body_admin = "<!DOCTYPE html><html lang='fr'><body style='font-family:Arial,sans-serif;background:#071326;color:#F6F1E8;padding:2rem;margin:0;'>
<div style='max-width:560px;margin:0 auto;'>
  <p style='font-family:Arial;font-size:0.65rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#F4C21A;margin-bottom:0.5rem;'>Chez Macha</p>
  <h2 style='color:#F6F1E8;font-size:1.3rem;margin:0 0 1.5rem;'>Nouvelle commande — Bon cadeau</h2>
  <div style='background:#0c1d33;border:1px solid rgba(244,194,26,0.3);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;'>
    <table style='width:100%;border-collapse:collapse;'>
      <tr><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.85rem;width:38%;'>ID commande</td><td style='padding:0.5rem 0;font-weight:700;color:#F4C21A;'>{$id}</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.85rem;'>Montant</td><td style='padding:0.5rem 0;font-weight:900;font-size:1.2rem;color:#F4C21A;'>{$montant}€</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.85rem;'>Acheteur</td><td style='padding:0.5rem 0;'>{$prenom} {$nom}</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.85rem;'>Email</td><td style='padding:0.5rem 0;'><a href='mailto:{$email}' style='color:#F4C21A;'>{$email}</a></td></tr>
      {$dest_ligne_admin}
      {$msg_ligne_admin}
      <tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.85rem;'>Date</td><td style='padding:0.5rem 0;'>{$date_now}</td></tr>
    </table>
  </div>
  <div style='background:rgba(244,194,26,0.1);border-left:3px solid #F4C21A;padding:0.75rem 1rem;border-radius:0 8px 8px 0;margin-bottom:1.5rem;'>
    <p style='margin:0;font-size:0.85rem;color:#8a94a8;'>Communication à recevoir :</p>
    <p style='margin:0.25rem 0 0;font-size:1.05rem;font-weight:700;color:#F4C21A;'>{$communication}</p>
  </div>
  <a href='{$admin_url}' style='display:inline-block;background:#F4C21A;color:#060917;font-weight:900;font-size:0.85rem;letter-spacing:0.08em;text-transform:uppercase;text-decoration:none;padding:0.75em 1.5em;border-radius:6px;'>Ouvrir le panneau admin →</a>
  <p style='margin-top:1.5rem;font-size:0.78rem;color:#555e70;'>Dès réception du virement, confirmez le paiement dans le panneau admin pour envoyer automatiquement le bon cadeau PDF.</p>
</div></body></html>";

mail('macha@chezmacha.be', "Nouvelle commande bon cadeau {$montant}€ — {$prenom} {$nom}", $body_admin, $headers_html);

/* ════════════════════════════════════════════
   EMAIL 2 — Confirmation acheteur
   ════════════════════════════════════════════ */
$dest_ligne_buyer = $prenom_dest ? "<tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;width:40%;'>Pour</td><td style='padding:0.6rem 0;'>{$prenom_dest}</td></tr>" : '';
$msg_ligne_buyer  = $message     ? "<tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Votre message</td><td style='padding:0.6rem 0;font-style:italic;'>" . htmlspecialchars($message) . "</td></tr>" : '';

$body_buyer = "<!DOCTYPE html><html lang='fr'><body style='font-family:Arial,sans-serif;background:#071326;color:#F6F1E8;padding:2rem;margin:0;'>
<div style='max-width:560px;margin:0 auto;'>
  <div style='text-align:center;margin-bottom:2rem;'>
    <p style='font-family:Arial;font-size:0.65rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#F4C21A;margin-bottom:0.5rem;'>Chez Macha</p>
    <h1 style='font-size:1.5rem;font-weight:900;margin:0;color:#F6F1E8;'>Commande confirmée !</h1>
    <p style='color:#8a94a8;font-size:0.9rem;margin-top:0.4rem;'>Plus qu'une étape : le virement bancaire.</p>
  </div>
  <div style='background:#0c1d33;border:1px solid rgba(244,194,26,0.3);border-radius:12px;padding:1.5rem;margin-bottom:1.25rem;'>
    <p style='font-size:0.65rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#8a94a8;margin:0 0 1rem;'>Récapitulatif</p>
    <table style='width:100%;border-collapse:collapse;'>
      <tr><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;width:40%;'>Montant</td><td style='padding:0.6rem 0;font-weight:900;font-size:1.2rem;color:#F4C21A;'>{$montant}€</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Acheteur</td><td style='padding:0.6rem 0;'>{$prenom} {$nom}</td></tr>
      {$dest_ligne_buyer}
      {$msg_ligne_buyer}
    </table>
  </div>
  <div style='background:linear-gradient(135deg,#0a1a0a,#0c1d33);border:1.5px solid rgba(244,194,26,0.4);border-radius:12px;padding:1.5rem;margin-bottom:1.25rem;'>
    <p style='font-size:0.65rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#8a94a8;margin:0 0 1rem;'>Instructions de virement</p>
    <table style='width:100%;border-collapse:collapse;'>
      <tr><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;width:40%;'>Bénéficiaire</td><td style='padding:0.6rem 0;'>ASBL Ririkou (Chez Macha)</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>IBAN</td><td style='padding:0.6rem 0;font-weight:700;font-size:1.05rem;'>BE73 0689 5870 2860</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Montant</td><td style='padding:0.6rem 0;font-weight:700;color:#F4C21A;'>{$montant}€</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Communication</td><td style='padding:0.6rem 0;'><strong style='color:#F4C21A;font-size:1.05rem;'>{$communication}</strong></td></tr>
    </table>
    <div style='margin-top:1rem;background:rgba(244,194,26,0.07);border-left:3px solid #F4C21A;padding:0.75rem 1rem;border-radius:0 8px 8px 0;font-size:0.83rem;color:#8a94a8;'>
      <strong style='color:#F6F1E8;'>Important :</strong> utilisez exactement cette communication lors du virement.
    </div>
  </div>
  <div style='background:rgba(244,194,26,0.07);border-radius:12px;padding:1.1rem;text-align:center;margin-bottom:1.5rem;'>
    <p style='color:#F6F1E8;font-size:0.92rem;margin:0;'>Dès réception du virement, vous recevrez votre bon cadeau PDF sous <strong style='color:#F4C21A;'>48h</strong>.</p>
  </div>
  <p style='text-align:center;font-size:0.78rem;color:#555e70;'>Des questions ? <a href='mailto:info@chezmacha.be' style='color:#F4C21A;'>info@chezmacha.be</a> · © 2026 Chez Macha</p>
</div></body></html>";

$headers_buyer = $headers_html . "Reply-To: info@chezmacha.be\r\n";
mail($email, 'Votre bon cadeau Chez Macha — Instructions de virement', $body_buyer, $headers_buyer);

echo json_encode(['ok' => true, 'id' => $id, 'communication' => $communication]);
