<?php require_once('inc/check_session.php'); ?>
<?php

require 'inc/db.php';

$orderNo = trim($_GET['oid'] ?? '');

if ($orderNo === '') {
    die('Sifariş tapılmadı.');
}

$stmt = $pdo->prepare("
    SELECT *,
    rm.id AS raw_id,
    rm.edv AS edv
    FROM orders o
    LEFT JOIN raw_materials rm ON o.item_id = rm.id
    WHERE order_no = ?
    ORDER BY o.id ASC
");

$stmt->execute([$orderNo]);

$orders = $stmt->fetchAll();

if (!$orders) {
    die('Sifariş tapılmadı.');
}

$orderInfo = $orders[0];
?>

<!DOCTYPE html>
<html lang="az">

<head>
    <?php require_once('inc/head.php'); ?>
</head>

<body class="sb-nav-fixed">

    <?php require_once('inc/navbar.php'); ?>

    <div id="layoutSidenav">

        <?php require_once('inc/sidebar.php'); ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Satışlar</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Satışlar</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Satış detalları
                            </span>


                            <a class="btn btn-success" href="orders.php">
                                <i class="fas fa-list"></i>
                                Satışlar
                            </a>
                        </div>
                        <div class="card-body">

                            <div class="mb-3">
                                <strong><i class="fas fa-file-alt"></i> Sifariş №:</strong>
                                <?= $orderInfo['order_no'] ?>
                                <br>
                                <strong><i class="fas fa-user"></i> Müştəri:</strong>
                                <?= htmlspecialchars($orderInfo['customer']) ?>
                                <br>
                                <strong><i class="fas fa-calendar"></i> Tarix:</strong>
                                <?= date('d.m.Y H:i', strtotime($orderInfo['created_at'])) ?>
                            </div>



                            <table id="datatablesSimple" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Məhsul</th>
                                        <th>Miqdar</th>
                                        <th>Maya</th>
                                        <th>Satış (vahid)</th>
                                        <th>Yekun</th>
                                        <th>Qazanc</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php $totalCost = 0;
                                    $totalRevenue = 0;
                                    $totalProfit = 0;
                                    $totalEdv = 0;
                                    ?>

                                    <?php foreach ($orders as $order): ?>

                                        <?php

                                        $cost = $order['cost'] * $order['qty'];
                                        $revenue = $order['sell_price'] * $order['qty'];
                                        $profit = $revenue - $cost;

                                        $totalCost += $cost;
                                        $totalRevenue += $revenue;
                                        $totalProfit += $profit;

                                        // ƏDV faizi// Yalnız ƏDV 18% olduqda hesabla
                                        if ((float) $order['edv'] === 18.0) {
                                            $totalEdv += $cost * 18 / 100;
                                        }
                                        ?>

                                        <?php
                                        if ($order['type'] == 'premium') {
                                            $type = 'PREMIUM';
                                        } elseif ($order['type'] == 'strong') {
                                            $type = 'STRONG';
                                        } else {
                                            $type = '';
                                        }
                                        ?>

                                        <?php
                                        if ($order['kind'] == 'raw') {
                                            $kind = 'Xammal';
                                        } elseif ($order['kind'] == 'sauce') {
                                            $kind = 'Sous';
                                        } elseif ($order['kind'] == 'flavour') {
                                            $kind = 'Aromatlı sous';
                                        } elseif ($order['kind'] == 'product') {
                                            $kind = 'Hazır məhsul';
                                        }
                                        ?>


                                        <tr>


                                            <td>
                                                <strong><?= $kind; ?></strong>
                                                <hr>
                                                <?php
                                                if ($kind == 'Hazır məhsul') {
                                                    echo htmlspecialchars(
                                                        $type . " " .
                                                        $order['name'] . " - " . number_format((float) $order['weight'] * 1000, 0) .
                                                        "qr, (istehsal: " . date('d.m.Y', strtotime($order['production_date'])) . ")"
                                                    );
                                                } else {
                                                    echo $order['name'];
                                                }
                                                ?>
                                            </td>


                                            <td>
                                                <?= $order['qty'] ?>

                                                <?php
                                                if ($kind == 'Xammal') {
                                                    $stmt = $pdo->prepare("
                                                        SELECT unit
                                                        FROM raw_materials
                                                        WHERE name = ?
                                                    ");

                                                    $stmt->execute([$order['name']]);

                                                    $unit = $stmt->fetchColumn();

                                                    echo $unit ?: '';
                                                } elseif ($kind == 'Hazır məhsul') {
                                                    echo 'əd';
                                                } elseif ($kind == 'Sous' || $kind == 'Aromatlı sous') {
                                                    echo 'kq';
                                                }
                                                ?>

                                            </td>

                                            <td>
                                                <?= number_format(((float) $order['cost']), 4); ?> ₼
                                                <br>
                                                <small>
                                                    (ƏDV:
                                                    <?php
                                                    if ($order['edv'] == '18') {
                                                        echo '+18% = ' . number_format((float) ($order['cost']) * 1.18, 3, ',', ' ') . ' ₼';
                                                    } elseif ($order['edv'] == '0') {
                                                        echo '+0% =' . number_format((float) ($order['cost']), 3, ',', ' ') . ' ₼';
                                                    } else {
                                                        echo 'yox';
                                                    }
                                                    ?>
                                                    )
                                                </small>
                                            </td>

                                            <td>
                                                <?= number_format((float) $order['sell_price'], 4); ?>
                                                ₼
                                            </td>

                                            <td>
                                                <?= number_format((float) $order['sell_price'] * $order['qty'], 4); ?> ₼
                                            </td>


                                            <td>
                                                <?= number_format((float) $profit, 4); ?> ₼
                                            </td>


                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                            <div class="alert alert-info">
                                <strong>
                                    Cəmi maya dəyəri:
                                    <?= number_format((float) $totalCost, 3, ',', ' '); ?> ₼
                                </strong>
                                <br>
                                <strong>
                                    Cəmi ƏDV:
                                    <?= number_format((float) $totalEdv, 3, ',', ' '); ?> ₼
                                </strong>
                                <br>
                                <strong>
                                    Cəmi satş həcmi:
                                    <?= number_format((float) $totalRevenue, 3, ',', ' '); ?> ₼
                                </strong>
                                <br>
                                <strong>
                                    Cəmi qazanc:
                                    <?= number_format((float) $totalProfit, 3, ',', ' '); ?> ₼
                                </strong>
                            </div>


                        </div>
                    </div>
                </div>
            </main>
            <?php require_once('inc/footer.php'); ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="assets/js/scripts.js"></script>
    <script src="assets/js/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="assets/js/datatables-simple-demo.js"></script>
</body>

</html>