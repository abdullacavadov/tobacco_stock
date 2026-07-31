<?php

require '../inc/db.php';


$frid = $_GET['frid'];

$stmt = $pdo->prepare("DELETE FROM flavour_recipes WHERE id = ?");
$stmt->execute([$frid]);

header("Location: ../recipes.php");
exit();