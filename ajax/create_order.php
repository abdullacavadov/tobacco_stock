<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {



    $items = $_POST['items'] ?? [];
    $custName = trim($_POST['customer'] ?? '');
    $order_no = date('YmdHis') . random_int(1000, 9999);
    
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
            'moneyField' => 'price',
            'hasMoney' => false
        ]

    ];

    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("
        INSERT INTO orders
        (
            order_no,
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
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
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

                $cost = $product['price'] / $stock;
                $allCost = $cost * $qty;

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

                $cost = $product['price'] / $stock;
                $allCost = $cost * $qty;

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

                $cost = $product['cost'] / $stock;
                $allCost = $cost * $qty;

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

                $cost = (float) $product['price'];

                // products cədvəlində ümumi maya dəyişmir
                $allCost = 0;

                $name = $product['name'];
                $type = $product['type'];
                $weight = $product['weight'];
                $productionDate = $product['production_date'];

                break;
        }

        if ($sellPrice < $cost * $qty) {
            throw new Exception(
                $name . ': satış qiyməti maya dəyərindən azdır. Miqdarın mayası: ' . $cost * $qty . '₼'
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
                $allCost,
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
            $order_no,
            $name,
            $weight,
            $qty,
            $type,
            $cost,
            $sellPrice,
            $productionDate,
            $kind,
            $custName
        ]);

    } // foreach sonu


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