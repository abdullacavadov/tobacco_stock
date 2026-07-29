<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $recipeId = (int) ($_POST['recipe_id'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $kg = (float) ($_POST['stock'] ?? 0);

    if ($kg <= 0) {
        throw new Exception('Yanlış miqdar');
    }

    if (!in_array($type, ['premium', 'strong'])) {
        throw new Exception('Yanlış sous növü');
    }


    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
    SELECT
        sr.id,
        sr.name,
        sr.type,
        sr.loss,
        sri.raw_material_name,
        sri.percentage
    FROM sauce_recipes sr
    INNER JOIN sauce_recipe_items sri
        ON sri.recipe_id = sr.id
    WHERE sr.id = ?
");

    $stmt->execute([$recipeId]);

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



    if ($loss > 0) {
        // $loss % istehsal itkisi varsa
        $recipeKg = $kg / (1 - $loss / 100);
    } else {
        // Xammal hesablanacaq baza miqdarı
        $recipeKg = $kg;
    }



    $rows = [];
    $totalCost = 0;

    foreach ($recipe as $materialName => $ratio) {

        $required = round($recipeKg * $ratio, 4);

        $stmt = $pdo->prepare("
            SELECT
                id,
                stock,
                price,
                in_stock
            FROM raw_materials
            WHERE name = ?
              AND stock > 0
            ORDER BY in_stock ASC, id ASC
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
                number_format($required, 2) .
                ' kq, mövcuddur: ' .
                number_format($totalStock, 2) .
                ' kq'
            );
        }

        $remaining = $required;

        foreach ($stocks as $stockRow) {

            if ($remaining <= 0) {
                break;
            }

            $used = min(
                $stockRow['stock'],
                $remaining
            );

            // 1 kq qiyməti
            $unitPrice = $stockRow['price'] / $stockRow['stock'];

            // İstifadə olunan hissənin qiyməti
            $cost = $used * $unitPrice;

            $rows[] = [
                'name' => $materialName,
                'in_stock' => date('d.m.Y', strtotime($stockRow['in_stock'])),
                'used' => round($used, 3) . ' kq',
                'price' => round($unitPrice, 3) . ' AZN',
                'total_price' => round($cost, 3) . ' AZN',
                'remaining' => round(
                    $stockRow['stock'] - $used,
                    3
                ) . ' kq'
            ];

            $totalCost += $cost;

            $remaining -= $used;
        }
    }

    echo json_encode([
        'success' => true,
        'recipe_kg' => round($recipeKg, 3),
        'finished_kg' => round($kg, 3),
        'loss' => $loss,
        'rows' => $rows,
        'total_cost' => round($totalCost, 3)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}