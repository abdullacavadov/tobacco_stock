<?php

require '../inc/db.php';


$srid = $_GET['srid'];

$stmt = $pdo->prepare("DELETE FROM sauce_recipes WHERE id = ?");
$stmt->execute([$srid]);

header("Location: ../sauce-recipes.php");
exit();