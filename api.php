<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

if (!isset($data['category']) || !isset($data['content'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing category or content']);
    exit;
}

$category = $data['category'];
$content = $data['content'];
$dataDir = __DIR__ . '/data/';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$filename = $dataDir . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $category)) . '.json';

if (file_put_contents($filename, json_encode($content, JSON_PRETTY_PRINT))) {
    echo json_encode(['status' => 'success', 'message' => 'Data saved successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save data']);
}
?>
