<?php
require_once '../db_config.php';

session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // 관리자가 아니면 인덱스 페이지로 리디렉션
    header('Location: index.php');
    exit;
}

// 게시글 ID 받아오기
$postId = $_POST['id'] ?? 0;

// 게시글 ID가 없거나 숫자가 아닐 경우 처리
if (!is_numeric($postId) || $postId <= 0) {
    echo "잘못된 접근입니다.";
    exit;
}

try{
    // 게시글의 파일 정보를 먼저 삭제 (파일 테이블)
    $stmt = $pdo->prepare("SELECT * FROM board_files WHERE notice_id = :post_id");
    $stmt->bindValue(':post_id', $postId);
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($files as $file) {
        $filePath = $file['file_path']; // 파일 경로 가져오기
        if (file_exists($filePath)) {
            unlink($filePath); // 서버에서 파일 삭제
        }
    }

    $stmt = $pdo->prepare("DELETE FROM board_files WHERE notice_id = :post_id");
    $stmt->bindValue(':post_id', $postId);
    $stmt->execute();


    // 게시글 삭제 쿼리
    $query = "DELETE FROM board_notice WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':id', $postId, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('게시글 삭제에 성공했습니다.');location.href='index.php';</script>";
        exit;
    } else {
        echo '<script>
            alert("게시글 삭제에 실패했습니다.");
            history.back();
        </script>';
    }
} catch (PDOException $e) {
    echo "<script>alert('게시글 삭제에 실패했습니다:'); history.back();</script>";
    exit;
}
?>