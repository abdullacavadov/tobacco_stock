<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

if(!isset($_POST['pid'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Məhsul ID-si göndərilməyib'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}



try {
    $pid = (int) $_POST['pid'];
    $name = trim($_POST['name'] ?? '');
    $package_weight = (float) ($_POST['weight'] ?? 0) / 1000;
    $type = trim($_POST['type'] ?? '');
    $stock = (int) ($_POST['stock'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $prod_date = $_POST['production_time'] ?? date('Y-m-d');


    if ($name == '') {
        throw new Exception('Məhsul seçilməyib');
    }

    if (!in_array($type, ['premium', 'strong'])) {
        throw new Exception('Yanlış növ');
    }

    if ($package_weight <= 0) {
        throw new Exception('Qab ölçüsü yanlışdır');
    }

    if ($stock <= 0) {
        throw new Exception('Məhsul sayı yanlışdır');
    }

    if ($price <= 0) {
        throw new Exception('Məhsulun maya dəyəri yanlışdır');
    }


    if ($prod_date > date('Y-m-d')) {
        throw new Exception('İstehsal tarixi gələcəkdə ola bilməz');
    }


    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
    UPDATE products
    SET
        name = ?,
        weight = ?,
        stock = ?,
        type = ?,
        price = ?,
        production_date = ?
    WHERE id = ?
");

    $stmt->execute([
        $name,
        $package_weight,
        $stock,
        $type,
        $price,
        $prod_date,
        $pid
    ]);


    $pdo->commit();

    echo json_encode([
        'success' => true
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}