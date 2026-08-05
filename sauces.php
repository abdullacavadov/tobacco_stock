<?php require_once('inc/check_session.php'); ?>
<?php

require 'inc/db.php';

$sauceTotalCost = $pdo
    ->query("SELECT COALESCE(SUM(price), 0) FROM sauce_stock")
    ->fetchColumn();

$sauceTotalStock = $pdo
    ->query("SELECT COALESCE(SUM(stock), 0) FROM sauce_stock")
    ->fetchColumn();

$sauces = $pdo
    ->query("
SELECT *
FROM sauce_stock
WHERE stock > 0
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
                    <h1 class="mt-4">Souslar</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Souslar</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Anbarda qalan souslar
                            </span>


                            <div>
                                <a class="btn btn-success" href="create-sauce.php">
                                    <i class="fas fa-plus"></i>
                                    Sous hazırla
                                </a>

                                <a class="btn btn-primary" href="add-sauce.php">
                                    <i class="fas fa-money-bill"></i>
                                    Sous alışı
                                </a>
                            </div>


                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>Sous</th>
                                        <th>Həcm</th>
                                        <th>Maya (kq)</th>
                                        <th>Tarix</th>
                                        <th>Əməliyyat</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($sauces as $sauce): ?>

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

                                            <?php if ($sauce['type'] === 'premium'): ?>
                                                <td class="premium-cell">PREMIUM</td>
                                            <?php else: ?>
                                                <td class="strong-cell">STRONG</td>
                                            <?php endif; ?>

                                            <td>
                                                <?= $sauce['stock'] ?> kq
                                            </td>

                                            <td>
                                                <?= number_format((float) $sauce['price'] / $sauce['stock'], 3, ',', ' '); ?> ₼
                                                <br>
                                                <small><b>(Cəmi: <?= number_format((float) $sauce['price'], 3, ',', ' '); ?> ₼)</b></small>
                                            </td>

                                            <td>
                                                <?= date('d.m.Y', strtotime($sauce['created_at'])); ?>
                                            </td>

                                            <td>
                                                <a href="edit-sauce.php?sid=<?= $sauce['id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-pen"></i>
                                                </a>

                                                <a class="btn btn-danger delete-btn" type="button"
                                                    href="ajax/delete_sauce.php?sid=<?= $sauce['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                            <div class="alert alert-info">
                                <strong>
                                    Cəmi sous dəyəri:
                                    <?= number_format((float) $sauceTotalCost, 3, ',', ' '); ?> AZN
                                </strong>
                                <br>
                                <strong>
                                    Cəmi sous həcmi:
                                    <?= number_format((float) $sauceTotalStock, 3, ',', ' '); ?> kq
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
                title: "Silmək istədiyinizə əminsiniz?",
                text: "Bu əməliyyat geri qaytarıla bilməz!",
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
</body>

</html>