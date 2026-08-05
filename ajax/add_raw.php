<?php

require '../inc/db.php';

if ($_POST['custom_name'] != '') {
    $name = $_POST['custom_name'];
} else {
    $name = $_POST['name'];
}
$stock = (float) $_POST['stock'];
$price = (float) $_POST['price'];
$edv = trim($_POST['edv']);
$type = trim($_POST['type']);
$supplier = trim($_POST['supplier']);
$description = trim($_POST['description']);

if (empty($name)) {
    exit('Xammal seçilməyib.');
}

if (empty($type)) {
    exit('Məhsulun növü seçilməyib.');
}

if (empty($supplier)) {
    exit('Təchizatçı qeyd edilməyib.');
}

if (!isset($_POST['stock']) || $_POST['stock'] === '') {
    exit('Məhsulun həcmini qeyd edin (KQ / ƏD).');
}

if (!isset($_POST['price']) || $_POST['price'] === '') {
    exit('Məhsulun qiymətini qeyd edin (AZN).');
}

if ($type == 'raw') {
    $unit = 'kq';
} elseif ($type == 'flavour') {
    $unit = 'kq';
} elseif ($type == 'package') {
    $unit = 'əd';
} elseif ($type == 'label') {
    $unit = 'əd';
} elseif ($type == 'cover') {
    $unit = 'əd';
}

$statement = $pdo->prepare("INSERT INTO raw_materials (name, type, stock, edv, unit, price, supplier, description) VALUES (?,?,?,?,?,?,?,?)");
$statement->execute([$name, $type, $stock, $edv, $unit, $price, $supplier, $description]);


echo 'success';