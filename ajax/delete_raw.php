<?php

require '../inc/db.php';


$rid = $_GET['rid'];

$stmt = $pdo->prepare("DELETE FROM raw_materials WHERE id = ?");
$stmt->execute([$rid]);

header("Location: ../raw.php");
exit();