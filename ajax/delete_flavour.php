<?php

require '../inc/db.php';


$fid = $_GET['fid'];

$stmt = $pdo->prepare("DELETE FROM sauce_with_flavour WHERE id = ?");
$stmt->execute([$fid]);

header("Location: ../flavours.php");
exit();