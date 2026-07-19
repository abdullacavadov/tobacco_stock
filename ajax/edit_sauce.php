<?php

require_once("../inc/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Sorğu metodu yanlışdır.');
}

$id = (int)($_POST['id'] ?? 0);
$stock = (float)($_POST['stock'] ?? 0);
$type = $_POST['type'] ?? '';
$price = (float)($_POST['price'] ?? 0);


if ($id <= 0) {
    exit('Sous tapılmadı.');
}

if (!isset($_POST['stock']) || $_POST['stock'] === '') {
    exit('Zəhmət olmasa, həcm daxil edin.');
}

if (!isset($_POST['type']) || $_POST['type'] === '') {
    exit('Zəhmət olmasa, növü seçin.');
}

if (!isset($_POST['price']) || $_POST['price'] === '') {
    exit('Zəhmət olmasa, ümumi həcmin qiymətini daxil edin.');
}



try {

    $statement = $pdo->prepare("
        UPDATE sauce_stock
        SET
            stock = ?,
            type = ?,
            price = ?
        WHERE id = ?
    ");

    $statement->execute([
        $stock,
        $type,
        $price,
        $id
    ]);

    echo 'success';

} catch (PDOException $e) {

    echo 'Server xətası baş verdi.';

    // Debug zamanı açıq saxla
    // echo $e->getMessage();
}