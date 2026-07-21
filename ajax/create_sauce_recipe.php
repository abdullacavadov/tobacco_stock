<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $raw_name = $_POST['raw_name'] ?? [];
    $percentages = $_POST['percentage'] ?? [];
    $loss = (float) ($_POST['loss'] ?? 0);



    if ($name === '') {
        throw new Exception('Resept adı qeyd edin. İstehsalatda hansı reseptdən istifadə olunacağını bilmək üçün reseptin adı vacibdir.');
    }

    if (!in_array($type, ['premium', 'strong'])) {
        throw new Exception('Yanlış sous növü');
    }

    if (empty($raw_name)) {
        throw new Exception('Ən azı 1 xammal seçilməlidir');
    }

    if ($type === 'strong' && empty($loss)) {
        throw new Exception('İstehsal itkisi qeyd edilməlidir. (Minimum 0, Maksimum 100)');
    }


    $cleanRaws = array_map('trim', $raw_name);

    $duplicates = [];

    foreach ($cleanRaws as $raw) {

        if (in_array($raw, $duplicates)) {
            continue;
        }

        if (count(array_keys($cleanRaws, $raw)) > 1) {
            $duplicates[] = $raw;
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
        FROM sauce_recipes
        WHERE name = ?
    ");

    $check->execute([$name]);

    if ($check->fetchColumn() > 0) {
        throw new Exception('Bu adda resept artıq mövcuddur');
    }

    $stmt = $pdo->prepare("
        INSERT INTO sauce_recipes
        (
            name,
            type,
            loss
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $name,
        $type,
        $loss
    ]);

    $recipeId = $pdo->lastInsertId();

    $totalPercent = 0;



    $insertItem = $pdo->prepare("
        INSERT INTO sauce_recipe_items
        (
            recipe_id,
            raw_material_name,
            percentage
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");

    $cleanRaws = array_map('trim', $raw_name);

    if (count($cleanRaws) !== count(array_unique($cleanRaws))) {
        throw new Exception('Eyni xammal bir reseptdə yalnız bir dəfə istifadə edilə bilər');
    }

    foreach ($raw_name as $i => $raw) {

        $raw = trim($raw);
        $percentage = (float) ($percentages[$i] ?? 0);

        if ($raw === '') {
            throw new Exception('Xammal seçilməyib');
        }

        if ($percentage <= 0) {
            throw new Exception(
                $raw . ' üçün faiz düzgün deyil'
            );
        }

        $totalPercent += $percentage;

        $insertItem->execute([
            $recipeId,
            $raw,
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