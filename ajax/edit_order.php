<?php

require '../inc/db.php';


$orderNo = $_POST['oid'] ?? 0;

if ($orderNo <= 0) {
    throw new Exception('Yanlış satış ID-si.');
}


$items = $_POST['items'] ?? [];
$custName = trim($_POST['customer'] ?? '');

if (empty($items)) {
    throw new Exception('Ən azı bir məhsul seçilməlidir.');
}

if ($custName === '') {
    throw new Exception('Gələcək analizlər üçün müştəri adı qeyd etmək tövsiyyə olunur.');
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



    $insertStmt = $pdo->prepare("
    INSERT INTO orders
    (
        order_no,
        item_id,
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
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
    ");


    foreach ($items as $item) {

        $kind = trim($item['kind'] ?? '');
        $id = (int) ($item['id'] ?? 0);
        $qty = (float) ($item['qty'] ?? 0);
        $sellPrice = (float) ($item['price'] ?? 0);

        if (!isset($config[$kind])) {
            throw new Exception('Yanlış məhsul növü.');
        }

        if ($id <= 0) {
            throw new Exception('Məhsul seçilməyib.');
        }

        if ($qty <= 0) {
            throw new Exception('Yanlış miqdar.');
        }

        if ($sellPrice <= 0) {
            throw new Exception('Satış qiyməti düzgün deyil.');
        }

        $cfg = $config[$kind];

        $sql = "
        SELECT *
        FROM {$cfg['table']}
        WHERE id = ?
        AND {$cfg['stockField']} > 0
        FOR UPDATE
    ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception('Məhsul stokda yoxdur.');
        }

        $item_id = $id;
        switch ($kind) {

            case 'raw':

                $stock = (float) $product['stock'];

                if ($stock < $qty) {
                    throw new Exception(
                        $product['name'] .
                        ': stok çatmır. Lazımdır ' .
                        $qty . ' ' . $product['unit'] .
                        ', mövcuddur ' .
                        number_format($stock, 2) . ' ' . $product['unit']
                    );
                }

                $unitCost = (float) $product['price'] / (float) $stock;
                $totalCost = $unitCost * $qty;

                $name = $product['name'];
                $type = '';
                $weight = '';
                $productionDate = '';

                break;


            case 'sauce':

                $stock = (float) $product['stock'];

                if ($stock < $qty) {
                    throw new Exception(
                        'Sous çatmır. Lazımdır ' .
                        $qty . ' kq, mövcuddur ' .
                        number_format($stock, 2) . ' kq'
                    );
                }

                $unitCost = (float) $product['price'] / (float) $stock;
                $totalCost = $unitCost * $qty;

                $name = $product['type'] == 'premium'
                    ? 'Premium sous'
                    : 'Strong sous';

                $type = $product['type'];
                $weight = '';
                $productionDate = '';

                break;


            case 'flavour':

                $stock = (float) $product['qty'];

                if ($stock < $qty) {
                    throw new Exception(
                        'Məhsul çatmır. Lazımdır ' .
                        $qty . ' kq, mövcuddur ' .
                        number_format($stock, 2) . ' kq'
                    );
                }

                $unitCost = (float) $product['cost'] / (float) $stock;
                $totalCost = $unitCost * $qty;

                $type = $product['sauce_type'];

                $name = ($type == 'premium'
                    ? 'Premium sous - '
                    : 'Strong sous - ')
                    . $product['flavour_name'];

                $weight = '';
                $productionDate = '';

                break;


            case 'product':

                $stock = (int) $product['stock'];

                if ($stock < $qty) {
                    throw new Exception(
                        $product['name'] .
                        ': stok çatmır. Lazımdır ' .
                        $qty .
                        ' əd, mövcuddur ' .
                        $stock .
                        ' əd'
                    );
                }

                $unitCost = (float) $product['price'];

                // products cədvəlində ümumi maya dəyişmir
                $totalCost = 0;

                $name = $product['name'];
                $type = $product['type'];
                $weight = $product['weight'];
                $productionDate = $product['production_date'];

                break;
        }

        if ($sellPrice < $unitCost) {
            throw new Exception(
                $name . ': satış qiyməti maya dəyərindən azdır. Miqdarın mayası: ' . $unitCost . '₼'
            );
        }


        // Stokdan sil

        if ($cfg['hasMoney']) {

            $sql = "
                UPDATE {$cfg['table']}
                SET
                    {$cfg['stockField']} = {$cfg['stockField']} - ?,
                    {$cfg['moneyField']} = {$cfg['moneyField']} - ?
                WHERE id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $qty,
                $totalCost,
                $id
            ]);

        } else {

            $sql = "
                UPDATE {$cfg['table']}
                SET
                    {$cfg['stockField']} = {$cfg['stockField']} - ?
                WHERE id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $qty,
                $id
            ]);

        }


        // Satışı qeyd et

        $insertStmt->execute([
            $orderNo,
            $item_id,
            $name,
            $weight,
            $qty,
            $type,
            $unitCost,
            $sellPrice,
            $productionDate,
            $kind,
            $custName
        ]);

    }






    $pdo->commit();

    echo json_encode([
        'success' => true
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw new Exception($e->getMessage());
}



