<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $recipeId = (int) ($_POST['recipe_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $loss = (float) ($_POST['loss'] ?? 0);

    $raw_name = $_POST['raw_name'] ?? [];
    $percentages = $_POST['percentage'] ?? [];

    if ($recipeId <= 0) {
        throw new Exception('Resept tapılmadı');
    }

    if ($name === '') {
        throw new Exception('Resept adı qeyd edin.');
    }

    if (!in_array($type, ['premium', 'strong'])) {
        throw new Exception('Yanlış sous növü.');
    }

    if (empty($raw_name)) {
        throw new Exception('Ən azı 1 xammal seçilməlidir.');
    }

    if ($type === 'strong' && ($loss < 0 || $loss > 100)) {
        throw new Exception('İstehsal itkisi 0-100 arasında olmalıdır.');
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM sauce_recipes
        WHERE id=?
    ");

    $stmt->execute([$recipeId]);

    if (!$stmt->fetch()) {
        throw new Exception('Resept tapılmadı.');
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM sauce_recipes
        WHERE name=?
        AND id<>?
    ");

    $stmt->execute([
        $name,
        $recipeId
    ]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Bu adda resept artıq mövcuddur.');
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
            'Eyni xammal bir reseptdə yalnız bir dəfə istifadə edilə bilər. Təkrarlanan xammallar: '
            . implode(', ', $duplicates)
        );

    }

    $totalPercent = 0;

    foreach ($raw_name as $i => $raw) {

        $raw = trim($raw);
        $percentage = (float) ($percentages[$i] ?? 0);

        if ($raw === '') {
            throw new Exception('Xammal seçilməyib.');
        }

        if ($percentage <= 0) {
            throw new Exception($raw . ' üçün faiz düzgün deyil.');
        }

        $totalPercent += $percentage;

    }

    if ($totalPercent > 100) {
        throw new Exception('Faizlərin cəmi 100%-dən çox ola bilməz.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE sauce_recipes
        SET
            name=?,
            type=?,
            loss=?
        WHERE id=?
    ");

    $stmt->execute([
        $name,
        $type,
        $loss,
        $recipeId
    ]);

    $stmt = $pdo->prepare("
        DELETE FROM sauce_recipe_items
        WHERE recipe_id=?
    ");

    $stmt->execute([$recipeId]);

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

    foreach ($raw_name as $i => $raw) {

        $insertItem->execute([
            $recipeId,
            trim($raw),
            (float) $percentages[$i]
        ]);

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