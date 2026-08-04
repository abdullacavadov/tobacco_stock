<?php
require '../inc/db.php';

$productId = (int) ($_POST['pid'] ?? 0);

if ($productId <= 0) {
    throw new Exception('Yanlış məhsul ID-si.');
}

$stock = (int) ($_POST['stock'] ?? 0);
$production_date = $_POST['production_date'] ?? '';


if ($stock <= 0) {
    throw new Exception('Məhsul sayı yanlışdır.');
}

if ($production_date > date('Y-m-d')) {
    throw new Exception('İstehsal tarixi gələcəkdə ola bilməz.');
}

$pdo->beginTransaction();


try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            weight,
            type,
            is_active
        FROM products
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$productId]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Məhsul tapılmadı.');
    }

    if ((int) $product['is_active'] === 0) {
        throw new Exception('Bu məhsul silinib.');
    }


    $needSauce = round(
        $stock * $product['weight'],
        4
    );

    $name = $product['name'];
    $type = $product['type'];


    /*
|--------------------------------------------------------------------------
| İSTİFADƏ OLUNAN MATERİALLARI GÖTÜR
|--------------------------------------------------------------------------
*/

    $stmt = $pdo->prepare("
    SELECT
        pmu.material_id,
        pmu.material_type,
        rm.name
    FROM product_material_usage pmu
    INNER JOIN raw_materials rm
        ON rm.id = pmu.material_id
    WHERE pmu.product_id = ?
");

    $stmt->execute([$productId]);

    $usedMaterials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $package = '';
    $label = '';
    $cover = '';

    foreach ($usedMaterials as $row) {

        switch ($row['material_type']) {

            case 'package':
                $package = $row['name'];
                break;

            case 'label':
                $label = $row['name'];
                break;

            case 'cover':
                $cover = $row['name'];
                break;

        }

    }



    /*
|--------------------------------------------------------------------------
| KÖHNƏ MATERİALLARI GERİ QAYTAR
|--------------------------------------------------------------------------
*/

    $stmt = $pdo->prepare("
    SELECT
        material_id,
        qty,
        cost
    FROM product_material_usage
    WHERE product_id = ?
");

    $stmt->execute([$productId]);

    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateMaterial = $pdo->prepare("
    UPDATE raw_materials
    SET
        stock = ROUND(stock + ?, 4),
        price = ROUND(price + ?, 4)
    WHERE id = ?
");

    foreach ($materials as $row) {

        $updateMaterial->execute([
            $row['qty'],
            $row['cost'],
            $row['material_id']
        ]);

    }


    /*
|--------------------------------------------------------------------------
| KÖHNƏ AROMALI SOUSU GERİ QAYTAR
|--------------------------------------------------------------------------
*/

    $stmt = $pdo->prepare("
    SELECT
        sauce_id,
        qty,
        cost
    FROM product_flavour_sauce_usage
    WHERE product_id = ?
");

    $stmt->execute([$productId]);

    $sauces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateSauce = $pdo->prepare("
    UPDATE sauce_with_flavour
    SET
        qty = ROUND(qty + ?, 4),
        cost = ROUND(cost + ?, 4)
    WHERE id = ?
");

    foreach ($sauces as $row) {

        $updateSauce->execute([
            $row['qty'],
            $row['cost'],
            $row['sauce_id']
        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | KÖHNƏ USAGE QEYDLƏRİNİ SİL
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
    DELETE FROM product_material_usage
    WHERE product_id = ?
");

    $stmt->execute([$productId]);

    $stmt = $pdo->prepare("
    DELETE FROM product_flavour_sauce_usage
    WHERE product_id = ?
");

    $stmt->execute([$productId]);




    $cost = 0;

    $packagePlan = [];
    $labelPlan = [];
    $coverPlan = [];
    $saucePlan = [];

    /*
    |--------------------------------------------------------------------------
    | PACKAGE FIFO
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
    SELECT
        id,
        stock,
        price
    FROM raw_materials
    WHERE
        name = ?
        AND type = 'package'
        AND stock > 0
    ORDER BY in_stock ASC, id ASC
    FOR UPDATE
");

    $stmt->execute([
        $package
    ]);

    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$packages) {
        throw new Exception('Qab stokda yoxdur.');
    }

    $totalPackageStock = array_sum(
        array_column($packages, 'stock')
    );

    if ($totalPackageStock < $stock) {

        throw new Exception(
            'Qab çatmır. Lazımdır: ' .
            $stock .
            ' ədəd, Mövcuddur: ' .
            $totalPackageStock .
            ' ədəd'
        );

    }

    $remainingPackage = $stock;

    foreach ($packages as $batch) {

        if ($remainingPackage <= 0) {
            break;
        }

        $used = min(
            $batch['stock'],
            $remainingPackage
        );

        //Vahidin qiyməti
        $unitPrice = $batch['price'] / $batch['stock'];

        //istifadə olunanın qiyməti
        $usedCost = round(
            $unitPrice * $used,
            4
        );

        $packagePlan[] = [
            'id' => $batch['id'],
            'used' => $used,
            'used_cost' => $usedCost
        ];

        $cost += $usedCost;

        $remainingPackage -= $used;

    }


    /*
|--------------------------------------------------------------------------
| LABEL FIFO
|--------------------------------------------------------------------------
*/

    if ($label !== '') {

        $stmt = $pdo->prepare("
            SELECT
                id,
                stock,
                price
            FROM raw_materials
            WHERE
                name = ?
                AND type = 'label'
                AND stock > 0
            ORDER BY in_stock ASC, id ASC
            FOR UPDATE
        ");

        $stmt->execute([
            $label
        ]);

        $labels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$labels) {
            throw new Exception('Etiket stokda yoxdur');
        }

        $totalLabelStock = array_sum(
            array_column($labels, 'stock')
        );

        if ($totalLabelStock < $stock) {

            throw new Exception(
                'Etiket çatmır. Lazımdır: ' .
                $stock .
                ' ədəd, Mövcuddur: ' .
                $totalLabelStock .
                ' ədəd'
            );

        }

        $remainingLabel = $stock;

        foreach ($labels as $batch) {

            if ($remainingLabel <= 0) {
                break;
            }

            $used = min(
                $batch['stock'],
                $remainingLabel
            );

            //Vahidin qiyməti
            $unitPrice = $batch['price'] / $batch['stock'];

            //istifadə olunanın qiyməti
            $usedCost = round(
                $unitPrice * $used,
                4
            );

            $labelPlan[] = [
                'id' => $batch['id'],
                'used' => $used,
                'used_cost' => $usedCost
            ];

            $cost += $usedCost;

            $remainingLabel -= $used;

        }

    }



    /*
    |--------------------------------------------------------------------------
    | COVER FIFO
    |--------------------------------------------------------------------------
    */

    if ($cover !== '') {

        $stmt = $pdo->prepare("
            SELECT
                id,
                stock,
                price
            FROM raw_materials
            WHERE
                name = ?
                AND type = 'cover'
                AND stock > 0
            ORDER BY in_stock ASC, id ASC
            FOR UPDATE
        ");

        $stmt->execute([
            $cover
        ]);

        $covers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$covers) {
            throw new Exception('Paket stokda yoxdur');
        }

        $totalCoverStock = array_sum(
            array_column($covers, 'stock')
        );

        if ($totalCoverStock < $stock) {

            throw new Exception(
                'Paket çatmır. Lazımdır: ' .
                $stock .
                ' ədəd, Mövcuddur: ' .
                $totalCoverStock .
                ' ədəd'
            );

        }

        $remainingCover = $stock;

        foreach ($covers as $batch) {

            if ($remainingCover <= 0) {
                break;
            }

            $used = min(
                $batch['stock'],
                $remainingCover
            );

            //Vahidin qiyməti
            $unitPrice = $batch['price'] / $batch['stock'];

            //istifadə olunanın qiyməti
            $usedCost = round(
                $unitPrice * $used,
                4
            );

            $coverPlan[] = [
                'id' => $batch['id'],
                'used' => $used,
                'used_cost' => $usedCost
            ];

            $cost += $usedCost;

            $remainingCover -= $used;

        }

    }



    /*
    |--------------------------------------------------------------------------
    | SAUCE WITH FLAVOUR FIFO
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            qty,
            cost
        FROM sauce_with_flavour
        WHERE
            flavour_name = ?
            AND sauce_type = ?
            AND qty > 0
        ORDER BY created_at ASC, id ASC
        FOR UPDATE
    ");

    $stmt->execute([
        $name,
        $type
    ]);

    $sauces = $stmt->fetchAll(PDO::FETCH_ASSOC);




    if (!$sauces) {
        throw new Exception('Seçilən sous stokda yoxdur');
    }

    $totalSauce = array_sum(
        array_column($sauces, 'qty')
    );

    if ($totalSauce < $needSauce) {

        throw new Exception(
            'Sous çatmır. Lazımdır: ' .
            number_format($needSauce, 3) .
            ' kq, Mövcuddur: ' .
            number_format($totalSauce, 3) .
            ' kq'
        );

    }

    $remainingSauce = $needSauce;

    foreach ($sauces as $batch) {

        if ($remainingSauce <= 0) {
            break;
        }

        $used = min(
            $batch['qty'],
            $remainingSauce
        );

        //Vahidin qiyməti
        $unitPrice = $batch['cost'] / $batch['qty'];

        //istifadə olunanın qiyməti
        $usedCost = round(
            $unitPrice * $used,
            4
        );

        $saucePlan[] = [
            'id' => $batch['id'],
            'used_qty' => $used,
            'used_cost' => $usedCost
        ];

        $cost += $usedCost;

        $remainingSauce -= $used;

    }


    $updateMaterial = $pdo->prepare("
    UPDATE raw_materials
    SET
        stock = ROUND(stock - ?, 4),
        price = ROUND(price - ?, 4)
    WHERE id = ?
");

    foreach ($packagePlan as $row) {

        $updateMaterial->execute([
            $row['used'],
            $row['used_cost'],
            $row['id']
        ]);

    }


    if ($label !== '') {

        foreach ($labelPlan as $row) {

            $updateMaterial->execute([
                $row['used'],
                $row['used_cost'],
                $row['id']
            ]);

        }

    }

    if ($cover !== '') {

        foreach ($coverPlan as $row) {
            $updateMaterial->execute([
                $row['used'],
                $row['used_cost'],
                $row['id']
            ]);

        }

    }


    $updateSauce = $pdo->prepare("
        UPDATE sauce_with_flavour
        SET
            qty = ROUND(qty - ?, 4),
            cost = ROUND(cost - ?, 4)
        WHERE id = ?
    ");

    foreach ($saucePlan as $row) {

        $updateSauce->execute([
            $row['used_qty'],
            $row['used_cost'],
            $row['id']
        ]);

    }

    $productPrice = round(
        $cost / $stock,
        4
    );



    $stmt = $pdo->prepare("
    UPDATE products
    SET
        stock = ?,
        price = ?,
        production_date = ?
    WHERE id = ?
");

    $stmt->execute([
        $stock,
        $productPrice,
        $production_date,
        $productId
    ]);



    $insertMaterialUsage = $pdo->prepare("
    INSERT INTO product_material_usage
        (
            product_id,
            material_id,
            material_type,
            qty,
            cost
        )
        VALUES
        (
            ?, ?, ?, ?, ?
        )
    ");


    foreach ($packagePlan as $row) {

        $insertMaterialUsage->execute([
            $productId,
            $row['id'],
            'package',
            $row['used'],
            $row['used_cost']
        ]);

    }


    foreach ($labelPlan as $row) {

        $insertMaterialUsage->execute([
            $productId,
            $row['id'],
            'label',
            $row['used'],
            $row['used_cost']
        ]);

    }


    foreach ($coverPlan as $row) {

        $insertMaterialUsage->execute([
            $productId,
            $row['id'],
            'cover',
            $row['used'],
            $row['used_cost']
        ]);

    }


    $insertSauceUsage = $pdo->prepare("
        INSERT INTO product_flavour_sauce_usage
        (
            product_id,
            sauce_id,
            qty,
            cost
        )
        VALUES
        (
            ?, ?, ?, ?
        )
    ");

    foreach ($saucePlan as $row) {

        $insertSauceUsage->execute([
            $productId,
            $row['id'],
            $row['used_qty'],
            $row['used_cost']
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

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}