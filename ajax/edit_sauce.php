<?php

require_once("../inc/db.php");

header('Content-Type: application/json; charset=utf-8');

$sid = (int) ($_POST['sid'] ?? 0);

if ($sid <= 0) {
    throw new Exception('Sous tapılmadı.');
}

$kg = (float) ($_POST['stock'] ?? 0);
$type = $_POST['type'] ?? '';
$materialPlan = [];
$cost = 0;

if (!isset($kg) || $kg === '') {
    throw new Exception('Zəhmət olmasa, həcm daxil edin.');
}

if ($kg <= 0) {
    throw new Exception('Miqdar düzgün deyil.');
}

if (!isset($_POST['type']) || $_POST['type'] === '') {
    throw new Exception('Zəhmət olmasa, növü seçin.');
}

if (!in_array($type, ['premium', 'strong'])) {
    throw new Exception('Sous növü düzgün deyil.');
}




$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            stock,
            type,
            price
        FROM sauce_stock
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$sid]);

    $sauce = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sauce) {
        throw new Exception('Sous tapılmadı.');
    }

    if ($sauce['type'] !== $type) {
        throw new Exception('Sous növünü dəyişmək olmaz.');
    }

    /* MATERIALS */
    $stmt = $pdo->prepare("
        SELECT
            material_id,
            cost,
            qty
        FROM sauce_material_usage
        WHERE sauce_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$sid]);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        UPDATE raw_materials
        SET
            stock = ROUND(stock + ?, 4),
            price = ROUND(price + ?, 4)
        WHERE id = ?
    ");

    foreach ($materials as $row) {

        $stmt->execute([
            $row['qty'],
            $row['cost'],
            $row['material_id']
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new Exception('Material geri qaytarılmadı.');
        }
    }

    $stmt = $pdo->prepare("
        DELETE FROM sauce_material_usage
        WHERE sauce_id = ?
    ");
    $stmt->execute([$sid]);

    $recipe_id = (int) ($_POST['recipe_id'] ?? 0);

    if ($recipe_id <= 0) {
        throw new Exception('Resept seçilməyib.');
    }

    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.name,
            r.type,
            r.loss,
            i.raw_material_name,
            i.percentage
        FROM sauce_recipes r
        INNER JOIN sauce_recipe_items i
            ON i.recipe_id = r.id
        WHERE r.id = ?
    ");

    $stmt->execute([$recipe_id]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        throw new Exception('Resept tapılmadı');
    }

    if ($rows[0]['type'] !== $type) {
        throw new Exception('Bu resept seçilən sous növünə aid deyil');
    }

    $type = $rows[0]['type'];
    $loss = $rows[0]['loss'];

    $recipe = [];

    foreach ($rows as $row) {
        $recipe[$row['raw_material_name']] = $row['percentage'] / 100;
    }

    $totalPercentage = array_sum($recipe);

    if (round($totalPercentage, 4) != 1) {
        throw new Exception(
            'Resept faizləri 100% olmalıdır. Hazırda: ' .
            round($totalPercentage * 100, 2) . '%'
        );
    }

    if ($loss >= 100) {
        throw new Exception('İtki faizi düzgün deyil.');
    }

    if ($loss > 0) {
        // $loss % istehsal itkisi varsa
        $recipeKg = $kg / (1 - $loss / 100);
    } else {
        // Xammal hesablanacaq baza miqdarı
        $recipeKg = $kg;
    }

    $cost = 0;
    $materialPlan = [];



    foreach ($recipe as $materialName => $ratio) {

        $required = round($recipeKg * $ratio, 4);

        $stmt = $pdo->prepare("
            SELECT
                id,
                stock,
                price
            FROM raw_materials
            WHERE name = ?
            AND stock > 0
            ORDER BY in_stock ASC, id ASC
            FOR UPDATE
        ");

        $stmt->execute([$materialName]);

        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$stocks) {
            throw new Exception($materialName . ' stokda yoxdur');
        }

        $totalStock = array_sum(array_column($stocks, 'stock'));

        if ($totalStock < $required) {
            throw new Exception(
                $materialName .
                ' çatmır. Lazımdır: ' .
                $required .
                ' kq, Mövcuddur: ' .
                $totalStock .
                ' kq'
            );
        }

        $remaining = $required;

        foreach ($stocks as $stockRow) {

            if ($remaining <= 0) {
                break;
            }

            // Bu partiyadan nə qədər istifadə olunacaq
            $used = min($stockRow['stock'], $remaining);

            // 1 vahidin qiyməti
            $unitPrice = $stockRow['price'] / $stockRow['stock'];

            // İstifadə olunan hissənin dəyəri
            $usedPrice = round($unitPrice * $used, 4);

            // Ümumi maya dəyərinə əlavə et
            $cost += $usedPrice;

            // Sonradan bərpa/edit üçün yadda saxla
            $materialPlan[] = [
                'id' => $stockRow['id'],
                'used' => $used,
                'used_cost' => $usedPrice
            ];

            // Yeni qalıqlar
            $newStock = round($stockRow['stock'] - $used, 4);
            $newPrice = round($stockRow['price'] - $usedPrice, 4);

            // Float xətalarının qarşısını al
            if ($newStock <= 0.0001) {
                $newStock = 0;
                $newPrice = 0;
            }

            $update = $pdo->prepare("
                UPDATE raw_materials
                SET
                    stock = ?,
                    price = ?
                WHERE id = ?
            ");

            $update->execute([
                $newStock,
                $newPrice,
                $stockRow['id']
            ]);

            $remaining -= $used;
        }
    }




    $cost = round($cost, 4);







    $statement = $pdo->prepare("
        UPDATE sauce_stock
        SET
            stock = ?,
            type = ?,
            price = ?
        WHERE id = ?
    ");

    $statement->execute([
        $kg,
        $type,
        $cost,
        $sid
    ]);


    if ($statement->rowCount() !== 1) {
        throw new Exception('Sous yenilənmədi.');
    }


    $insertMaterialUsage = $pdo->prepare("
    INSERT INTO sauce_material_usage
    (
        sauce_id,
        material_id,
        qty,
        cost
    )
    VALUES (?, ?, ?, ?)
");


    foreach ($materialPlan as $row) {

        $insertMaterialUsage->execute([
            $sid,
            $row['id'],
            $row['used'],
            $row['used_cost']
        ]);

    }

    $pdo->commit();

    echo json_encode([
        'success' => true
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}