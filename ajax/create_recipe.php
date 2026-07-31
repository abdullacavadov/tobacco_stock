<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $name = trim($_POST['name'] ?? '');
    $sauceType = trim($_POST['sauce_type'] ?? '');
    $materialNames = $_POST['material_name'] ?? [];
    $percentages = $_POST['percentage'] ?? [];



    if ($name === '') {
        throw new Exception('Resept adı boş ola bilməz');
    }

    if (!in_array($sauceType, ['premium', 'strong'])) {
        throw new Exception('Yanlış sous növü');
    }

    if (empty($materialNames)) {
        throw new Exception('Ən azı 1 dad seçilməlidir');
    }


    $cleanMaterials = array_map('trim', $materialNames);

    $duplicates = [];

    foreach ($cleanMaterials as $material) {

        if (in_array($material, $duplicates)) {
            continue;
        }

        if (count(array_keys($cleanMaterials, $material)) > 1) {
            $duplicates[] = $material;
        }
    }

    if (!empty($duplicates)) {
        throw new Exception(
            'Eyni xammal bir reseptdə yalnız bir dəfə istifadə edilə bilər. Təkrarlanan xammallar: ' . implode(', ', $duplicates)
        );
    }






    $pdo->beginTransaction();

    $check = $pdo->prepare("
        SELECT COUNT(*)
        FROM flavour_recipes
        WHERE name = ?
    ");

    $check->execute([$name]);

    if ($check->fetchColumn() > 0) {
        throw new Exception('Bu adda resept artıq mövcuddur');
    }




    $checkMaterial = $pdo->prepare("
    SELECT COUNT(*)
    FROM raw_materials
    WHERE
        name = ?
        AND type IN ('flavour','raw')
");



    $stmt = $pdo->prepare("
        INSERT INTO flavour_recipes
        (
            name,
            sauce_type
        )
        VALUES
        (
            ?,
            ?
        )
    ");

    $stmt->execute([
        $name,
        $sauceType
    ]);

    $recipeId = $pdo->lastInsertId();

    $totalPercent = 0;



    $insertItem = $pdo->prepare("
        INSERT INTO flavour_recipe_items
        (
            recipe_id,
            flavour_name,
            percentage
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");

    $cleanMaterials = array_map('trim', $materialNames);

    if (count($cleanMaterials) !== count(array_unique($cleanMaterials))) {
        throw new Exception('Eyni xammal bir reseptdə yalnız bir dəfə istifadə edilə bilər');
    }

    foreach ($materialNames as $i => $materialName) {

        $materialName = trim($materialName);
        $percentage = (float) ($percentages[$i] ?? 0);

        if ($materialName === '') {
            throw new Exception('Xammal seçilməyib');
        }

        if ($percentage <= 0) {
            throw new Exception(
                $materialName . ' üçün faiz düzgün deyil'
            );
        }


        $checkMaterial->execute([$materialName]);

        if (!$checkMaterial->fetchColumn()) {
            throw new Exception($materialName . ' tapılmadı');
        }


        $totalPercent += $percentage;

        $insertItem->execute([
            $recipeId,
            $materialName,
            $percentage
        ]);
    }

    if ($totalPercent > 100) {
        throw new Exception(
            'Faizlərin cəmi 100%-dən çox ola bilməz'
        );
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