<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $id = (int) ($_POST['name'] ?? '0');
    $qty = (float) ($_POST['qty'] ?? 0);
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
    FROM sauce_with_flavour
    WHERE id = ?
    AND qty > 0
    FOR UPDATE
");

    $stmt->execute([$id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Məhsul stokda yoxdur');
    }

    if ($product['qty'] < $qty) {

        throw new Exception(
            'Məhsul çatmır. Lazımdır: ' .
            $qty . 'kq, Mövcuddur: ' .
            number_format($product['qty'], 2) . 'kq'
        );

    }

    $cost = $product['cost'] / $product['qty'];      // Vahidin maya dəyəri
    $type = $product['sauce_type'];
    if($type == 'premium'){
        $name = 'Premium sous - ' . $product['flavour_name'];
    } else {
        $name = 'Strong sous - ' . $product['flavour_name'];
    }
    $kind = 'flavour';
    
    $weight = '';
    $prod_date = '';

    $allCost = (float) $cost*$qty;


    if ($sellPrice < $cost) {
        throw new Exception('Satış qiyməti maya dəyərindən azdır.');
    }


    $stmt = $pdo->prepare("
    UPDATE sauce_with_flavour
    SET qty = qty - ?, cost = cost - ?
    WHERE id = ?
");

    $stmt->execute([
        $qty,
        $allCost,
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
        kind,
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
        $sellPrice,     // Vahidin satış qiyməti
        $prod_date,
        $kind,
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