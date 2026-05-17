<?php
header('Content-Type: application/json; charset=utf-8');

/* ── Labels lisibles pour l'export CSV / admin ── */
$labels_q1 = ['fan' => 'Oui, je suis fan', 'connais' => 'Oui, je le connais', 'non' => 'Non, pas encore'];
$labels_q2 = ['oui-direct' => 'Oui, je réserve direct !', 'oui-prix' => 'Oui, si le prix est correct', 'peut-etre' => 'Peut-être', 'non' => 'Non'];
$labels_q3 = ['plusieurs' => 'Oui, plusieurs fois', 'une-fois' => 'Oui, une fois', 'pas-encore' => 'Pas encore'];
$labels_q4 = ['semaine' => 'En semaine (lundi–jeudi)', 'weekend' => 'Le week-end (vendredi–dimanche)', 'les-deux' => 'Les deux me vont'];

/* ── Lecture & validation ── */
$q1    = isset($_POST['q1']) ? trim($_POST['q1']) : '';
$q2    = isset($_POST['q2']) ? trim($_POST['q2']) : '';
$q3    = isset($_POST['q3']) ? trim($_POST['q3']) : '';
$q4    = isset($_POST['q4']) ? trim($_POST['q4']) : '';
$email = isset($_POST['q5']) ? trim($_POST['q5']) : '';

if (!array_key_exists($q1, $labels_q1)) { http_response_code(400); echo json_encode(['error' => 'q1 invalide']); exit; }
if (!array_key_exists($q2, $labels_q2)) { http_response_code(400); echo json_encode(['error' => 'q2 invalide']); exit; }
if ($q3 && !array_key_exists($q3, $labels_q3)) $q3 = '';
if ($q4 && !array_key_exists($q4, $labels_q4)) $q4 = '';
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';

/* ── Sauvegarde JSON ── */
$json_file = __DIR__ . '/reponses-sondage-dan-gagnon.json';
$reponses  = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
if (!is_array($reponses)) $reponses = [];

$reponses[] = [
    'date'  => date('Y-m-d H:i:s'),
    'q1'    => $q1,
    'q2'    => $q2,
    'q3'    => $q3,
    'q4'    => $q4,
    'email' => $email,
    /* Valeurs lisibles pour l'admin */
    'q1_label' => $labels_q1[$q1],
    'q2_label' => $labels_q2[$q2],
    'q3_label' => $q3 ? $labels_q3[$q3] : '',
    'q4_label' => $q4 ? $labels_q4[$q4] : '',
];

file_put_contents($json_file, json_encode($reponses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

echo json_encode(['ok' => true, 'total' => count($reponses)]);
