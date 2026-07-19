<?php require_once('inc/check_session.php'); ?>
<?php require_once('inc/db.php'); ?>

<?php
if (!isset($_GET['sid']) || empty($_GET['sid'])) {
    header("Location: sauces.php");
    exit();
}

$sauce_id = $_GET['sid'];

$statement = $pdo->prepare("SELECT * FROM sauce_stock WHERE id = ?");
$statement->execute([$sauce_id]);
$sauce = $statement->fetch(PDO::FETCH_ASSOC);

$sauce_stock = $sauce['stock'];
$sauce_type = $sauce['type']; 
$sauce_price = $sauce['price'];

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
                    <h1 class="mt-4">Sous tənzimləməsi</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="sauces.php">Souslar</a></li>
                        <li class="breadcrumb-item active">Sous tənzimləməsi</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Sous tənzimləməsi
                            </span>


                            <a class="btn btn-success" href="sauces.php">
                                <i class="fas fa-list"></i>
                                Souslar
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="sauce_edit">

                                <input type="hidden" name="id" value="<?= $sauce_id; ?>">


                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-control" id="type" name="type">
                                                <option value="premium" <?php if ($sauce_type == 'premium') {
                                                    echo 'selected';
                                                } ?>>Qırmızı</option>
                                                <option value="strong" <?php if ($sauce_type == 'strong') {
                                                    echo 'selected';
                                                } ?>>Qara</option>
                                            </select>
                                            <label for="stock">Növü</label>
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input class="form-control" id="stock" name="stock" type="number"
                                                step="0.01" placeholder="100 kq" value="<?= $sauce_stock; ?>" />
                                            <label for="stock">Həcm (kq)</label>
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input class="form-control" id="price" name="price" type="number"
                                                step="0.01" placeholder="100 ₼" value="<?= $sauce_price; ?>" />
                                            <label for="price">Ümumi həcmin qiyməti (AZN)</label>
                                        </div>
                                    </div>


                                </div>





                                <div class="mt-4 mb-0">
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            Yadda saxla
                                        </button>
                                    </div>
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

            const form = document.getElementById("sauce_edit");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/edit_sauce.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.text())

                    .then(response => {

                        if (response.trim() === "success") {

                            Swal.fire({
                                title: "Sous həcmi uğurla yeniləndi.",
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