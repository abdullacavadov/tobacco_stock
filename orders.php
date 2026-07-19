<?php require_once('inc/check_session.php'); ?>
<?php

require 'inc/db.php';

$orders = $pdo
    ->query("
SELECT *
FROM orders
ORDER BY created_at DESC
")
    ->fetchAll();

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


                            <a class="btn btn-success" href="create-order.php">
                                <i class="fas fa-plus"></i>
                                Satış reallaşdır
                            </a>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>Məhsul</th>
                                        <th>Say</th>
                                        <th>Maya</th>
                                        <th>Satış</th>
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
                                        } else {
                                            $type = 'STRONG';
                                        }
                                        ?>

                                        <tr>

                                            <style>
                                                table.dataTable tbody td.premium-cell {
                                                    background: red !important;
                                                    color: white !important;
                                                    box-shadow: inset 0 0 0 9999px red !important;
                                                }

                                                table.dataTable tbody td.strong-cell {
                                                    background: black !important;
                                                    color: white !important;
                                                    box-shadow: inset 0 0 0 9999px black !important;
                                                }
                                            </style>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $type . " " .
                                                    $order['name'] . " - " . number_format((float) $order['weight'] * 1000, 0) .
                                                    "qr, (istehsal: " . date('d.m.Y', strtotime($order['production_date'])) . ")"
                                                ) ?>
                                            </td>


                                            <td>
                                                <?= $order['qty'] ?> ədəd
                                            </td>

                                            <td>
                                                <?= (float) $order['cost']; ?> ₼
                                            </td>

                                            <td>
                                                <?= (float) $order['sell_price']; ?> ₼
                                            </td>

                                            <td>
                                                <?= (float) $order['sell_price'] * $order['qty']; ?> ₼
                                                <br>
                                                <small>
                                                    Qazanc: 
                                                    <?= (float) ($order['sell_price'] - $order['cost']) * $order['qty']; ?> ₼
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