<?php

require '../inc/db.php';

$rid = $_POST['rid'];
$name = trim($_POST['name']);
$stock = (float) $_POST['stock'];
$price = (float) $_POST['price'];
$edv = trim($_POST['edv']);
$type = trim($_POST['type']);
$supplier = trim($_POST['supplier']);
$description = trim($_POST['description']);

if (empty($name)) {
    exit('Xammalın adını qeyd edin.');
}

if (empty($type)) {
    exit('Xammalın növünü seçin.');
}

if (!isset($_POST['stock']) || $_POST['stock'] === '') {
    exit('Xammalın həcmini qeyd edin (KQ).');
}

if (!isset($_POST['price']) || $_POST['price'] === '') {
    exit('Xammalın qiymətini qeyd edin (AZN).');
}

if ($type == 'raw' || $type == 'flavour') {
    $unit = 'kq';
} elseif ($type == 'package' || $type == 'label' || $type == 'cover') {
    $unit = 'əd';
}

if (empty($supplier)) {
    exit('Xammalın təchizatçısını qeyd edin.');
}

try {

    $statement = $pdo->prepare("UPDATE raw_materials SET name = ?, type = ?, stock = ?, supplier = ?, price = ?, edv = ?, unit = ?, description = ? WHERE id = ?");
    $statement->execute([$name, $type, $stock, $supplier, $price, $edv, $unit, $description, $rid]);

    echo 'success';

} catch (PDOException $e) {
    echo $e->getMessage();
    exit('Server xətası baş verdi.');

}