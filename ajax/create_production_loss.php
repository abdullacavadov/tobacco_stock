<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {



    $reason = trim($_POST['reason'] ?? '');
    $note = trim($_POST['note'] ?? '');

    $sources = $_POST['source'] ?? [];
    $itemIds = $_POST['item'] ?? [];
    $qtys = $_POST['qty'] ?? [];

    if (
        !in_array(
            $reason,
            [
                'test',
                'quality_control',
                'waste',
                'damaged',
                'other'
            ]
        )
    ) {
        throw new Exception('Yanlış səbəb');
    }

    if ($note === '') {
        throw new Exception(
            'Qeyd boş ola bilməz'
        );
    }

    if (empty($sources)) {
        throw new Exception(
            'Ən azı bir məhsul seçilməlidir'
        );
    }

    if (
        count($sources) != count($itemIds)
        ||
        count($sources) != count($qtys)
    ) {
        throw new Exception(
            'Göndərilən məlumatlar natamamdır'
        );
    }

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO production_loss
        (
            reason,
            note
        )
        VALUES
        (
            ?,
            ?
        )
    ");

    $stmt->execute([
        $reason,
        $note
    ]);

    $lossId = $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Prepared Statement
    |--------------------------------------------------------------------------
    */

    $insertItem = $pdo->prepare("
        INSERT INTO production_loss_items
        (
            loss_id,
            source,
            source_id,
            qty,
            unit_price,
            cost
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    $usedItems = [];

    /*
    |--------------------------------------------------------------------------
    | Items
    |--------------------------------------------------------------------------
    */

    foreach ($sources as $i => $source) {

        $source = trim($source);

        $itemId = (int) ($itemIds[$i] ?? 0);

        $qty = (float) ($qtys[$i] ?? 0);

        if (
            !in_array(
                $source,
                [
                    'raw',
                    'sauce',
                    'flavour',
                    'product'
                ]
            )
        ) {
            throw new Exception('Yanlış anbar seçilib');
        }

        if ($itemId <= 0) {
            throw new Exception('Məhsul seçilməyib');
        }

        if ($qty <= 0) {
            throw new Exception('Miqdar düzgün deyil');
        }

        // Eyni partiya iki dəfə əlavə edilməsin
        $uniqueKey = $source . '_' . $itemId;

        if (isset($usedItems[$uniqueKey])) {
            throw new Exception(
                'Eyni məhsul iki dəfə seçilə bilməz'
            );
        }

        $usedItems[$uniqueKey] = true;

        switch ($source) {

            /*
            |--------------------------------------------------------------------------
            | RAW MATERIAL
            |--------------------------------------------------------------------------
            */

            case 'raw':

                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        name,
                        stock,
                        price
                    FROM raw_materials
                    WHERE id=?
                    FOR UPDATE
                ");

                $stmt->execute([
                    $itemId
                ]);

                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    throw new Exception('Xammal tapılmadı');
                }

                if ($item['stock'] < $qty) {

                    throw new Exception(
                        $item['name'] .
                        ' stokda kifayət qədər yoxdur'
                    );

                }

                $unitPrice = $item['price'];

                $cost = round(
                    $unitPrice * $qty,
                    4
                );

                $stmt = $pdo->prepare("
                    UPDATE raw_materials
                    SET stock = stock - ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $qty,
                    $itemId
                ]);

                break;


            /*
|--------------------------------------------------------------------------
| SAUCE STOCK
|--------------------------------------------------------------------------
*/

            case 'sauce':

                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        type,
                        stock,
                        price
                    FROM sauce_stock
                    WHERE id = ?
                    FOR UPDATE
                ");

                $stmt->execute([
                    $itemId
                ]);

                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    throw new Exception('Hazır sous tapılmadı');
                }

                if ($item['stock'] < $qty) {

                    throw new Exception(
                        'Hazır sous stokda kifayət qədər deyil'
                    );

                }

                // 1 kq qiyməti
                $unitPrice = $item['price'] / $item['stock'];

                // İstifadə olunan hissənin maya dəyəri
                $cost = round(
                    $unitPrice * $qty,
                    4
                );

                $stmt = $pdo->prepare("
                    UPDATE sauce_stock
                    SET
                        stock = stock - ?,
                        price = price - ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $qty,
                    $cost,
                    $itemId
                ]);

                break;


            /*
        |--------------------------------------------------------------------------
        | FLAVOUR STOCK
        |--------------------------------------------------------------------------
        */



            case 'flavour':

                $select = $pdo->prepare("
                    SELECT
                        id,
                        flavour_name,
                        sauce_type,
                        qty,
                        cost
                    FROM sauce_with_flavour
                    WHERE id = ?
                    FOR UPDATE
                ");

                $select->execute([$itemId]);

                $material = $select->fetch(PDO::FETCH_ASSOC);

                if (!$material) {
                    throw new Exception('Dadlandırılmış sous tapılmadı');
                }

                if ($material['qty'] < $qty) {

                    throw new Exception(
                        $material['flavour_name'] .
                        ' stokda kifayət qədər yoxdur. Mövcuddur: ' .
                        number_format($material['qty'], 2) .
                        ' kq'
                    );

                }

                // 1 kq maya dəyəri
                $unitPrice = $material['cost'] / $material['qty'];

                // İstifadə olunan hissənin maya dəyəri
                $cost = round($unitPrice * $qty, 4);

                // Qalan hissənin ümumi maya dəyəri
                $newCost = round($material['cost'] - $cost, 4);

                $update = $pdo->prepare("
                    UPDATE sauce_with_flavour
                    SET
                        qty = qty - ?,
                        cost = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $qty,
                    $newCost,
                    $itemId
                ]);


                break;

            /*
            |--------------------------------------------------------------------------
            | PRODUCT STOCK
            |--------------------------------------------------------------------------
            */

            case 'product':

                $select = $pdo->prepare("
                    SELECT
                        id,
                        name,
                        stock,
                        price,
                        weight,
                        type
                    FROM products
                    WHERE id = ?
                    FOR UPDATE
                ");

                $select->execute([$itemId]);

                $material = $select->fetch(PDO::FETCH_ASSOC);

                if (!$material) {
                    throw new Exception('Hazır məhsul tapılmadı');
                }

                if ($material['stock'] < $qty) {

                    throw new Exception(
                        $material['name'] .
                        ' stokda kifayət qədər yoxdur. Mövcuddur: ' .
                        $material['stock'] .
                        ' əd'
                    );

                }

                // price = 1 ədəd məhsulun maya dəyəri
                $unitPrice = $material['price'];

                $cost = round($unitPrice * $qty, 4);

                $update = $pdo->prepare("
                    UPDATE products
                    SET stock = stock - ?
                    WHERE id = ?
                ");

                $update->execute([
                    $qty,
                    $itemId
                ]);

                break;


        }

        $insertItem->execute([
            $lossId,
            $source,
            $itemId,
            $qty,
            $unitPrice,
            $cost
        ]);
        
    }

    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'İstehsalat itkisi uğurla qeydə alındı.'
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