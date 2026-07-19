<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $type = trim($_POST['type'] ?? '');
    $kg = (float) ($_POST['stock'] ?? 0);

    if ($kg <= 0) {
        throw new Exception('Yanlış miqdar');
    }

    $recipes = [

        'premium' => [
            'Tütün' => 0.1281,
            'Qliserin' => 0.6151,
            'Fruktoza' => 0.2563,
            'Rəng' => 0.0005
        ],

        'strong' => [
            'Tütün' => 0.1538,
            'Qliserin' => 0.6154,
            'Fruktoza' => 0.1538,
            'Bəhməz' => 0.0769
        ]

    ];

    if (!isset($recipes[$type])) {
        throw new Exception('Yanlış sous növü');
    }

    $recipe = $recipes[$type];

    // Xammal hesablanacaq baza miqdarı
    $recipeKg = $kg;

    if ($type === 'strong') {
        // 15% istehsal itkisi
        $recipeKg = $kg / 0.85;
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

            $cost = $used * $stockRow['price'];

            $rows[] = [
                'name' => $materialName,
                'in_stock' => date('d.m.Y', strtotime($stockRow['in_stock'])),
                'used' => round($used, 3) . ' kq',
                'price' => round($stockRow['price'], 2) . ' AZN',
                'total_price' => round($cost, 2) . ' AZN',
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
        'loss' => ($type === 'strong') ? 15 : 0,
        'rows' => $rows,
        'total_cost' => round($totalCost, 2)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}