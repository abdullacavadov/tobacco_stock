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
                    <h1 class="mt-4">Satış</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="orders.php">Satışlar</a></li>
                        <li class="breadcrumb-item active">Satış yaratma</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Aromatlı sous satışın reallaşdırılması
                            </span>


                            <a class="btn btn-success" href="orders.php">
                                <i class="fas fa-list"></i>
                                Satışlar
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="create_flavour_order">


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
                                                    ORDER BY created_at ASC
                                                ");
                                                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                ?>

                                                <?php foreach ($rows as $row): ?>

                                                    <<?php
                                                    if ($row['sauce_type'] == 'premium') {
                                                        $row['sauce_type'] = 'Premium';
                                                    } elseif ($row['sauce_type'] == 'strong') {
                                                        $row['sauce_type'] = 'Strong';
                                                    }
                                                    ?>


                                                        <option value="<?= htmlspecialchars($row['id']) ?>">
                                                            <?= htmlspecialchars(
                                                                $row['sauce_type'] . ' ' . $row['flavour_name'] . ' (1kq mayası - ' . number_format($row['cost'] / $row['qty'], 3) .
                                                                "₼, stokda: " .
                                                                number_format($row['qty'], 2) . 'kq | ' . date('d.m.Y', strtotime($row['created_at'])) . ")"
                                                            ) ?>
                                                        </option>

                                                    <?php endforeach; ?>
                                            </select>
                                            <label for="name">Məhsul</label>
                                        </div>
                                    </div>




                                    <div class="col-md-6 mb-3">
                                        <div class="form-floating">
                                            <input class="form-control" id="qty" name="qty" type="number" step="0.001"
                                                placeholder="100" />
                                            <label for="qty">Miqdar (kq)</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="form-floating">
                                            <input class="form-control" id="price" name="price" type="number"
                                                step="0.0001" placeholder="10" />
                                            <label for="price">Vahidin satış qiyməti (AZN)</label>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <div class="form-floating">
                                            <input class="form-control" id="customer" name="customer" type="text"
                                                placeholder="Ağayev Elvin" />
                                            <label for="customer">Alıcı</label>
                                        </div>
                                    </div>


                                </div>


                                <div class="mt-4 mb-0">
                                    <div class="d-grid"><button type="submit" class="btn btn-primary btn-block">Əlavə
                                            et</button></div>
                                </div>
                            </form>


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

            const form = document.getElementById("create_flavour_order");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/create_flavour_order.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {

                            Swal.fire({
                                title: "Sous satıldı.",
                                icon: "success",
                                confirmButtonText: "OK"
                            }).then(() => {
                                location.reload();
                            });
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