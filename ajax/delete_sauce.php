<?php

require '../inc/db.php';


$sid = $_GET['sid'];

$stmt = $pdo->prepare("DELETE FROM sauce_stock WHERE id = ?");
$stmt->execute([$sid]);

header("Location: ../sauces.php");
exit();