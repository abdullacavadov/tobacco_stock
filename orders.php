<?php require_once('inc/check_session.php'); ?>
<?php

require 'inc/db.php';

$orderTotalCost = $pdo
    ->query("SELECT COALESCE(SUM(cost*qty), 0) FROM orders")
    ->fetchColumn();

$orderTotalPrice = $pdo
    ->query("SELECT COALESCE(SUM(sell_price*qty), 0) FROM orders")
    ->fetchColumn();

$fullProfit = $orderTotalPrice - $orderTotalCost;

if (!isset($_GET['kind'])) {
    $sql = "SELECT * FROM orders ORDER BY created_at DESC";
} else {
    $kind = $_GET['kind'];
    $sql = "SELECT * FROM orders WHERE kind = '$kind' ORDER BY created_at DESC";
}

$orders = $pdo
    ->query($sql)
    ->fetchAll();

$totalCost = 0;
$totalRevenue = 0;
$totalProfit = 0;
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
                                Ümumi satışlar
                            </span>


                            <form action="orders.php" method="get" class="d-flex gap-1">

                                <select class="form-control" name="kind" style="width: 160px">
                                    <option disabled selected>-- Filter seç --</option>
                                    <option value="raw">Xammal</option>
                                    <option value="flavour">Aromalı sous</option>
                                    <option value="sauce">Sous</option>
                                    <option value="product">Hazır məhsul</option>
                                </select>

                                <input type="submit" class="btn btn-primary" value="Filtrlə">

                                <?php if (isset($_GET['kind'])): ?>
                                    <a href="orders.php" class="btn btn-secondary">Sıfırla</a>
                                <?php endif; ?>

                            </form>


                            <a class="btn btn-success" href="create-order.php">
                                <i class="fas fa-plus"></i>
                                Satış reallaşdır
                            </a>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Məhsul</th>
                                        <th>Miqdar</th>
                                        <th>Maya</th>
                                        <th>Satış (vahid)</th>
                                        <th>Yekun</th>
                                        <th>Müştəri</th>
                                        <th>Tarix</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($orders as $order): ?>

                                        <?php
                                        if ($order['type'] == 'premium') {
                                            $type = 'PREMIUM';
                                        } elseif ($order['type'] == 'strong') {
                                            $type = 'STRONG';
                                        } else {
                                            $type = '';
                                        }

                                        $name = $order['name'];
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

                                        <?php
                                        $revenue = (float) $order['sell_price'];
                                        $profit = (float) (($order['sell_price'] - $order['cost']) * $order['qty']);

                                        $totalCost += (float) $order['cost'] * $order['qty']; // ümumi maya dəyəri
                                        $totalRevenue += (float) $revenue * $order['qty']; //ümumi dövriyyə
                                        $totalProfit += $profit;
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

                                                    $stmt->execute([$name]);

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
                                            </td>

                                            <td>
                                                <?= number_format((float) $order['sell_price'], 4); ?>
                                                ₼
                                            </td>

                                            <td>
                                                <?= number_format((float) $order['sell_price'] * $order['qty'], 4); ?> ₼
                                                <br>
                                                <small>
                                                    Qazanc:
                                                    <?= number_format((float) $profit, 4); ?>
                                                    ₼
                                                </small>
                                            </td>

                                            <td>
                                                <?= $order['customer'] ?>
                                            </td>

                                            <td>
                                                <?= date('d.m.Y', strtotime($order['created_at'])); ?>
                                            </td>


                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                            <div class="alert alert-info">
                                <strong>
                                    Cəmi maya dəyəri:
                                    <?= number_format((float) $orderTotalCost, 3, ',', ' '); ?> AZN
                                </strong>
                                <br>
                                <strong>
                                    Cəmi satş həcmi:
                                    <?= number_format((float) $orderTotalPrice, 3, ',', ' '); ?> kq
                                </strong>
                                <br>
                                <strong>
                                    Cəmi qazanc:
                                    <?= number_format((float) $fullProfit, 3, ',', ' '); ?> kq
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