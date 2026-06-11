<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../auth/helper.php';
require_once '../../koneksi.php';

require_auth('administrator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = trim($_POST['action'] ?? 'mark_all');
    $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($action === 'hapus') {
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
            $conn->close(); exit;
        }
        $stmt = $conn->prepare("DELETE FROM kontak_donatur WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status' => 'success']);
        $conn->close(); exit;
    }

    if ($action === 'mark_one') {
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
            $conn->close(); exit;
        }
        $stmt = $conn->prepare("UPDATE kontak_donatur SET is_read=1 WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status' => 'success']);
        $conn->close(); exit;
    }

    // mark_all — default, also handles legacy calls without action param
    $conn->query("UPDATE kontak_donatur SET is_read=1 WHERE is_read=0");
    echo json_encode(['status' => 'success', 'marked_read' => $conn->affected_rows]);
    $conn->close();
    exit;
}

$rows = $conn->query("
    SELECT id, nama, kontak, kategori_minat, pesan, is_read, created_at
    FROM kontak_donatur
    ORDER BY is_read ASC, created_at DESC
    LIMIT 100
");

$data = [];
while ($r = $rows->fetch_assoc()) {
    $data[] = $r;
}

echo json_encode(['status' => 'success', 'data' => $data]);
$conn->close();
