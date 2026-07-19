<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $type = trim($_POST['type'] ?? '');
    $kg = (float) ($_POST['stock'] ?? 0);

    if ($kg <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Yanlış miqdar'
        ], JSON_UNESCAPED_UNICODE);
        exit();
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
        echo json_encode([
            'success' => false,
            'message' => 'Yanlış sous növü'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $recipe = $recipes[$type];

    $recipe = $recipes[$type];

    // Material hesablanacaq baza miqdarı
    $recipeKg = $kg;

    if ($type === 'strong') {
        // Strong sousunda 15% itki var
        $recipeKg = $kg / 0.85;
    }

    $cost = 0;


    $pdo->beginTransaction();

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
            echo json_encode([
                'success' => false,
                'message' => $materialName . ' stokda yoxdur'
            ], JSON_UNESCAPED_UNICODE);
            exit();
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
            exit();
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

            $cost += $used * $stockRow['price'];

            $update = $pdo->prepare("
                UPDATE raw_materials
                SET stock = stock - ?
                WHERE id = ?
            ");

            $update->execute([
                $used,
                $stockRow['id']
            ]);

            $remaining -= $used;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO sauce_stock
        (
            type,
            stock,
            price
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $type,
        $kg,
        round($cost, 2)
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}