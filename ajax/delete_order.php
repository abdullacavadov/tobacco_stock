<?php

require '../inc/db.php';


$orderNo = $_GET['oid'] ?? 0;

if ($orderNo <= 0) {
    throw new Exception('Yanlış satış ID-si.');
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE order_no = ?
    FOR UPDATE
");

    $stmt->execute([$orderNo]);

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$orders) {
        throw new Exception('Belə bir satış tapılmadı.');
    }


    $config = [

        'raw' => [
            'table' => 'raw_materials',
            'stockField' => 'stock',
            'moneyField' => 'price',
            'hasMoney' => true
        ],

        'sauce' => [
            'table' => 'sauce_stock',
            'stockField' => 'stock',
            'moneyField' => 'price',
            'hasMoney' => true
        ],

        'flavour' => [
            'table' => 'sauce_with_flavour',
            'stockField' => 'qty',
            'moneyField' => 'cost',
            'hasMoney' => true
        ],

        'product' => [
            'table' => 'products',
            'stockField' => 'stock',
            'hasMoney' => false
        ]

    ];


    foreach ($orders as $order) {

        $cfg = $config[$order['kind']];

        if ($cfg['hasMoney']) {

            $totalCost = $order['qty'] * $order['cost'];

            $sql = "
            UPDATE {$cfg['table']}
            SET
                {$cfg['stockField']} = {$cfg['stockField']} + ?,
                {$cfg['moneyField']} = {$cfg['moneyField']} + ?
            WHERE id = ?
        ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $order['qty'],
                $totalCost,
                $order['item_id']
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Stok qeydi tapılmadı.');
            }

        } else {

            $sql = "
            UPDATE {$cfg['table']}
            SET
                {$cfg['stockField']} = {$cfg['stockField']} + ?
            WHERE id = ?
        ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $order['qty'],
                $order['item_id']
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Stok qeydi tapılmadı.');
            }

        }

    }


    $stmt = $pdo->prepare("
        DELETE
        FROM orders
        WHERE order_no = ?
    ");

    $stmt->execute([$orderNo]);

    $pdo->commit();

    header("Location: ../orders.php");
    exit();
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw new Exception($e->getMessage());
}



