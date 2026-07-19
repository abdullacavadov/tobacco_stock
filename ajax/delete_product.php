<?php

require '../inc/db.php';


$pid = $_GET['pid'];

$stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$pid]);

header("Location: ../products.php");
exit();