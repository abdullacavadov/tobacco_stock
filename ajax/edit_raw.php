<?php

require '../inc/db.php';

$rid = $_POST['rid'];
$name = trim($_POST['name']);
$stock = (float) $_POST['stock'];
$price = (float) $_POST['price'];
$type = trim($_POST['type']);

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

if($type == 'raw' || $type == 'flavour' ) {
    $unit = 'kq';
} elseif($type == 'package' || $type == 'label' || $type == 'cover') {
    $unit = 'əd';
}

try {

$statement = $pdo->prepare("UPDATE raw_materials SET name = ?, type = ?, stock = ?, price = ?, unit = ? WHERE id = ?");
$statement->execute([$name, $type, $stock, $price, $unit, $rid]);

echo 'success';

} catch (PDOException $e) {
    exit('Server xətası baş verdi.');
    // Debug zamanı açıq saxla
    // echo $e->getMessage();
}