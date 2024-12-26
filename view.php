<?php
require_once '../db_config.php';
session_start();
$isAdmin = $_SESSION['is_admin'];
$visibleQry = $isAdmin ? "" : "AND visible_yn = 'Y'";  //관리자는 visible_yn = 'N' 도 보이게

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM board_notice WHERE id = :id $visibleQry");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    echo "<script>alert('게시글을 찾을 수 없습니다.');location.href='index.php';</script>";
    exit;
}

if($id) {
    $updateHitQuery = "UPDATE board_notice SET hit = hit + 1, updated_at = updated_at WHERE id = :id";
    $stmt = $pdo->prepare($updateHitQuery);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $stmt_files = $pdo->prepare("SELECT id, file_name, file_size FROM board_files WHERE notice_id = :id");
    $stmt_files->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt_files->execute();
    $files = $stmt_files->fetchAll(PDO::FETCH_ASSOC);
    // if(!$files) $files = [];
    // print_r($files);
}

function formatFileSize($bytes) {
    if ($bytes < 1024) {
        return $bytes . ' byte';
    } elseif ($bytes < 1048576) { // 1024 * 1024 = 1048576
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes < 1073741824) { // 1024 * 1024 * 1024 = 1073741824
        return number_format($bytes / 1048576, 2) . ' MB';
    } else {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/common.css" />
    <title><?= htmlspecialchars($post['title']) ?></title>
</head>
<body>
    <div id="wrap">
        <div class="view-container">
            <div class="view-info">
                <h1><?= htmlspecialchars($post['title']) ?></h1>
                <p>작성일: <?= htmlspecialchars($post['created_at']) ?>
                <?= ($post['updated_at'] > $post['created_at']) ? '<br>수정일: ' . htmlspecialchars($post['updated_at']) : ""; ?>
                <?php foreach($files as $file): ?>
                    <br>첨부파일: <a class="add-file" href="download.php?id=<?=$file['id']?>"><?=$file['file_name']?> </a>(<?=formatFileSize($file['file_size'])?>)
                <?php endforeach; ?>
                </p>
            </div>
            <div class="view-content"><?= $post['content'] ?></div>
        </div>
        <div class="btns-container pb30">
            <div class="btns-container--left">
                <input type="button" class="btn-normal" value="목록" onclick="location.href='index.php';">
                <?= $isAdmin ? '<input type="button" class="btn-normal" value="수정" onclick="location.href=\'write.php?id='.$id.'\';">' : "" ?>
                <?= $isAdmin ? '<input type="button" class="btn-normal" value="삭제" onclick="board_delete(\''.$id.'\');">' : "" ?>
            </div>
            <div class="btns-container--right">
                <?= $isAdmin ? '<input type="button" class="btn-normal" value="글쓰기" onclick="location.href=\'write.php\';">' : "" ?>
            </div>
        </div>
        

        <?php include 'board_list.php'; ?>
    </div> 

    <script>
        function board_delete(id) {
            if (confirm('삭제하시겠습니까?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete.php';

                // 숨겨진 필드로 _method 지정 (DELETE 시뮬레이션)
                var methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';

                var methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = 'id';
                methodField.value = id;
                form.appendChild(methodField);

                // 폼 제출
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>