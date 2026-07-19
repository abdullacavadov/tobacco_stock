<?php

require '../db.php';

$id = $_POST['id'];
$qty = $_POST['qty'];

$pdo->beginTransaction();

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM products
        WHERE id=?
    ");

    $stmt->execute([$id]);

    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception('Məhsul tapılmadı');
    }

    if ($product['stock'] < $qty) {
        throw new Exception('Stok kifayət deyil');
    }

    $stmt = $pdo->prepare("
        UPDATE products
        SET stock=stock-?
        WHERE id=?
    ");

    $stmt->execute([
        $qty,
        $id
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO movements
        (
            movement_type,
            product_name,
            quantity
        )
        VALUES
        (
            'satış',
            ?,
            ?
        )
    ");

    $stmt->execute([
        $product['name'],
        $qty
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}