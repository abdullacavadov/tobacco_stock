<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['fid'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sous ID-si göndərilməyib'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $fid = (int) $_POST['fid'];
    $sauce_type = trim($_POST['sauce_type'] ?? '');
    $qty = (float) ($_POST['qty'] ?? 0);
    $recipe_id = (int) ($_POST['recipe_id'] ?? 0);
    $cost = (float) ($_POST['cost'] ?? 0);


    if (!in_array($sauce_type, ['premium', 'strong'])) {
        throw new Exception('Yanlış sous növü');
    }

    if ($qty <= 0) {
        throw new Exception('Yanlış miqdar');
    }

    if ($recipe_id <= 0) {
        throw new Exception('Resept seçilməyib');
    }

    if ($cost <= 0) {
        throw new Exception('Yanlış qiymət');
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
    ];

    if ($recipe['sauce_type'] !== $sauce_type) {
        throw new Exception('Bu resept seçilən sous növünə aid deyil');
    }


    $pdo->beginTransaction();


    $stmt = $pdo->prepare("
        UPDATE sauce_with_flavour
        SET
            sauce_type = ?,
            flavour_name = ?,
            qty = ?,
            cost = ?
        WHERE id = ?
        ");

    $stmt->execute([
        $sauce_type,
        $recipe['name'],
        $qty,
        $cost,
        $fid
    ]);

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