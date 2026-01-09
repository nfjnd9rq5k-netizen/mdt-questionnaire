<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo 'Accès refusé - Vous devez être connecté en tant qu\'administrateur';
    exit;
}

$filename = $_GET['file'] ?? '';
$studyId = $_GET['study'] ?? '';

$filename = basename($filename);
$studyId = preg_replace('/[^a-zA-Z0-9_-]/', '', $studyId);

if (!empty($studyId)) {
    $filepath = __DIR__ . '/../studies/' . $studyId . '/data/photos/' . $filename;
} else {
    $filepath = __DIR__ . '/secure_data/photos/' . $filename;
}

if (empty($filename) || !file_exists($filepath)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Photo non trouvée';
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filepath);
finfo_close($finfo);

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo 'Type de fichier non autorisé';
    exit;
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=3600');

readfile($filepath);
