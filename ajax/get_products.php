<?php

require '../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $kind = trim($_GET['kind'] ?? '');

    $allowedKinds = ['raw', 'sauce', 'flavour', 'product'];

    if (!in_array($kind, $allowedKinds, true)) {
        throw new Exception('Yanlış məhsul növü.');
    }

    $result = [];

    switch ($kind) {

        case 'raw':

            $stmt = $pdo->query("
                SELECT *
                FROM raw_materials
                WHERE stock > 0
                ORDER BY name ASC
            ");

            foreach ($stmt as $row) {

                $type = match ($row['type']) {
                    'raw' => 'Xammal',
                    'flavour' => 'Aroma',
                    'package' => 'Qab',
                    'label' => 'Etiket',
                    'cover' => 'Paket',
                    default => ''
                };

                $result[] = [
                    'id' => (int)$row['id'],
                    'text' =>
                        $type . ' - ' .
                        $row['name'] .
                        ' (' . $row['supplier'] . ')' .
                        ', 1' . $row['unit'] .
                        ' mayası - ' .
                        number_format($row['price'] / $row['stock'], 3) .
                        '₼ (stok: ' .
                        number_format($row['stock'], 2) .
                        ' ' . $row['unit'] .
                        ', part: ' .
                        date('d.m.Y', strtotime($row['in_stock'])) .
                        ')'
                ];
            }

            break;

        case 'sauce':

            $stmt = $pdo->query("
                SELECT *
                FROM sauce_stock
                WHERE stock > 0
                ORDER BY created_at ASC
            ");

            foreach ($stmt as $row) {

                $type = $row['type'] == 'premium'
                    ? 'Premium'
                    : 'Strong';

                $result[] = [
                    'id' => (int)$row['id'],
                    'text' =>
                        $type .
                        ' (1kq mayası - ' .
                        number_format($row['price'] / $row['stock'], 3) .
                        '₼, stok: ' .
                        number_format($row['stock'], 2) .
                        'kq | ' .
                        date('d.m.Y', strtotime($row['created_at'])) .
                        ')'
                ];
            }

            break;

        case 'flavour':

            $stmt = $pdo->query("
                SELECT *
                FROM sauce_with_flavour
                WHERE qty > 0
                ORDER BY created_at ASC
            ");

            foreach ($stmt as $row) {

                $type = $row['sauce_type'] == 'premium'
                    ? 'Premium'
                    : 'Strong';

                $result[] = [
                    'id' => (int)$row['id'],
                    'text' =>
                        $type . ' ' .
                        $row['flavour_name'] .
                        ' (1kq mayası - ' .
                        number_format($row['cost'] / $row['qty'], 3) .
                        '₼, stok: ' .
                        number_format($row['qty'], 2) .
                        'kq | ' .
                        date('d.m.Y', strtotime($row['created_at'])) .
                        ')'
                ];
            }

            break;

        case 'product':

            $stmt = $pdo->query("
                SELECT *
                FROM products
                WHERE stock > 0
                ORDER BY production_date DESC
            ");

            foreach ($stmt as $row) {

                $type = $row['type'] == 'premium'
                    ? 'PREMIUM'
                    : 'STRONG';

                $result[] = [
                    'id' => (int)$row['id'],
                    'text' =>
                        $type . ' ' .
                        $row['name'] .
                        ' - ' .
                        number_format($row['weight'] * 1000, 0) .
                        'qr, Maya dəyəri - ' .
                        number_format($row['price'], 2) .
                        '₼ (stok: ' .
                        (int)$row['stock'] .
                        ' əd, istehsal: ' .
                        date('d.m.Y', strtotime($row['production_date'])) .
                        ')'
                ];
            }

            break;
    }

    echo json_encode([
        'success' => true,
        'products' => $result
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}