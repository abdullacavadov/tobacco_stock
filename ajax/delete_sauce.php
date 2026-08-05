<?php

require '../inc/db.php';


$sid = (int) ($_GET['sid'] ?? 0);

if ($sid <= 0) {
    throw new Exception('Yanlış sous ID-si.');
}

$pdo->beginTransaction();

try {

    $stmt = $pdo->prepare("
        SELECT id
        FROM sauce_stock
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$sid]);

    $sauce = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sauce) {
        throw new Exception('Sous tapılmadı.');
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

    }

    if ($stmt->rowCount() !== 1) {
        throw new Exception('Material geri qaytarılmadı.');
    }


    $stmt = $pdo->prepare("
        DELETE FROM sauce_material_usage
        WHERE sauce_id = ?
    ");
    $stmt->execute([$sid]);


    $stmt = $pdo->prepare("DELETE FROM sauce_stock WHERE id = ?");
    $stmt->execute([$sid]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception('Sous silinmədi.');
    }

    $pdo->commit();

    header("Location: ../sauces.php");
    exit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw new Exception($e->getMessage());
}

