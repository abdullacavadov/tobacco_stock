<?php

require '../inc/db.php';

if ($_POST['custom_name'] != '') {
    $name = $_POST['custom_name'];
} else {
    $name = $_POST['name'];
}
$stock = (float) $_POST['stock'];
$price = (float) $_POST['price'];
$type = trim($_POST['type']);

if (empty($name)) {
    exit('Xammal seçilməyib.');
}

if (empty($type)) {
    exit('Məhsulun növü seçilməyib.');
}

if (!isset($_POST['stock']) || $_POST['stock'] === '') {
    exit('Məhsulun həcmini qeyd edin (KQ / ƏD).');
}

if (!isset($_POST['price']) || $_POST['price'] === '') {
    exit('Məhsulun qiymətini qeyd edin (AZN).');
}


$statement = $pdo->prepare("INSERT INTO raw_materials (name, type, stock, price) VALUES (?,?,?,?)");
$statement->execute([$name, $type, $stock, $price]);


echo 'success';