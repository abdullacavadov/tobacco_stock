<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sauce_type = trim($_POST['sauce_type'] ?? '');
    $qty = (float) ($_POST['qty'] ?? 0);
    $recipe_id = (int) ($_POST['recipe_id'] ?? 0);

    $saucePlan = [];
    $materialPlan = [];
    $cost = 0;

    if (!in_array($sauce_type, ['premium', 'strong'])) {
        throw new Exception('Yanlış sous növü');
    }

    if ($qty <= 0) {
        throw new Exception('Yanlış miqdar');
    }

    if ($recipe_id <= 0) {
        throw new Exception('Resept seçilməyib');
    }

    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.name,
            r.sauce_type,
            i.flavour_name,
            i.percentage
        FROM flavour_recipes r
        LEFT JOIN flavour_recipe_items i
            ON i.recipe_id = r.id
        WHERE r.id = ?
    ");

    $stmt->execute([$recipe_id]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        throw new Exception('Resept tapılmadı');
    }

    $recipe = [
        'id' => $rows[0]['id'],
        'name' => $rows[0]['name'],
        'sauce_type' => $rows[0]['sauce_type'],
        'items' => []
    ];

    if ($recipe['sauce_type'] !== $sauce_type) {
        throw new Exception('Bu resept seçilən sous növünə aid deyil');
    }



    foreach ($rows as $row) {

        if (!empty($row['flavour_name'])) {



            $recipe['items'][] = [
                'name' => $row['flavour_name'],
                'percent' => (float) $row['percentage']
            ];

        }

    }

    if (empty($recipe['items'])) {
        throw new Exception('Reseptdə heç bir aromat yoxdur');
    }

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | SAUCE FIFO
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            stock,
            price
        FROM sauce_stock
        WHERE type = ?
        AND stock > 0
        ORDER BY created_at ASC, id ASC
        FOR UPDATE
    ");

    $stmt->execute([$sauce_type]);

    $sauceStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$sauceStocks) {
        throw new Exception('Hazır sous stokda yoxdur');
    }

    $totalSauceStock = array_sum(array_column($sauceStocks, 'stock'));

    if ($totalSauceStock < $qty) {

        throw new Exception(
            'Hazır sous çatmır. Lazımdır: ' .
            number_format($qty, 2) .
            ' kq, Mövcuddur: ' .
            number_format($totalSauceStock, 2) .
            ' kq'
        );

    }

    $remainingSauce = $qty;

    foreach ($sauceStocks as $batch) {

        if ($remainingSauce <= 0) {
            break;
        }

        $used = min($batch['stock'], $remainingSauce);

        $unitPrice = $batch['price'] / $batch['stock'];

        $usedCost = round(
            $unitPrice * $used,
            4
        );

        $saucePlan[] = [
            'id' => $batch['id'],
            'used' => $used,
            'used_cost' => $usedCost
        ];

        $cost += $usedCost;

        $remainingSauce -= $used;
    }

    /*
    |--------------------------------------------------------------------------
    | FLAVOUR FIFO
    |--------------------------------------------------------------------------
    */



    foreach ($recipe['items'] as $item) {

        $materialName = $item['name'];

        // Məsələn: 50kg * 3% = 1.5kg
        $required = round(
            $qty * ($item['percent'] / 100),
            4
        );

        $stmt = $pdo->prepare("
            SELECT
                id,
                stock,
                price
            FROM raw_materials
            WHERE
                type IN ('flavour', 'raw')
                AND name=?
                AND stock>0
            ORDER BY in_stock ASC, id ASC
            FOR UPDATE
        ");

        $stmt->execute([
            $materialName
        ]);

        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$stocks) {

            throw new Exception(
                $materialName . ' stokda yoxdur'
            );

        }

        $totalStock = array_sum(
            array_column($stocks, 'stock')
        );

        if ($totalStock < $required) {

            throw new Exception(
                $materialName .
                ' çatmır. Lazımdır: ' .
                number_format($required, 4) .
                ' kq, Mövcuddur: ' .
                number_format($totalStock, 4) .
                ' kq'
            );

        }

        $remaining = $required;

        foreach ($stocks as $batch) {

            if ($remaining <= 0) {
                break;
            }

            $used = min(
                $batch['stock'],
                $remaining
            );

            $unitPrice = $batch['price'] / $batch['stock'];

            $usedCost = round(
                $unitPrice * $used,
                4
            );

            $materialPlan[] = [
                'id' => $batch['id'],
                'used' => $used,
                'used_cost' => $usedCost
            ];

            $cost += $usedCost;

            $remaining -= $used;
        }

    }

    $updateSauce = $pdo->prepare("
        UPDATE sauce_stock
        SET
            stock = stock - ?,
            price = price - ?
        WHERE id = ?
    ");

    foreach ($saucePlan as $row) {

        $updateSauce->execute([
            $row['used'],
            $row['used_cost'],
            $row['id']
        ]);

        if ($updateSauce->rowCount() !== 1) {
            throw new Exception('Sous stokdan çıxarılmadı.');
        }

    }


    $updateMaterial = $pdo->prepare("
        UPDATE raw_materials
        SET
            stock = stock - ?,
            price = price - ?
        WHERE id = ?
    ");

    foreach ($materialPlan as $row) {

        $updateMaterial->execute([
            $row['used'],
            $row['used_cost'],
            $row['id']
        ]);

        if ($updateMaterial->rowCount() !== 1) {
            throw new Exception('Material stokdan çıxarılmadı.');
        }

    }

    $cost = round($cost, 4);


    $stmt = $pdo->prepare("
        INSERT INTO sauce_with_flavour
        (
            sauce_type,
            recipe_id,
            flavour_name,
            qty,
            cost,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW()
        )
        ");

    $stmt->execute([
        $sauce_type,
        $recipe_id,
        $recipe['name'],
        $qty,
        $cost
    ]);

    $sauceFlavourId = (int) $pdo->lastInsertId();



    $insertMaterialUsage = $pdo->prepare("
    INSERT INTO sauce_flavour_material_usage
(
    sauce_flavour_id,
    material_id,
    qty,
    cost
)
VALUES (?,?,?,?)
    ");


    foreach ($materialPlan as $row) {

        $insertMaterialUsage->execute([
            $sauceFlavourId,
            $row['id'],
            $row['used'],
            $row['used_cost']
        ]);

        if ($insertMaterialUsage->rowCount() !== 1) {
            throw new Exception('Material istifadəsi əlavə olunmadı.');
        }

    }


    $insertSauceUsage = $pdo->prepare("
        INSERT INTO sauce_flavour_sauce_usage
            (
                sauce_flavour_id,
                sauce_stock_id,
                qty,
                cost
            )
            VALUES (?,?,?,?)
    ");

    foreach ($saucePlan as $row) {

        $insertSauceUsage->execute([
            $sauceFlavourId,
            $row['id'],
            $row['used'],
            $row['used_cost']
        ]);

        if ($insertSauceUsage->rowCount() !== 1) {
            throw new Exception('Sous istifadəsi əlavə olunmadı.');
        }

    }

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