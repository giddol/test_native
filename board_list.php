<?php
require_once  '../db_config.php';
session_start();

$isAdmin = $_SESSION['is_admin'];
$visibleQry = $isAdmin ? "" : "AND visible_yn = 'Y'";  //관리자는 visible_yn = 'N' 도 보이게
$query = "SELECT * FROM board_notice WHERE 1=1 AND notice_yn = 'N' $visibleQry ";
$keyword = $_GET['keyword'] ?? '';
$param = array();
$where = '';
if ($keyword) {
    $param['keyword'] = $keyword;
    $param['searchTp'] = $_GET['searchTp'];
    if ($_GET['searchTp'] == "A") {
        $where .= "AND title LIKE :keyword ";
    } elseif ($_GET['searchTp'] == "B") {
        $where .= "AND content LIKE :keyword ";
    } elseif ($_GET['searchTp'] == "C") {
        $where .= "AND (title LIKE :keyword OR content LIKE :keyword) ";
    }
}
if (!isset($_GET['page']) || !is_numeric($_GET['page']) || $_GET['page'] < 1) $_GET['page'] = 1;
$limit = 10;
$query .= $where;
$query .= "ORDER BY created_at DESC, id DESC LIMIT " . ($_GET['page'] - 1) * $limit . ", $limit";
// echo $query;
// exit;
$stmt = $pdo->prepare($query);
if ($keyword)    $stmt->bindValue(':keyword', "%$keyword%");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query_notice = "SELECT * FROM board_notice WHERE 1=1 AND notice_yn = 'Y' $visibleQry ORDER BY created_at DESC, id DESC";
$stmt_notice = $pdo->query($query_notice);
$rows_notice = $stmt_notice->fetchAll(PDO::FETCH_ASSOC);

$stmt_total = $pdo->prepare("SELECT COUNT(*) FROM board_notice WHERE notice_yn = 'N' $visibleQry $where");
if ($keyword)    $stmt_total->bindValue(':keyword', "%$keyword%");
$stmt_total->execute();
$total = $stmt_total->fetchColumn();
$totalPage = ceil($total / $limit);
// $stmt = $pdo->prepare(" WHERE")

$addParam = $param ? "&" . http_build_query($param) : "";
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="list">
    <colgroup>
        <col style="width:60px;">
        <col style="width:auto">
        <col style="width:100px;">
        <col style="width:60px;">
    </colgroup>
    <thead>
        <tr class="listhead" align="center">
            <th class="tdnum">번호</th>
            <th class="tdsub">제목</th>
            <th class="tddate">날짜</th>
            <th class="tdhit">조회</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows_notice as $row) { ?>
            <tr class="listnotice listtr <?= (int)$row['id'] === $id ? "currenttr" : "" ?> <?= $row['visible_yn'] === 'N' ? "novisible" : ""?>">
                <td class="tdnum">공지</td>
                <td class="tdsub"><a href="view.php?id=<?= $row['id'] . $addParam . "&page=" . $_GET['page'] ?>"><?= htmlspecialchars($row['title']) ?></a></td>
                <td class="tdname"><?= substr($row['created_at'], 0, 10) ?></td>
                <td class="tdhit"><?= $row['hit'] ?></td>
            </tr>
        <?php } ?>
        <?php foreach ($rows as $row) { ?>
            <tr class="listtr <?= $row['id'] === $id ? "currenttr" : "" ?> <?= $row['visible_yn'] === 'N' ? "novisible" : ""?>">
                <td class="tdnum"><?= $row['id'] ?></td>
                <td class="tdsub"><a href="view.php?id=<?= $row['id'] . $addParam . "&page=" . $_GET['page'] ?>"><?= htmlspecialchars($row['title']) ?></a></td>
                <td class="tdname"><?= substr($row['created_at'], 0, 10) ?></td>
                <td class="tdhit"><?= $row['hit'] ?></td>
            </tr>
        <?php } ?>
        <?php if (!$rows) { ?>
            <tr>
                <td class="tdnum" colspan="4">검색 결과가 없습니다.</td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<div class="btns-container">
    <div class="btns-container--left">
        <input type="button" class="btn-normal" value="목록" onclick="location.href='index.php';">
    </div>
    <div class="btns-container--right">
        <?= $isAdmin ? '<input type="button" class="btn-normal" value="글쓰기" onclick="location.href=\'write.php\';">' : "" ?>
        <?= $isAdmin ? '<input type="button" class="btn-normal" value="로그아웃" onclick="location.href=\'logout.php\';">' : "" ?>
    </div>
</div>
<div class="pagination">
    <ul>
        <?php
        $pageInterval = 5;
        $startPage = floor(($_GET['page'] - 1) / $pageInterval) * $pageInterval + 1;
        $endPage = min($startPage + $pageInterval - 1, $totalPage);

        $prevPage = $startPage - 1;
        if ($prevPage < 1) {
            $prevPage = 1;
        }

        $nextPage = $endPage + 1;
        if ($nextPage > $totalPage) {
            $nextPage = $totalPage;
        }
        ?>

        <?php if ($startPage > 1) { ?>
            <li><a href="index.php?page=<?= $prevPage ?><?= $addParam ?>">&lt;</a></li>
        <?php } ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++) { ?>
            <li><?= $i == $_GET['page'] ? "<a class=\"currentPage\">$i</a>" : "<a href=\"index.php?page=$i$addParam\">$i</a>" ?></li>
        <?php } ?>

        <?php if ($endPage < $totalPage) { ?>
            <li><a href="index.php?page=<?= $nextPage ?><?= $addParam ?>">&gt;</a></li>
        <?php } ?>

    </ul>
</div>
<form method="get" name="search" action="index.php">
    <div class="search">
        <select name="searchTp">
            <option value="A" <?=$_GET['searchTp'] === 'A' ? 'selected' : '' ?>>제목</option>
            <option value="B" <?=$_GET['searchTp'] === 'B' ? 'selected' : '' ?>>내용</option>
            <option value="C" <?=$_GET['searchTp'] === 'C' ? 'selected' : '' ?>>제목+내용</option>
        </select>
        <input type="text" name="keyword" class="searchKeyword" value="<?=$_GET['keyword'] ?? '' ?>" size="20">
        <input type="submit" class="btn-normal" value="검색">
    </div>
</form>