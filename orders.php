<?php require_once('inc/check_session.php'); ?>
<?php

require 'inc/db.php';

$order_no = $_GET['oid'] ?? '';

$orderTotalCost = $pdo
    ->query("SELECT COALESCE(SUM(cost*qty), 0) FROM orders")
    ->fetchColumn();

$orderTotalPrice = $pdo
    ->query("SELECT COALESCE(SUM(sell_price*qty), 0) FROM orders")
    ->fetchColumn();

$fullProfit = $orderTotalPrice - $orderTotalCost;

$sql = "
SELECT
    order_no,
    kind,
    customer,
    created_at,
    SUM(qty) AS total_qty,
    SUM(cost * qty) AS total_cost,
    SUM(sell_price * qty) AS total_price,
    COUNT(*) AS item_count
FROM orders
GROUP BY order_no
ORDER BY created_at DESC
";

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

                            <button type="button" id="exportExcel" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Yüklə
                            </button>



                            <a class="btn btn-success" href="create-order.php">
                                <i class="fas fa-plus"></i>
                                Satış reallaşdır
                            </a>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Sifariş №</th>
                                        <th>Müştəri</th>
                                        <th>Məhsul</th>
                                        <th>Məbləğ</th>
                                        <th>Qazanc</th>
                                        <th>Tarix</th>
                                        <th>Əməliyyat</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($orders as $order): ?>



                                        <?php
                                        $profit = $order['total_price'] - $order['total_cost'];

                                        $totalCost += $order['total_cost'];
                                        $totalRevenue += $order['total_price'];
                                        $totalProfit += $profit;
                                        ?>

                                        <tr>


                                            <td>
                                                <?= $order['order_no'] ?>
                                            </td>

                                            <td>
                                                <?= $order['customer'] ?>
                                            </td>

                                            <td>
                                                <?= $order['item_count'] ?> məhsul
                                            </td>

                                            <td>
                                                <?= number_format(((float) $totalRevenue), 4); ?> ₼
                                            </td>

                                            <td>
                                                <?= number_format((float) $totalProfit, 4); ?>
                                                ₼
                                            </td>

                                            <td>
                                                <?= date('d.m.Y', strtotime($order['created_at'])); ?>
                                            </td>


                                            <td>


                                                <a class="btn btn-sm btn-primary"
                                                    href="order-details.php?oid=<?= $order['order_no'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <a class="btn btn-sm btn-info"
                                                    href="edit-order.php?oid=<?= $order['order_no'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <a class="btn btn-sm btn-danger delete-btn" type="button"
                                                    href="ajax/delete_order.php?oid=<?= $order['order_no'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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
                                    <?= number_format((float) $orderTotalPrice, 3, ',', ' '); ?> AZN
                                </strong>
                                <br>
                                <strong>
                                    Cəmi qazanc:
                                    <?= number_format((float) $fullProfit, 3, ',', ' '); ?> AZN
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("click", function (e) {
            const btn = e.target.closest(".delete-btn");
            if (!btn) return;

            e.preventDefault();
            const deleteUrl = btn.getAttribute("href");

            Swal.fire({
                title: "Satışı silmək istədiyinizə əminsiniz?",
                text: "Bu əməliyyat geri qaytarıla bilməz! Geri qaytarma zamanı anbar dəyəri satış anında qeyd edilən maya dəyərinə əsasən yenidən hesablanacaq.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Bəli, sil",
                cancelButtonText: "Ləğv et"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = deleteUrl;
                }
            });
        });
    </script>

    <script>
        document.getElementById('exportExcel').addEventListener('click', function () {

            const table = document.getElementById('datatablesSimple');

            let html = `
        <html xmlns:x="urn:schemas-microsoft-com:office:excel">
        <head>
            <meta charset="UTF-8">
        </head>
        <body>
            ${table.outerHTML}
        </body>
        </html>
    `;

            const blob = new Blob(
                ['\ufeff' + html],
                { type: 'application/vnd.ms-excel' }
            );

            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');

            link.href = url;
            link.download = 'satishlar - ' + new Date().toISOString().slice(0, 10) + '.xls';

            document.body.appendChild(link);
            link.click();
            link.remove();

            URL.revokeObjectURL(url);
        });
    </script>
</body>

</html>