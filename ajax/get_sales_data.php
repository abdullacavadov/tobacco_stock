<?php

require_once("../inc/db.php");

header('Content-Type: application/json; charset=utf-8');

try {

    $year = (int) ($_POST['year'] ?? 0);

    if ($year < 2000 || $year > 2100) {
        throw new Exception('Yanlış il.');
    }

    $stmt = $pdo->prepare("
        SELECT 
            MONTH(created_at) AS month,
            COALESCE(SUM(sell_price * qty), 0) AS total
        FROM orders
        WHERE YEAR(created_at) = ?
        GROUP BY MONTH(created_at)
        ORDER BY MONTH(created_at)
    ");

    $stmt->execute([$year]);

    $sales = array_fill(1, 12, 0);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $sales[(int) $row['month']] = (float) $row['total'];
    }

    echo json_encode([
        'success' => true,
        'data' => array_values($sales)
    ]);

} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}