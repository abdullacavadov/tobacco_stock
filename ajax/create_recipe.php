<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $name = trim($_POST['name'] ?? '');
    $sauceType = trim($_POST['sauce_type'] ?? '');
    $flavourNames = $_POST['flavour_name'] ?? [];
    $percentages = $_POST['percentage'] ?? [];



    if ($name === '') {
        throw new Exception('Resept adı boş ola bilməz');
    }

    if (!in_array($sauceType, ['premium', 'strong'])) {
        throw new Exception('Yanlış sous növü');
    }

    if (empty($flavourNames)) {
        throw new Exception('Ən azı 1 dad seçilməlidir');
    }


    $cleanFlavours = array_map('trim', $flavourNames);

    $duplicates = [];

    foreach ($cleanFlavours as $flavour) {

        if (in_array($flavour, $duplicates)) {
            continue;
        }

        if (count(array_keys($cleanFlavours, $flavour)) > 1) {
            $duplicates[] = $flavour;
        }
    }

    if (!empty($duplicates)) {
        throw new Exception(
            'Eyni dad bir reseptdə yalnız bir dəfə istifadə edilə bilər. Təkrarlanan dadlar: ' . implode(', ', $duplicates)
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

    $cleanFlavours = array_map('trim', $flavourNames);

    if (count($cleanFlavours) !== count(array_unique($cleanFlavours))) {
        throw new Exception('Eyni dad bir reseptdə yalnız bir dəfə istifadə edilə bilər');
    }

    foreach ($flavourNames as $i => $flavourName) {

        $flavourName = trim($flavourName);
        $percentage = (float) ($percentages[$i] ?? 0);

        if ($flavourName === '') {
            throw new Exception('Dad seçilməyib');
        }

        if ($percentage <= 0) {
            throw new Exception(
                $flavourName . ' üçün faiz düzgün deyil'
            );
        }

        $totalPercent += $percentage;

        $insertItem->execute([
            $recipeId,
            $flavourName,
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