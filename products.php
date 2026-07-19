<?php require_once('inc/check_session.php'); ?>
<?php

require 'inc/db.php';

$products = $pdo
    ->query("
SELECT *
FROM products
ORDER BY in_stock DESC, stock DESC
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
                    <h1 class="mt-4">Qablaşdırılmış məhsullar</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Qablaşdırılmış məhsullar</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Anbarda qalan qablaşdırılmış məhsullar
                            </span>


                            <a class="btn btn-success" href="create-product.php">
                                <i class="fas fa-plus"></i>
                                Qablaşdır
                            </a>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>Ad</th>
                                        <th>Növ</th>
                                        <th>Say</th>
                                        <th>Maya (əd)</th>
                                        <th>İstehsal</th>
                                        <th>Anbardadır</th>
                                        <th>Əməliyyat</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($products as $product): ?>

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
                                                <?= $product['name'] ?>
                                            </td>

                                            <?php if ($product['type'] === 'premium'): ?>
                                                <td class="premium-cell">Premium (<?= $product['weight']*1000 ?>qr)</td>
                                            <?php else: ?>
                                                <td class="strong-cell">Strong (<?= $product['weight']*1000 ?>qr)</td>
                                            <?php endif; ?>

                                            <td>
                                                <?= number_format($product['stock'], 0, '.', ',') ?> əd
                                            </td>

                                            <td>
                                                <?= (float) $product['price']; ?> ₼
                                                <br>
                                                <small><b>(Cəmi: <?= (float) $product['price'] * $product['stock']; ?> ₼)</b></small>
                                            </td>

                                            <td>
                                                <?= date('d.m.Y', strtotime($product['production_date'])); ?>
                                            </td>

                                            <td>
                                                <?= date('d.m.Y', strtotime($product['in_stock'])); ?>
                                            </td>

                                            <td>
                                                <a href="edit-product.php?pid=<?= $product['id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-pen"></i>
                                                </a>

                                                <a class="btn btn-danger delete-btn" type="button"
                                                    href="ajax/delete_product.php?pid=<?= $product['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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