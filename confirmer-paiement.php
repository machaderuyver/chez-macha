<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

/* ── Auth ── */
if (empty($_SESSION['admin_bons'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

/* ── Validation ── */
$id = isset($_POST['id']) ? trim($_POST['id']) : '';
if (!preg_match('/^BON-\d{4}-[A-Z0-9]{6}$/', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

/* ── Charger la commande ── */
$json_file = __DIR__ . '/commandes-bons.json';
if (!file_exists($json_file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Fichier commandes introuvable']);
    exit;
}

$commandes = json_decode(file_get_contents($json_file), true);
if (!is_array($commandes)) {
    http_response_code(500);
    echo json_encode(['error' => 'Fichier JSON corrompu']);
    exit;
}

$index    = null;
$commande = null;
foreach ($commandes as $i => $c) {
    if ($c['id'] === $id) { $index = $i; $commande = $c; break; }
}

if ($commande === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Commande introuvable']);
    exit;
}
if ($commande['statut'] === 'confirme') {
    echo json_encode(['ok' => true, 'email' => $commande['email'], 'already' => true]);
    exit;
}

/* ════════════════════════════════════════════
   Génération du PDF bon cadeau (FPDF)
   FPDF doit être placé dans lib/fpdf.php
   Téléchargement : http://www.fpdf.org/
   ════════════════════════════════════════════ */
$fpdf_path = __DIR__ . '/lib/fpdf.php';
if (!file_exists($fpdf_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Librairie FPDF manquante (lib/fpdf.php)']);
    exit;
}
require_once $fpdf_path;

/* ── Données du bon ── */
$montant       = (int)$commande['montant'];
$prenom        = $commande['prenom'];
$nom           = $commande['nom'];
$email         = $commande['email'];
$prenom_dest   = $commande['prenom_destinataire'] ?? '';
$message_perso = $commande['message'] ?? '';
$validite      = date('d/m/Y', strtotime('+1 year'));
$date_emission = date('d/m/Y');

/* ── Couleurs ── */
define('C_BG_R',   7);  define('C_BG_G',  19); define('C_BG_B',  38);   // #071326
define('C_CARD_R',12);  define('C_CARD_G',29); define('C_CARD_B',51);   // #0c1d33
define('C_GOLD_R',244); define('C_GOLD_G',194);define('C_GOLD_B',26);   // #F4C21A
define('C_TEXT_R',246); define('C_TEXT_G',241);define('C_TEXT_B',232);  // #F6F1E8
define('C_MUTED_R',138);define('C_MUTED_G',148);define('C_MUTED_B',168); // #8a94a8
define('C_DARK_R', 85); define('C_DARK_G', 94); define('C_DARK_B',112);  // #555e70

/* ── Création PDF (200x120mm, paysage, format carte cadeau) ── */
$pdf = new FPDF('L', 'mm', [200, 120]);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

/* Fond général dark */
$pdf->SetFillColor(C_BG_R, C_BG_G, C_BG_B);
$pdf->Rect(0, 0, 200, 120, 'F');

/* Bande gauche (colonne gauche) */
$pdf->SetFillColor(C_CARD_R, C_CARD_G, C_CARD_B);
$pdf->Rect(0, 0, 85, 120, 'F');

/* Ligne gold verticale séparatrice */
$pdf->SetFillColor(C_GOLD_R, C_GOLD_G, C_GOLD_B);
$pdf->Rect(84, 0, 2, 120, 'F');

/* Bande gold top (5mm) */
$pdf->Rect(0, 0, 200, 4, 'F');

/* Bande gold bottom (2mm) */
$pdf->Rect(0, 118, 200, 2, 'F');

/* ── Colonne gauche — branding + montant ── */

/* Label surtitre */
$pdf->SetFont('Helvetica', 'B', 6.5);
$pdf->SetTextColor(C_GOLD_R, C_GOLD_G, C_GOLD_B);
$pdf->SetXY(0, 10);
$pdf->Cell(85, 5, 'C H E Z   M A C H A', 0, 1, 'C');

/* Ligne under label */
$pdf->SetDrawColor(C_GOLD_R, C_GOLD_G, C_GOLD_B);
$pdf->SetLineWidth(0.3);
$pdf->Line(15, 16, 70, 16);

/* Sous-titre */
$pdf->SetFont('Helvetica', '', 5.5);
$pdf->SetTextColor(C_MUTED_R, C_MUTED_G, C_MUTED_B);
$pdf->SetXY(0, 17);
$pdf->Cell(85, 4, 'COMEDY CLUB BELGE ITINERANT', 0, 1, 'C');

/* Montant — gros */
$pdf->SetFont('Helvetica', 'B', 48);
$pdf->SetTextColor(C_GOLD_R, C_GOLD_G, C_GOLD_B);
$pdf->SetXY(0, 28);
$pdf->Cell(85, 28, $montant . ' EUR', 0, 1, 'C');

/* Label bon cadeau */
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetTextColor(C_TEXT_R, C_TEXT_G, C_TEXT_B);
$pdf->SetXY(0, 58);
$pdf->Cell(85, 6, 'BON CADEAU', 0, 1, 'C');

/* Séparateur */
$pdf->SetDrawColor(C_GOLD_R, C_GOLD_G, C_GOLD_B);
$pdf->SetLineWidth(0.25);
$pdf->Line(15, 66, 70, 66);

/* Date émission */
$pdf->SetFont('Helvetica', '', 6);
$pdf->SetTextColor(C_DARK_R, C_DARK_G, C_DARK_B);
$pdf->SetXY(0, 68);
$pdf->Cell(85, 4, 'Emis le ' . $date_emission, 0, 1, 'C');

/* Site */
$pdf->SetFont('Helvetica', '', 6);
$pdf->SetTextColor(C_MUTED_R, C_MUTED_G, C_MUTED_B);
$pdf->SetXY(0, 110);
$pdf->Cell(84, 4, 'www.chezmacha.be', 0, 0, 'C');

/* ── Colonne droite — détails ── */
$x = 92; // Départ X colonne droite

/* Code unique */
$pdf->SetFont('Helvetica', 'B', 7);
$pdf->SetTextColor(C_MUTED_R, C_MUTED_G, C_MUTED_B);
$pdf->SetXY($x, 10);
$pdf->Cell(30, 4, 'CODE :', 0);

$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(C_GOLD_R, C_GOLD_G, C_GOLD_B);
$pdf->SetXY($x + 22, 9);
$pdf->Cell(80, 6, $id, 0);

/* Ligne séparatrice */
$pdf->SetDrawColor(C_CARD_R, C_CARD_G, C_CARD_B);
$pdf->SetLineWidth(0.3);
$pdf->Line($x, 18, 196, 18);

/* Validité */
$pdf->SetFont('Helvetica', '', 6.5);
$pdf->SetTextColor(C_MUTED_R, C_MUTED_G, C_MUTED_B);
$pdf->SetXY($x, 21);
$pdf->Cell(35, 4, 'VALABLE JUSQU\'AU :');

$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetTextColor(C_TEXT_R, C_TEXT_G, C_TEXT_B);
$pdf->SetXY($x + 38, 20);
$pdf->Cell(60, 6, $validite);

/* Ligne */
$pdf->SetDrawColor(C_CARD_R, C_CARD_G, C_CARD_B);
$pdf->SetLineWidth(0.3);
$pdf->Line($x, 29, 196, 29);

/* Destinataire */
if ($prenom_dest) {
    $pdf->SetFont('Helvetica', '', 6.5);
    $pdf->SetTextColor(C_MUTED_R, C_MUTED_G, C_MUTED_B);
    $pdf->SetXY($x, 32);
    $pdf->Cell(20, 4, 'POUR :');

    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(C_GOLD_R, C_GOLD_G, C_GOLD_B);
    $pdf->SetXY($x + 20, 31);
    $pdf->Cell(80, 6, $prenom_dest);

    $pdf->SetDrawColor(C_CARD_R, C_CARD_G, C_CARD_B);
    $pdf->Line($x, 40, 196, 40);
    $msg_y = 44;
} else {
    $msg_y = 33;
}

/* Message personnalisé */
if ($message_perso) {
    $pdf->SetFont('Helvetica', '', 6.5);
    $pdf->SetTextColor(C_MUTED_R, C_MUTED_G, C_MUTED_B);
    $pdf->SetXY($x, $msg_y);
    $pdf->Cell(30, 4, 'MESSAGE :');

    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetTextColor(C_TEXT_R, C_TEXT_G, C_TEXT_B);
    $pdf->SetXY($x, $msg_y + 5);
    $pdf->MultiCell(104, 5, '"' . $message_perso . '"');
}

/* Bloc conditions */
$pdf->SetFont('Helvetica', '', 5.5);
$pdf->SetTextColor(C_DARK_R, C_DARK_G, C_DARK_B);
$pdf->SetXY($x, 90);
$pdf->MultiCell(104, 3.5, 'Ce bon cadeau est valable 1 an a compter de la date d\'emission. Non remboursable, non echangeable contre de l\'argent. Presentez ce bon lors de votre reservation.');

/* Infos contact */
$pdf->SetFont('Helvetica', '', 5.5);
$pdf->SetTextColor(C_MUTED_R, C_MUTED_G, C_MUTED_B);
$pdf->SetXY($x, 110);
$pdf->Cell(104, 4, 'info@chezmacha.be   |   ASBL Ririkou  BE 1033.391.775', 0, 0, 'R');

/* ── Récupération du PDF en string ── */
$pdf_string = $pdf->Output('S');

/* ════════════════════════════════════════════
   Envoi email avec PDF en pièce jointe
   ════════════════════════════════════════════ */
$boundary   = 'BonCadeau_' . md5(uniqid('', true));
$from       = 'noreply@chezmacha.be';
$acheteur   = $prenom . ' ' . $nom;
$dest_pour  = $prenom_dest ? " pour {$prenom_dest}" : '';

$email_html = "<!DOCTYPE html><html lang='fr'><body style='font-family:Arial,sans-serif;background:#071326;color:#F6F1E8;padding:2rem;margin:0;'>
<div style='max-width:560px;margin:0 auto;'>
  <p style='font-size:0.65rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#F4C21A;margin-bottom:0.5rem;'>Chez Macha</p>
  <h1 style='font-size:1.4rem;font-weight:900;margin:0 0 0.3rem;color:#F6F1E8;'>Votre bon cadeau est arrivé&nbsp;! 🎭</h1>
  <p style='color:#8a94a8;margin-bottom:1.5rem;font-size:0.9rem;'>Bonjour {$prenom}, votre bon cadeau de <strong style='color:#F4C21A;'>{$montant}€</strong>{$dest_pour} est en pièce jointe.</p>
  <div style='background:#0c1d33;border:1px solid rgba(244,194,26,0.3);border-radius:12px;padding:1.25rem;margin-bottom:1.25rem;'>
    <table style='width:100%;border-collapse:collapse;'>
      <tr><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.83rem;width:35%;'>Code</td><td style='padding:0.5rem 0;font-weight:700;color:#F4C21A;'>{$id}</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.83rem;'>Montant</td><td style='padding:0.5rem 0;font-weight:900;font-size:1.1rem;color:#F4C21A;'>{$montant}€</td></tr>
      <tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.83rem;'>Valable jusqu'au</td><td style='padding:0.5rem 0;'>{$validite}</td></tr>" .
      ($prenom_dest ? "<tr style='border-top:1px solid rgba(255,255,255,0.06);'><td style='padding:0.5rem 0;color:#8a94a8;font-size:0.83rem;'>Pour</td><td style='padding:0.5rem 0;font-weight:700;color:#F4C21A;'>{$prenom_dest}</td></tr>" : '') .
      "</table>
  </div>
  <p style='font-size:0.9rem;color:#8a94a8;line-height:1.65;'>Pour réserver, rendez-vous sur <a href='https://www.chezmacha.be/tickets.html' style='color:#F4C21A;'>www.chezmacha.be/tickets.html</a> et mentionnez votre code lors de la réservation.</p>
  <p style='margin-top:1.5rem;font-size:0.78rem;color:#555e70;'>Des questions ? <a href='mailto:info@chezmacha.be' style='color:#F4C21A;'>info@chezmacha.be</a> · © 2026 Chez Macha · ASBL Ririkou</p>
</div></body></html>";

/* En-têtes multipart */
$headers  = "From: Chez Macha <{$from}>\r\n";
$headers .= "Reply-To: info@chezmacha.be\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

/* Corps */
$body  = "--{$boundary}\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$body .= $email_html . "\r\n";

/* Pièce jointe PDF */
$body .= "--{$boundary}\r\n";
$body .= "Content-Type: application/pdf\r\n";
$body .= "Content-Transfer-Encoding: base64\r\n";
$body .= "Content-Disposition: attachment; filename=\"bon-cadeau-chez-macha-{$id}.pdf\"\r\n\r\n";
$body .= chunk_split(base64_encode($pdf_string)) . "\r\n";
$body .= "--{$boundary}--";

$subject = "Votre bon cadeau Chez Macha {$montant}€ — {$id}";
$sent    = mail($email, $subject, $body, $headers);

if (!$sent) {
    http_response_code(500);
    echo json_encode(['error' => 'Échec envoi email']);
    exit;
}

/* ── Mise à jour du statut dans le JSON ── */
$commandes[$index]['statut']      = 'confirme';
$commandes[$index]['date_confirm'] = date('Y-m-d H:i:s');
file_put_contents($json_file, json_encode($commandes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

echo json_encode(['ok' => true, 'email' => $email, 'id' => $id]);
