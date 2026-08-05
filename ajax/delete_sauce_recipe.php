<?php

require '../inc/db.php';


$srid = $_GET['srid'];

$stmt = $pdo->prepare("UPDATE sauce_recipes SET is_active = 0 WHERE id = ?");
$stmt->execute([$srid]);

header("Location: ../sauce-recipes.php");
exit();