<?php require_once('inc/check_session.php'); ?>
<?php

require 'inc/db.php';


if (!isset($_GET['type'])) {
    $sql = "SELECT * FROM raw_materials WHERE stock > 0 ORDER BY in_stock DESC";
} else {
    $type = $_GET['type'];
    $sql = "SELECT * FROM raw_materials WHERE stock > 0 AND type = '$type' ORDER BY in_stock DESC";
}

$products = $pdo
    ->query($sql)
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
                    <h1 class="mt-4">Xammal</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Xammal</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Anbarda qalan xammal
                            </span>

                            <form action="raw.php" method="get" class="d-flex gap-1">

                                <select class="form-control" name="type" style="width: 160px">
                                    <option disabled selected>-- Filter seç --</option>
                                    <option value="raw">Xammal</option>
                                    <option value="flavour">Aroma</option>
                                    <option value="package">Qab</option>
                                    <option value="label">Etiket</option>
                                    <option value="cover">Paket</option>
                                </select>

                                <input type="submit" class="btn btn-primary" value="Filtrlə">

                                <?php if (isset($_GET['type'])): ?>
                                    <a href="raw.php" class="btn btn-secondary">Sıfırla</a>
                                <?php endif; ?>

                            </form>



                            <a class="btn btn-success" href="add-in-stock.php">
                                <i class="fas fa-plus"></i>
                                Anbar qəbulu
                            </a>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>Məhsul</th>
                                        <th>Növü</th>
                                        <th>Həcm</th>
                                        <th>Qiymət (kq)</th>
                                        <th>Gəliş</th>
                                        <th>Əməliyyat</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($products as $item): ?>

                                        <?php
                                        if ($item['type'] == 'raw') {
                                            $item['type'] = 'Xammal';
                                            $color = 'primary';
                                        } elseif ($item['type'] == 'flavour') {
                                            $item['type'] = 'Aroma';
                                            $color = 'danger';
                                        } elseif ($item['type'] == 'package') {
                                            $item['type'] = 'Qab';
                                            $color = 'warning';
                                        } elseif ($item['type'] == 'label') {
                                            $item['type'] = 'Etiket';
                                            $color = 'success';
                                        } elseif ($item['type'] == 'cover') {
                                            $item['type'] = 'Paket';
                                            $color = 'dark';
                                        }
                                        ?>

                                        <tr>

                                            <td>
                                                <?= $item['name'] ?>
                                            </td>

                                            <td>
                                                <span class="badge bg-<?= $color ?>">
                                                    <?= $item['type'] ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= $item['stock'] . ' ' . $item['unit'] ?>

                                            </td>

                                            <td>
                                                <?= $item['price']; ?> ₼
                                                <br>
                                                <small><b>(Cəmi: <?= (float) $item['price'] * $item['stock']; ?>
                                                        ₼)</b></small>
                                            </td>

                                            <td>
                                                <?= date('d.m.Y', strtotime($item['in_stock'])); ?>
                                            </td>

                                            <td>
                                                <a class="btn btn-sm btn-primary"
                                                    href="edit-raw.php?rid=<?= $item['id'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a class="btn btn-sm btn-danger delete-btn" type="button"
                                                    href="ajax/delete_raw.php?rid=<?= $item['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <th>Məhsul</th>
                                        <th>Həcm</th>
                                        <th>Qiymət</th>
                                        <th>Gəliş</th>
                                    </tr>
                                </tfoot>
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