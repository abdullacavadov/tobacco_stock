<?php require_once('inc/check_session.php'); ?>
<?php require_once('inc/db.php'); ?>

<?php
if (!isset($_GET['pid'])) {
    header("Location: products.php");
    exit();
}

$pid = $_GET['pid'];

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$pid]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: products.php");
    exit();
}

$name = $product['name'];
$weight = $product['weight'];
$type = $product['type'];
$stock = $product['stock'];
$price = $product['price'];
$production_date = $product['production_date'];
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
                    <h1 class="mt-4">Məhsulun redaktəsi</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="products.php">Hazır məhsullar</a></li>
                        <li class="breadcrumb-item active">Məhsulun redaktəsi</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Hazır məhsulun redaktəsi
                            </span>


                            <a class="btn btn-success" href="products.php">
                                <i class="fas fa-list"></i>
                                Hazır məhsullar
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="edit_product">

                            <input type="hidden" name="pid" value="<?= htmlspecialchars($pid) ?>">

                                <div class="row">
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="form-floating">
                                            <input class="form-control" id="stock" name="stock" type="number" step="1"
                                                placeholder="100 kq" value="<?= htmlspecialchars($product['stock']) ?>" />
                                            <label for="stock">Stok sayı (əd)</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="form-floating">
                                            <input class="form-control" id="production_date" name="production_date" type="date"
                                                placeholder="100 kq" value="<?= htmlspecialchars($product['production_date']) ?>" />
                                            <label for="production_date">İstehsal tarixi</label>
                                        </div>
                                    </div>


                                </div>


                                <div class="mt-4 mb-0">
                                    <div class="d-grid"><button type="submit" class="btn btn-primary btn-block">Dəyişdir</button></div>
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

            const form = document.getElementById("edit_product");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/edit_product.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {

                            Swal.fire({
                                title: "Məhsul uğurla dəyişdirildi.",
                                icon: "success"
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