<?php

require '../inc/db.php';


$stock = (float) $_POST['stock'];
$price = (float) $_POST['price'];
$type = trim($_POST['type']);


if (empty($type)) {
    exit('Sousun növü seçilməyib.');
}

if (!isset($_POST['stock']) || $_POST['stock'] === '') {
    exit('Sousun həcmini qeyd edin (KQ).');
}

if (!isset($_POST['price']) || $_POST['price'] === '') {
    exit('Sousun ümumi alış qiymətini qeyd edin (AZN).');
}


$statement = $pdo->prepare("INSERT INTO sauce_stock (type, stock, price) VALUES (?,?,?)");
$statement->execute([$type, $stock, $price]);


echo 'success';