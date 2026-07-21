<?php require_once('inc/db.php'); ?>
<?php require_once('inc/check_session.php'); ?>

<?php
if (!isset($_GET['rid'])) {
    header("Location: raw.php");
    exit();
}
$rid = $_GET['rid'];
$raw_material = $pdo->query("SELECT * FROM raw_materials WHERE id = $rid")->fetch();

$name = $raw_material['name'];
$type = $raw_material['type'];
$stock = $raw_material['stock'];
$unit = $raw_material['unit'];
$price = $raw_material['price'];
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
                    <h1 class="mt-4">Xammal redaktəsi</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="raw.php">Xammallar</a></li>
                        <li class="breadcrumb-item active">Xammal redaktəsi</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Xammal redaktəsi
                            </span>


                            <a class="btn btn-success" href="raw.php">
                                <i class="fas fa-list"></i>
                                Xammallar
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="raw_edit">

                            <input type="hidden" name="rid" value="<?= $rid; ?>">

                                <div class="row mb-3">
                                    <div class="col-12">

                                        <div class="form-floating">
                                            <input class="form-control" name="name" type="text"
                                                placeholder="Xammal adı" value="<?= $name; ?>">
                                            <label for="nameInput">Məhsul adı</label>
                                        </div>

                                    </div>

                                    

                                </div>


                                <div class="row mb-3">
                                    <div class="col-12">

                                        <div class="form-floating mb-3 mb-md-0">
                                            <select class="form-control" id="type" name="type">
                                                <option value="raw" <?php echo ($type == 'raw') ? 'selected' : ''; ?>>Xammal</option>
                                                <option value="flavour" <?php echo ($type == 'flavour') ? 'selected' : ''; ?>>Aroma</option>
                                                <option value="package" <?php echo ($type == 'package') ? 'selected' : ''; ?>>Qab</option>
                                                <option value="label" <?php echo ($type == 'label') ? 'selected' : ''; ?>>Etiket</option>
                                            </select>
                                            <label for="type">Növü</label>
                                        </div>
                                    </div>



                                </div>




                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-floating">
                                            <input class="form-control" id="stock" name="stock" type="number"
                                                step="0.0001" min="0.0001" placeholder="100 kq" value="<?= $stock; ?>" />
                                            <label for="stock">Həcm (kq / əd)</label>
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="price" type="number" name="price"
                                                step="0.0001" min="0.0001" value="<?= $price; ?>" />
                                            <label for="price">Ümumi həcmin qiyməti (AZN)</label>
                                        </div>
                                    </div>
                                </div>





                                <div class="mt-4 mb-0">
                                    <div class="d-grid"><button type="submit" class="btn btn-primary btn-block">Redaktə
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

            const form = document.getElementById("raw_edit");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/edit_raw.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.text())

                    .then(response => {

                        if (response.trim() === "success") {

                            Swal.fire({
                                title: "Xammal redaktə edildi.",
                                icon: "success",
                                draggable: true
                            });
                        } else {

                            Swal.fire({
                                title: "Xəta baş verdi",
                                text: response,
                                icon: "error",
                                draggable: true
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