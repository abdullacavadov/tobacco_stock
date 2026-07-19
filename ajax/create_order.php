<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $id = (int) ($_POST['name'] ?? '0');
    $qty = (int) ($_POST['qty'] ?? 0);
    $sellPrice = (float) ($_POST['price'] ?? 0);
    $custName = trim($_POST['customer'] ?? '');

    if ($id <= 0) {
        throw new Exception('Məhsul seçilməyib');
    }

    if ($qty <= 0) {
        throw new Exception('Yanlış miqdar');
    }

    if ($sellPrice <= 0) {
        throw new Exception('Satış qiyməti düzgün deyil');
    }

    if ($custName == '') {
        throw new Exception('Gələcək analizlər üçün müştəri adı qeyd etmək tövsiyyə olunur.');
    }


    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ?
    AND stock > 0
    FOR UPDATE
");

    $stmt->execute([$id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Məhsul stokda yoxdur');
    }

    if ($product['stock'] < $qty) {

        throw new Exception(
            'Məhsul çatmır. Lazımdır: ' .
            $qty .
            ' əd, Mövcuddur: ' .
            number_format($product['stock'], 0) . " əd."
        );

    }

    $weight = $product['weight'];
    $prod_date = $product['production_date'];
    $cost = $product['price'];      // 1 ədədin maya dəyəri
    $name = $product['name'];
    $type = $product['type'];


    if ($sellPrice < $cost) {
        throw new Exception('Satış qiyməti maya dəyərindən azdır.');
    }


    $stmt = $pdo->prepare("
    UPDATE products
    SET stock = stock - ?
    WHERE id = ?
");

    $stmt->execute([
        $qty,
        $id
    ]);


    $stmt = $pdo->prepare("
    INSERT INTO orders
    (
        name,
        weight,
        qty,
        type,
        cost,
        sell_price,
        production_date,
        customer
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )
");

    $stmt->execute([
        $name,
        $weight,
        $qty,
        $type,
        $cost,   // maya dəyəri
        $sellPrice,     // 1 ədəd satış qiyməti
        $prod_date,
        $custName
    ]);


    $pdo->commit();

    echo json_encode([
        'success' => true
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}