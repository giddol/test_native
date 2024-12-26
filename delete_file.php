<?php
require_once '../db_config.php';

session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // 관리자가 아니면 인덱스 페이지로 리디렉션
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileId = $_POST['id'];

    // 파일 정보 가져오기
    $stmt = $pdo->prepare("SELECT file_path FROM board_files WHERE id = :id");
    $stmt->bindValue(':id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        // 서버 파일 삭제
        if (unlink($file['file_path'])) {
            // DB에서 파일 기록 삭제
            $stmt = $pdo->prepare("DELETE FROM board_files WHERE id = :id");
            $stmt->bindValue(':id', $fileId, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => '파일 삭제 실패']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => '파일을 찾을 수 없습니다.']);
    }
}
?>