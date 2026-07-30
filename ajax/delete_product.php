<?php

require '../inc/db.php';


$pid = $_GET['pid'];

$pid = (int) ($_GET['pid'] ?? 0);

if ($pid <= 0) {
    throw new Exception('Yanlış məhsul ID-si.');
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("
    SELECT id
    FROM products
    WHERE id = ?
    FOR UPDATE
");

    $stmt->execute([$pid]);

    if (!$stmt->fetch()) {
        throw new Exception('Məhsul tapılmadı.');
    }



    $stmt = $pdo->prepare("
    SELECT
        material_id,
        cost,
        qty
    FROM product_material_usage
    WHERE product_id = ?
    FOR UPDATE
");

    $stmt->execute([$pid]);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
    UPDATE raw_materials
    SET stock = ROUND(stock + ?, 4),
        price = ROUND(price + ?, 4)
    WHERE id = ?
");

    foreach ($materials as $row) {

        $stmt->execute([
            $row['qty'],
            $row['cost'],
            $row['material_id']
        ]);

    }




    $stmt = $pdo->prepare("
    SELECT
        sauce_id,
        qty,
        cost
    FROM product_flavour_sauce_usage
    WHERE product_id = ?
    FOR UPDATE
");

    $stmt->execute([$pid]);
    $sauces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
    UPDATE sauce_with_flavour
    SET
        qty = ROUND(qty + ?, 4),
        cost = ROUND(cost + ?, 4)
    WHERE id = ?
");

    foreach ($sauces as $row) {

        $stmt->execute([
            $row['qty'],
            $row['cost'],
            $row['sauce_id']
        ]);

    }

    $stmt = $pdo->prepare("
    DELETE FROM products
    WHERE id = ?
");

    $stmt->execute([$pid]);


    if ($stmt->rowCount() === 0) {
        throw new Exception('Məhsul silinmədi.');
    }

    $pdo->commit();

    header("Location: ../products.php");
    exit();
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw new Exception($e->getMessage());
}



