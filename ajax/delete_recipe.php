<?php

require '../inc/db.php';


$frid = $_GET['frid'];

$stmt = $pdo->prepare("UPDATE flavour_recipes SET is_active = 0 WHERE id = ?");
$stmt->execute([$frid]);

header("Location: ../recipes.php");
exit();