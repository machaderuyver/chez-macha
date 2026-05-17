<?php
header('Content-Type: application/json; charset=utf-8');

/* ── Validation ── */
$montants_autorises = [30, 50, 100];

$montant = isset($_POST['montant']) ? (int)$_POST['montant'] : 0;
$prenom  = isset($_POST['prenom'])  ? trim(strip_tags($_POST['prenom']))  : '';
$nom     = isset($_POST['nom'])     ? trim(strip_tags($_POST['nom']))     : '';
$email   = isset($_POST['email'])   ? trim($_POST['email'])               : '';
$prenom_dest = isset($_POST['prenom_destinataire']) ? trim(strip_tags($_POST['prenom_destinataire'])) : '';
$message_perso = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

if (!in_array($montant, $montants_autorises)) {
    http_response_code(400);
    echo json_encode(['error' => 'Montant invalide']);
    exit;
}
if (empty($prenom) || empty($nom)) {
    http_response_code(400);
    echo json_encode(['error' => 'Prénom et nom requis']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email invalide']);
    exit;
}

$communication = "Bon cadeau {$montant}€ {$prenom}";
$acheteur = "{$prenom} {$nom}";
$date_commande = date('d/m/Y à H:i');

/* ────────────────────────────────────────────
   EMAIL 1 — Notification interne (macha@chezmacha.be)
   ──────────────────────────────────────────── */
$to_macha   = 'macha@chezmacha.be';
$from       = 'noreply@chezmacha.be';
$headers_base = "From: Chez Macha <{$from}>\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";

$sujet_macha = "Nouvelle commande bon cadeau {$montant}€ — {$acheteur}";

$body_macha = "<!DOCTYPE html><html lang='fr'><body style='font-family:Arial,sans-serif;background:#071326;color:#F6F1E8;padding:2rem;'>
<div style='max-width:560px;margin:0 auto;'>
  <h2 style='color:#F4C21A;font-size:1.3rem;margin-bottom:1.5rem;'>Nouvelle commande — Bon cadeau</h2>
  <table style='width:100%;border-collapse:collapse;'>
    <tr><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;width:40%;'>Montant</td><td style='padding:0.6rem 0;font-weight:bold;color:#F4C21A;font-size:1.1rem;'>{$montant}€</td></tr>
    <tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Acheteur</td><td style='padding:0.6rem 0;'>{$acheteur}</td></tr>
    <tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Email</td><td style='padding:0.6rem 0;'><a href='mailto:{$email}' style='color:#F4C21A;'>{$email}</a></td></tr>
    <tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Destinataire</td><td style='padding:0.6rem 0;'>" . ($prenom_dest ?: '<em style=\"color:#555e70;\">Non renseigné</em>') . "</td></tr>
    <tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Message perso</td><td style='padding:0.6rem 0;font-style:italic;'>" . ($message_perso ?: '<em style=\"color:#555e70;\">Aucun</em>') . "</td></tr>
    <tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Date</td><td style='padding:0.6rem 0;'>{$date_commande}</td></tr>
  </table>
  <div style='margin-top:1.5rem;background:rgba(244,194,26,0.1);border-left:3px solid #F4C21A;padding:0.75rem 1rem;border-radius:0 8px 8px 0;'>
    <strong>Communication à vérifier :</strong><br>
    <span style='font-size:1.1rem;color:#F4C21A;letter-spacing:0.03em;'>{$communication}</span>
  </div>
  <p style='margin-top:1.5rem;font-size:0.8rem;color:#555e70;'>Dès réception du virement avec cette communication, envoyer le bon cadeau par email à <a href='mailto:{$email}' style='color:#F4C21A;'>{$email}</a>.</p>
</div>
</body></html>";

mail($to_macha, $sujet_macha, $body_macha, $headers_base);

/* ────────────────────────────────────────────
   EMAIL 2 — Confirmation à l'acheteur
   ──────────────────────────────────────────── */
$sujet_acheteur = "Votre bon cadeau Chez Macha — Instructions de virement";

$dest_ligne = $prenom_dest ? "<tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Pour</td><td style='padding:0.6rem 0;'>{$prenom_dest}</td></tr>" : '';
$msg_ligne  = $message_perso ? "<tr style='border-top:1px solid rgba(255,255,255,0.08);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Votre message</td><td style='padding:0.6rem 0;font-style:italic;'>{$message_perso}</td></tr>" : '';

$body_acheteur = "<!DOCTYPE html><html lang='fr'><body style='font-family:Arial,sans-serif;background:#071326;color:#F6F1E8;padding:2rem;margin:0;'>
<div style='max-width:560px;margin:0 auto;'>

  <div style='text-align:center;margin-bottom:2rem;'>
    <p style='font-family:Arial,sans-serif;font-size:0.7rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#F4C21A;margin-bottom:0.5rem;'>Chez Macha</p>
    <h1 style='font-size:1.6rem;font-weight:900;margin:0;color:#F6F1E8;'>Votre commande est confirmée&nbsp;!</h1>
    <p style='color:#8a94a8;font-size:0.9rem;margin-top:0.5rem;'>Plus qu'une étape : le virement bancaire.</p>
  </div>

  <div style='background:#0c1d33;border:1px solid rgba(244,194,26,0.3);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;'>
    <p style='font-size:0.7rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#8a94a8;margin-bottom:1rem;'>Récapitulatif</p>
    <table style='width:100%;border-collapse:collapse;'>
      <tr><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;width:40%;'>Montant</td><td style='padding:0.6rem 0;font-weight:bold;color:#F4C21A;font-size:1.1rem;'>{$montant}€</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Acheteur</td><td style='padding:0.6rem 0;'>{$acheteur}</td></tr>
      {$dest_ligne}
      {$msg_ligne}
    </table>
  </div>

  <div style='background:linear-gradient(135deg,#0a1a0a,#0c1d33);border:1.5px solid rgba(244,194,26,0.4);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;'>
    <p style='font-size:0.7rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#8a94a8;margin-bottom:1rem;'>Instructions de virement</p>
    <table style='width:100%;border-collapse:collapse;'>
      <tr><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;width:40%;'>Bénéficiaire</td><td style='padding:0.6rem 0;'>ASBL Ririkou (Chez Macha)</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>IBAN</td><td style='padding:0.6rem 0;font-weight:bold;font-size:1.05rem;color:#F6F1E8;letter-spacing:0.03em;'>BE73 0689 5870 2860</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Montant</td><td style='padding:0.6rem 0;font-weight:bold;color:#F4C21A;'>{$montant}€</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.6rem 0;color:#8a94a8;font-size:0.85rem;'>Communication</td><td style='padding:0.6rem 0;'><strong style='color:#F4C21A;font-size:1.05rem;'>{$communication}</strong></td></tr>
    </table>
    <div style='margin-top:1.25rem;background:rgba(244,194,26,0.07);border-left:3px solid #F4C21A;padding:0.75rem 1rem;border-radius:0 8px 8px 0;font-size:0.85rem;color:#8a94a8;line-height:1.6;'>
      <strong style='color:#F6F1E8;'>Important :</strong> utilisez exactement cette communication lors de votre virement afin que nous puissions identifier votre commande.
    </div>
  </div>

  <div style='background:rgba(244,194,26,0.07);border-radius:12px;padding:1.25rem;text-align:center;margin-bottom:1.5rem;'>
    <p style='color:#F6F1E8;font-size:0.95rem;margin:0;'>
      Dès réception de votre virement, vous recevrez votre bon cadeau personnalisé par email sous <strong style='color:#F4C21A;'>48h</strong>.
    </p>
  </div>

  <p style='text-align:center;font-size:0.8rem;color:#555e70;line-height:1.6;'>
    Des questions ? Écrivez-nous à <a href='mailto:info@chezmacha.be' style='color:#F4C21A;'>info@chezmacha.be</a><br>
    © 2026 Chez Macha · ASBL Ririkou · BE 1033.391.775
  </p>
</div>
</body></html>";

$headers_acheteur = $headers_base . "Reply-To: info@chezmacha.be\r\n";
mail($email, $sujet_acheteur, $body_acheteur, $headers_acheteur);

/* ── Réponse JSON (consommée silencieusement par le JS front) ── */
echo json_encode(['ok' => true, 'communication' => $communication]);
