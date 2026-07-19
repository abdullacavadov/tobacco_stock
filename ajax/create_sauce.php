<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $recipeId = (int) ($_POST['recipe_id'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $kg = (float) ($_POST['stock'] ?? 0);

    if ($kg <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Yanlış miqdar'
        ], JSON_UNESCAPED_UNICODE);
        exit();
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

    $recipe = [];

    foreach ($rows as $row) {
        $recipe[$row['raw_material_name']] = $row['percentage'] / 100;
    }

    // Material hesablanacaq baza miqdarı
    $recipeKg = $kg;

    if ($type === 'strong') {
        // Strong sousunda 15% itki var
        $recipeKg = $kg / 0.85;
    }

    $cost = 0;




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