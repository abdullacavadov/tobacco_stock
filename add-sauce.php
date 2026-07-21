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
                    <h1 class="mt-4">Sous alışı</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="sauces.php">Souslar</a></li>
                        <li class="breadcrumb-item active">Sous alışı</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Sous alışı
                            </span>


                            <a class="btn btn-success" href="sauces.php">
                                <i class="fas fa-list"></i>
                                Souslar
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="add_sauce">


                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <div class="form-floating">
                                            <select class="form-control" id="type" name="type">
                                                <option value="premium" selected>Qırmızı</option>
                                                <option value="strong">Qara</option>
                                            </select>
                                            <label for="stock">Növü</label>
                                        </div>
                                    </div>


                                    <div class="col-md-4 mb-2">
                                        <div class="form-floating">
                                            <input class="form-control" id="stock" name="stock" type="number"
                                                step="0.0001" min="0.0001" placeholder="100 kq" />
                                            <label for="stock">Həcm (kq)</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <div class="form-floating">
                                            <input class="form-control" id="price" name="price" type="number"
                                                step="0.0001" min="0.0001" placeholder="100 ₼" />
                                            <label for="price">Ümumi alış qiyməti</label>
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

            const form = document.getElementById("add_sauce");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/add_sauce.php", {
                    method: "POST",
                    body: formData
                })
                    .then(response => response.text())
                    .then(response => {

                        if (response.trim() === "success") {

                            Swal.fire({
                                title: "Sous anbara əlavə edildi.",
                                icon: "success"
                            });

                            form.reset();

                        } else {

                            Swal.fire({
                                title: "Xəta baş verdi",
                                text: response,
                                icon: "error"
                            });

                        }

                    })
                    .catch(error => {

                        console.error(error);

                        Swal.fire({
                            title: "Server xətası baş verdi.",
                            text: "Zəhmət olmasa, yenidən cəhd edin.",
                            icon: "error"
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