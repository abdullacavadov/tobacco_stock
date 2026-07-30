<?php require_once('inc/check_session.php'); ?>
<?php

require 'inc/db.php';

$rawTotal = $pdo
    ->query("SELECT COALESCE(SUM(price), 0) FROM raw_materials")
    ->fetchColumn();

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

    <style>
        .info-badge {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #eef4ff;
            color: #0d6efd;
            cursor: pointer;
            transition: .25s;
            border: 1px dotted #6e6e6e;
            margin-left: 5px;
        }

        .info-badge:hover {
            background: #0d6efd;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(13, 110, 253, .3);
        }
    </style>
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


                            <style>
                                #datatablesSimple tfoot {
                                    display: table-footer-group !important;
                                }
                            </style>


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
                                        <th>Təchizatçı</th>
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

                                                <span class="d-flex align-items-center">
                                                    <?= $item['name'] ?>

                                                    <button type="button" class="info-badge description-btn"
                                                        data-bs-toggle="modal" data-bs-target="#descriptionModal"
                                                        data-title="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>"
                                                        data-description="<?= htmlspecialchars($item['description'], ENT_QUOTES) ?>">
                                                        <i class="fas fa-circle-info"></i>
                                                    </button>
                                                </span>
                                            </td>

                                            <td>
                                                <?= $item['supplier'] ?>
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
                                                <?= $item['price'] / $item['stock']; ?> ₼
                                                <br>
                                                <small><b>(Cəmi: <?= (float) $item['price']; ?>
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

                                    <tr>

                                    </tr>
                                </tbody>


                            </table>
                            <div class="alert alert-info">
                                <strong>
                                    Cəmi xammal dəyəri: <?= number_format((float) $rawTotal, 3, ',', ' '); ?> AZN
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require_once('inc/footer.php'); ?>
        </div>
    </div>

    <div class="modal fade" id="descriptionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-circle-info me-2"></i>
                        <span id="descriptionTitle"></span>
                    </h5>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">
                    <p class="mb-0" id="descriptionText"></p>
                </div>

            </div>
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

    <script>
        const modal = document.getElementById('descriptionModal');

        modal.addEventListener('show.bs.modal', function (event) {

            const button = event.relatedTarget;

            document.getElementById('descriptionTitle').textContent =
                button.dataset.title;

            document.getElementById('descriptionText').innerHTML =
                button.dataset.description.replace(/\n/g, "<br>");
        });
    </script>
</body>

</html>