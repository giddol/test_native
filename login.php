<?php
session_start();

// 임시비밀번호
$adminPassword = 'admin123';

// 로그인 폼 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    // 비밀번호 확인
    if ($password === $adminPassword) {
        // 비밀번호가 맞으면 세션에 관리자 권한 부여
        $_SESSION['is_admin'] = true;
        header('Location: index.php'); 
        exit;
    } else {
        $errorMessage = '비밀번호가 잘못되었습니다.';
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인</title>
</head>
<body>
    <h1>관리자 로그인</h1>
    <form method="POST">
        <label for="password">비밀번호:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">로그인</button>
    </form>
    <?php if (isset($errorMessage)): ?>
        <p style="color:red;"><?= $errorMessage ?></p>
    <?php endif; ?>
</body>
</html>