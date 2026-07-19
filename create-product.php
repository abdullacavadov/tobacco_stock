<?php require_once('inc/check_session.php'); ?>
<?php require_once('inc/db.php'); ?>



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
                    <h1 class="mt-4">Qablaşdırma</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="products.php">Hazır məhsullar</a></li>
                        <li class="breadcrumb-item active">Qablaşdırma</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Hazır məhsulların qablaşdırılması
                            </span>


                            <a class="btn btn-success" href="products.php">
                                <i class="fas fa-list"></i>
                                Hazır məhsullar
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="create_product">


                                <div class="row">

                                    <div class="col-md-12 mb-3">
                                        <div class="form-floating">
                                            <select class="form-control" id="name" name="name">

                                                <?php

                                                $stmt = $pdo->query("
                                                    SELECT
                                                        *
                                                    FROM sauce_with_flavour 
                                                    WHERE qty > 0 
                                                    ORDER BY created_at DESC
                                                ");

                                                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                ?>

                                                <?php foreach ($rows as $row): ?>

                                                    <option value="<?= htmlspecialchars($row['flavour_name']) ?>">
                                                        <?= htmlspecialchars(
                                                            $row['flavour_name'] .
                                                            " (" .
                                                            $row['sauce_type'] .
                                                            ", stokda: " .
                                                            number_format((float) $row['qty'], 2, '.', '') .
                                                            " kq)"
                                                        ) ?>
                                                    </option>

                                                <?php endforeach; ?>
                                            </select>
                                            <label for="name">Dad</label>
                                        </div>
                                    </div>


                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <select class="form-control" id="package" name="package">

                                                <?php

                                                $stmt = $pdo->query("
                                                    SELECT *
                                                    FROM raw_materials
                                                    WHERE stock > 0
                                                    AND type = 'package'
                                                    ORDER BY name ASC
                                                ");

                                                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                ?>

                                                <?php foreach ($rows as $row): ?>

                                                    <option value="<?= htmlspecialchars($row['name']) ?>">
                                                        <?= htmlspecialchars($row['name']) ?>
                                                    </option>

                                                <?php endforeach; ?>
                                            </select>
                                            <label for="package">Qab</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <select class="form-control" id="label" name="label">

                                                <?php

                                                $stmt = $pdo->query("
                                                    SELECT *
                                                    FROM raw_materials
                                                    WHERE stock > 0
                                                    AND type = 'label'
                                                    ORDER BY name ASC
                                                ");

                                                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                ?>

                                                <?php foreach ($rows as $row): ?>

                                                    <option value="<?= htmlspecialchars($row['name']) ?>">
                                                        <?= htmlspecialchars($row['name']) ?>
                                                    </option>

                                                <?php endforeach; ?>
                                            </select>
                                            <label for="label">Etiket</label>
                                        </div>
                                    </div>

                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input class="form-control" id="package_weight" name="package_weight" type="number" step="0.01"
                                                placeholder="100 kq" />
                                            <label for="package_weight">Qabın həcmi (qram)</label>
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-control" id="type" name="type">
                                                <option value="premium" selected>Qırmızı</option>
                                                <option value="strong">Qara</option>
                                            </select>
                                            <label for="type">Məhsulun növü</label>
                                        </div>
                                    </div>


                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input class="form-control" id="stock" name="stock" type="number" step="0.01"
                                                placeholder="100 kq" />
                                            <label for="stock">Say (əd)</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <div class="form-floating">
                                            <input class="form-control" id="production_date" name="production_date" type="date"
                                                placeholder="100 kq" />
                                            <label for="production_date">İstehsal tarixi</label>
                                        </div>
                                    </div>


                                </div>


                                <div class="mt-4 mb-0">
                                    <div class="d-grid"><button type="submit" class="btn btn-primary btn-block">Əlavə
                                            et</button></div>
                                </div>
                            </form>

                            <div id="recipePreview" class="mt-4"></div>

                        </div>
                    </div>
                </div>
            </main>
            <?php require_once('inc/footer.php'); ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="assets/js/scripts.js"></script>
    <script src="assets/js/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="assets/js/datatables-simple-demo.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const form = document.getElementById("create_product");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/create_product.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {

                            Swal.fire({
                                title: "Məhsul anbara əlavə edildi.",
                                icon: "success"
                            });

                            form.reset();

                        } else {

                            Swal.fire({
                                title: "Xəta baş verdi",
                                text: data.message,
                                icon: "error"
                            });

                        }

                    })

                    .catch(error => {

                        Swal.fire({
                            title: "Server xətası baş verdi.",
                            text: "Zəhmət olmasa, yenidən cəhd edin.",
                            icon: "error",
                            draggable: true
                        });

                    })

                    .finally(() => {
                        submitBtn.disabled = false;
                    });

            });

        });
    </script>


</body>

</html>