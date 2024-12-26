<?php
// ini_set('display_errors', '0');
require_once '../db_config.php';

session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // 관리자가 아니면 인덱스 페이지로 리디렉션
    header('Location: index.php');
    exit;
}

$notice_yn = 'N';
$visible_yn = "Y";

$fileList = [];
if ($_REQUEST['id']) {
    $files = $pdo->prepare("SELECT id, file_name, file_size FROM board_files WHERE notice_id = :notice_id");
    $files->bindValue(':notice_id', $_GET['id'], PDO::PARAM_INT);
    $files->execute();
    $fileList = $files->fetchAll(PDO::FETCH_ASSOC);
}

if ($_POST['type'] === 'act') {
    // 파일 업로드 처리
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/'; // 파일을 저장할 디렉토리
    $maxFiles = 3;  // 최대 파일 개수
    $maxFileSize = 5 * 1024 * 1024; // 파일 크기 제한 (5MB)

    // 디렉터리가 없으면 생성
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }


    $id = $_POST['id'];
    $tp = $id ? "수정" : "등록";
    $title = $_POST['title'];
    $content = $_POST['content'];
    $notice_yn = isset($_POST['notice_yn']) ? 'Y' : 'N';
    $visible_yn = isset($_POST['visible_yn']) ? 'Y' : 'N';

    if (!$title) {
        echo "<script>alert('제목을 입력해주세요.');history.back();</script>";
        exit;
    } else if (!$content) {
        echo "<script>alert('내용을 입력해주세요.');history.back();</script>";
        exit;
    }

    // 파일 처리 시작
    $uploadedFiles = [];
    $fileError = false;

    // $_FILES['file_upload'] 배열에서 파일 처리
    if (isset($_FILES['file_upload']) && count($_FILES['file_upload']['name']) > 0 && $_FILES['file_upload']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $fileCount = count($_FILES['file_upload']['name']);

        $sumFileCount = $fileCount+count($fileList); //기존에 등록된 파일 개수 더함
        // 파일 개수 제한
        if ($sumFileCount > $maxFiles) {
            echo "<script>alert('최대 {$maxFiles}개 파일만 첨부 가능합니다.'); history.back();</script>";
            exit;
        }

        // 파일 처리 반복문
        for ($i = 0; $i < $fileCount; $i++) {
            $fileTmpPath = $_FILES['file_upload']['tmp_name'][$i];
            $fileName = $_FILES['file_upload']['name'][$i];
            $fileSize = $_FILES['file_upload']['size'][$i];
            $fileType = $_FILES['file_upload']['type'][$i];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $newFileName = uniqid('file_', true) . '.' . $fileExtension;

            // 파일 크기 제한
            if ($fileSize > $maxFileSize) {
                $fileError = true;
                echo "<script>alert('파일 {$fileName}의 크기가 5MB를 초과합니다.'); history.back();</script>";
                exit;
            }

            // 파일을 저장할 경로
            $destPath = $uploadDir . $newFileName;

            // 파일 이동
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // 업로드된 파일 정보 저장
                $uploadedFiles[] = [
                    'file_name' => $fileName,
                    'file_path' => $destPath,
                    'file_size' => $fileSize
                ];
            } else {
                $fileError = true;
                echo "<script>alert('파일 업로드에 실패했습니다: {$fileName}'); history.back();</script>";
                exit;
            }
        }

    }

    try {
        if (!empty($title) && !empty($content)) {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE board_notice SET
                    title = :title,
                    content = :content,
                    notice_yn = :notice_yn,
                    visible_yn = :visible_yn
                    WHERE id = :id
                ");
                $stmt->bindValue(':id', $id);
            } else {
                $stmt = $pdo->prepare("INSERT INTO board_notice (title, content, notice_yn, visible_yn)
                    VALUES(:title, :content, :notice_yn, :visible_yn)
                ");
            }

            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':content', $content);
            $stmt->bindValue(':notice_yn', $notice_yn);
            $stmt->bindValue(':visible_yn', $visible_yn);

            $stmt->execute();

            if (!$id) $id = $pdo->lastInsertId();

            // 파일 정보를 board_files 테이블에 저장
            foreach ($uploadedFiles as $file) {
                $stmt = $pdo->prepare("INSERT INTO board_files (notice_id, file_name, file_path, file_size)
                    VALUES(:notice_id, :file_name, :file_path, :file_size)
                ");
                $stmt->bindValue(':notice_id', $id);
                $stmt->bindValue(':file_name', $file['file_name']);
                $stmt->bindValue(':file_path', $file['file_path']);
                $stmt->bindValue(':file_size', $file['file_size']);
                $stmt->execute();
            }
            
            echo "<script>alert('{$tp}에 성공했습니다.');window.location.href = 'view.php?id=$id';</script>";
            exit;
        }
    } catch (PDOException $e) {
        echo "<script>alert('{$tp}에 실패했습니다.'); history.back();</script>";
        exit;
    }
}

if ($_REQUEST['id']) {
    $stmt = $pdo->prepare("SELECT * FROM board_notice WHERE id = :id");
    $stmt->bindValue(':id', $_REQUEST['id']);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
}
$tp = $_REQUEST['id'] ? "수정" : "등록";
if ($tp === "등록")    $row['visible_yn'] = 'Y';

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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js" type="text/javascript" language="javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-multifile@2.2.2/jquery.MultiFile.min.js" integrity="sha256-TiSXq9ubGgxFwCUu3belTfML3FOjrdlF0VtPjFLpksk=" crossorigin="anonymous"></script>
    <script type="text/javascript" src="../se2/js/service/HuskyEZCreator.js" charset="utf-8"></script>
    <title><?= $tp ?></title>
</head>

<body>
    <div id="write-wrap" style="text-align: center;">
        <h1><?= $tp ?></h1>
        <form method="post" enctype="multipart/form-data" name="frm">
            <input type="hidden" name="type" value="act">
            <input type="hidden" name="id" value="<?= $_REQUEST['id'] ?>">

            <div class="form-group">
                <label for="title" class="write-label">제목</label>
                <input type="text" id="title" style="width:650px;" name="title" value="<?= htmlspecialchars($row['title']) ?>" required>
            </div>

            <div class="form-group mt10">
                <div class="write-label">설정</div>
                <label for="notice_yn" class="write-config-label">공지 여부</label>
                <input type="checkbox" id="notice_yn" name="notice_yn" value="Y" <?= $row['notice_yn'] === 'Y' ? 'checked' : '' ?>>
                <label for="visible_yn" class="write-config-label">공개 여부</label>
                <input type="checkbox" id="visible_yn" name="visible_yn" value="Y" <?= $row['visible_yn'] === 'Y' ? 'checked' : '' ?>>
            </div>
            <?php if (count($fileList) > 0): ?>
            <div class="form-group mt10">
                <p class="write-label">기존 첨부파일</p>
                <ul class="prevFile">
                    <?php foreach ($fileList as $file): ?>
                    <li>
                        <span class="file-name"><?= htmlspecialchars($file['file_name']) ?></span>
                        <span class="file-size">(<?=formatFileSize($file['file_size'])?>)</span>
                        <button type="button" class="delete-file" data-file-id="<?= $file['id'] ?>">삭제</button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="form-group mt10">
                <label for="addfile" class="write-label">파일첨부</label>
                <div class="file-input">
                    <button type="button">파일 선택</button>
                    <input type="file" style="width:85px;" multiple="multiple" id="file_upload" name="file_upload[]" class="maxsize-5120" />
                </div>
                <div id="file_upload_list"></div>
            </div>

            <div class="form-group mt10">
                <label for="ir1" class="write-label">내용</label>
                <textarea id="ir1" name="content" style="width:650px;height: 300px;"><?= htmlspecialchars($row['content']) ?></textarea>
            </div>

            <button type="submit" class="btn-normal mt10" onclick="submitContents();"><?= $tp ?></button>
            <button type="button" class="btn-normal mt10" onclick="history.back();">취소</button>
        </form>
    </div>
    <script>
    var oEditors = [];
    nhn.husky.EZCreator.createInIFrame({
        oAppRef: oEditors,
        elPlaceHolder: "ir1",
        sSkinURI: "../se2/SmartEditor2Skin.html",
        fCreator: "createSEditor2"
    });
    $(function() { // wait for document to load
        $('#file_upload').MultiFile({
            list: '#file_upload_list',
            max:3,
            STRING: {
                toomany:'파일은 최대 3개까지만 업로드할 수 있습니다.'
            }
        });

        $(document).on('click', '.delete-file', function () {
            const fileId = $(this).data('file-id');
            if (confirm('이 파일을 삭제하시겠습니까?')) {
                $.ajax({
                    url: 'delete_file.php', // 파일 삭제를 처리하는 PHP 파일
                    method: 'POST',
                    data: { id: fileId },
                    success: function (response) {
                        const _response = JSON.parse(response);
                        if (_response.success) {
                            alert('파일이 삭제되었습니다.');
                            $(`button[data-file-id="${fileId}"]`).closest('li').remove();
                        } else {
                            alert('파일 삭제에 실패했습니다.');
                        }
                    }
                });
            }
        });
    });
    function submitContents() {
        if (!document.frm.checkValidity()) {
            return false;
        }
        event.preventDefault();
        oEditors.getById["ir1"].exec("UPDATE_CONTENTS_FIELD", []);
        let content = document.getElementById("ir1").value;
        content = stripHTMLTags(content);
        if(content.length < 1) {
            alert('내용을 입력해주세요');
            return false;
        }
        document.frm.submit();
    }

    function stripHTMLTags(html) {
        const div = document.createElement("div");
        div.innerHTML = html; // HTML 내용을 DOM에 삽입
        div.remove();
        return div.textContent || div.innerText || ""; // 텍스트만 반환
    }
    </script>

</body>

</html>