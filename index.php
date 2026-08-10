<?php require_once('inc/check_session.php'); ?>

<?php
require 'inc/db.php';

$rawTotal = $pdo
    ->query("SELECT SUM(price) FROM raw_materials")
    ->fetchColumn();

$sauceTotal = $pdo
    ->query("SELECT SUM(stock) FROM sauce_stock")
    ->fetchColumn();

$productTotal = $pdo
    ->query("SELECT SUM(qty) FROM sauce_with_flavour")
    ->fetchColumn();

$packageTotal = $pdo
    ->query("SELECT SUM(stock) FROM products")
    ->fetchColumn();
?>


<?php

$years = $pdo->query("
                                    SELECT DISTINCT YEAR(created_at) AS year
                                    FROM orders
                                    WHERE created_at IS NOT NULL
                                    ORDER BY year DESC
                                ")->fetchAll(PDO::FETCH_COLUMN);

$currentYear = !empty($years) ? (int) $years[0] : (int) date('Y');
?>


<?php

$stmt = $pdo->prepare("
                                    SELECT 
                                        MONTH(created_at) AS month,
                                        COALESCE(SUM(sell_price * qty), 0) AS total
                                    FROM orders
                                    WHERE YEAR(created_at) = ?
                                    GROUP BY MONTH(created_at)
                                    ORDER BY MONTH(created_at)
                                ");

$stmt->execute([$currentYear]);

$salesByMonth = array_fill(1, 12, 0);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $salesByMonth[(int) $row['month']] = (float) $row['total'];
}
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
                    <h1 class="mt-4">Dashboard</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>



                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">Ümumi xammal dəyəri</div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white text-decoration-none"
                                        href="raw.php"><?= number_format($rawTotal, 2) ?> AZN</a>

                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4">
                                <div class="card-body">Ümumi sous həcmi</div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white text-decoration-none"
                                        href="sauces.php"><?= number_format($sauceTotal, 2) ?> KQ</a>

                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">Dadlandırılmışların ümumi həcmi</div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white text-decoration-none"
                                        href="flavours.php"><?= number_format($productTotal, 2) ?> KQ</a>

                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4">
                                <div class="card-body">Qablaşdırılmış məhsullar</div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white text-decoration-none"
                                        href="products.php"><?= number_format($packageTotal, 0) ?> ƏD</a>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-area me-1"></i>
                                    Area Chart Example
                                </div>
                                <div class="card-body"><canvas id="myAreaChart" width="100%" height="40"></canvas></div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>
                                            <i class="fas fa-chart-bar me-1"></i>
                                            Satışlar
                                        </span>
                                        <select id="salesYear" class="form-control" style="width: 120px;">
                                            <?php foreach ($years as $year): ?>
                                                <option value="<?= (int) $year ?>" <?= (int) $year === $currentYear ? 'selected' : '' ?>>
                                                    <?= (int) $year ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>


                                </div>


                                <div class="card-body">


                                    <canvas id="myBarChart" width="100%" height="40"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
            <?php require_once('inc/footer.php'); ?>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/4.0.0/jquery.min.js"
        integrity="sha512-8LENNbXmzI/Gbj+OwXmqR6V4QaUAw0/porPzy1+dQoJqC0JPHedWoe0DDOTL2uHA5XXJyIsPtiMHH86pVlay6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        const initialSales = <?= json_encode(array_values($salesByMonth), JSON_NUMERIC_CHECK) ?>;
    </script>

    <script>
        $('#salesYear').on('change', function () {

            const year = $(this).val();

            $.ajax({
                url: 'ajax/get_sales_data.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    year: year
                },

                success: function (response) {

                    if (!response.success) {
                        alert(response.message || 'Məlumat alınmadı.');
                        return;
                    }

                    salesChart.data.datasets[0].data = response.data;

                    salesChart.update();
                },

                error: function () {
                    alert('Satış məlumatları alınarkən xəta baş verdi.');
                }
            });

        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>



    <script src="assets/js/scripts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="assets/demo/chart-area-demo.js"></script>
    <script src="assets/demo/chart-bar-demo.js"></script>
    <script src="assets/js/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="assets/js/datatables-simple-demo.js"></script>
    <script src="assets/js/app.js"></script>


</body>

</html>