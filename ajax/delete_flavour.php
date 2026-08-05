<?php

require '../inc/db.php';


$fid = (int) ($_GET['fid'] ?? 0);

if ($fid <= 0) {
    throw new Exception('Yanlış dadlandırımış sous ID-si.');
}

$pdo->beginTransaction();


try {

    $stmt = $pdo->prepare("
        SELECT id
        FROM sauce_with_flavour
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$fid]);

    $fl_sauce = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fl_sauce) {
        throw new Exception('Sous tapılmadı.');
    }



    /* MATERIALS */
    $stmt = $pdo->prepare("
        SELECT
            material_id,
            cost,
            qty
        FROM sauce_flavour_material_usage
        WHERE sauce_flavour_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$fid]);
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


    /*SAUCES */
    $stmt = $pdo->prepare("
        SELECT
            sauce_stock_id,
            qty,
            cost
        FROM sauce_flavour_sauce_usage
        WHERE sauce_flavour_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$fid]);
    $sauces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        UPDATE sauce_stock
        SET
            stock = ROUND(stock + ?, 4),
            price = ROUND(price + ?, 4)
        WHERE id = ?
    ");

    foreach ($sauces as $row) {

        $stmt->execute([
            $row['qty'],
            $row['cost'],
            $row['sauce_stock_id']
        ]);

    }

    if ($stmt->rowCount() !== 1) {
        throw new Exception('Sous geri qaytarılmadı.');
    }


    $stmt = $pdo->prepare("
        DELETE FROM sauce_flavour_sauce_usage
        WHERE sauce_flavour_id = ?
    ");
    $stmt->execute([$fid]);

    $stmt = $pdo->prepare("
        DELETE FROM sauce_flavour_material_usage
        WHERE sauce_flavour_id = ?
    ");
    $stmt->execute([$fid]);



    $stmt = $pdo->prepare("DELETE FROM sauce_with_flavour WHERE id = ?");
    $stmt->execute([$fid]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception('Məhsul silinmədi.');
    }




    $pdo->commit();

    header("Location: ../flavours.php");
    exit();
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw new Exception($e->getMessage());
}


