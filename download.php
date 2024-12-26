<?php
require_once '../db_config.php';

// 업로드된 파일이 저장된 디렉토리 경로
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';

// 사용자가 요청한 파일 이름 (GET 파라미터로 전달)
$id = $_GET['id'] ?? '';

// 파일 정보 조회
$stmt = $pdo->prepare("SELECT file_name, file_path FROM board_files WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$file = $stmt->fetch(PDO::FETCH_ASSOC);

// 파일이 실제로 존재하는지 확인
if (!$file || !file_exists($file['file_path'])) {
    http_response_code(404);
    die('파일을 찾을 수 없습니다.');
}

// 파일 다운로드 헤더 설정
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file['file_path']));

// 파일 출력
readfile($file['file_path']);
exit;