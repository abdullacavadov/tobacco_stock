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
                                Satışın reallaşdırılması
                            </span>


                            <a class="btn btn-success" href="orders.php">
                                <i class="fas fa-list"></i>
                                Satışlar
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="create_order">

                                <div class="row">

                                    <div class="col-md-12 mb-3">
                                        <div class="form-floating">
                                            <input class="form-control" name="customer" type="text" placeholder="Alıcı">
                                            <label>Alıcı</label>
                                        </div>
                                    </div>

                                </div>

                                <div id="items">

                                    <div class="item-row border rounded p-3 mb-3">

                                        <div class="row">

                                            <div class="col-md-2 mb-3">
                                                <div class="form-floating">

                                                    <select class="form-control kind-select" name="items[0][kind]">

                                                        <option value="raw">Xammal</option>
                                                        <option value="sauce">Sous</option>
                                                        <option value="flavour">Sous + Aroma</option>
                                                        <option value="product">Hazır məhsul</option>

                                                    </select>

                                                    <label>Növ</label>

                                                </div>
                                            </div>

                                            <div class="col-md-5 mb-3">
                                                <div class="form-floating">

                                                    <select class="form-control product-select" name="items[0][id]">

                                                    </select>

                                                    <label>Məhsul</label>

                                                </div>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <div class="form-floating">

                                                    <input class="form-control" type="number" step="0.001"
                                                        name="items[0][qty]">

                                                    <label>Miqdar</label>

                                                </div>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <div class="form-floating">

                                                    <input class="form-control" type="number" step="0.0001"
                                                        name="items[0][price]">

                                                    <label>Vahidin qiyməti</label>

                                                </div>
                                            </div>

                                            <div class="col-md-1 d-flex align-items-center">

                                                <button type="button" class="btn btn-danger remove-row w-100">
                                                    <i class="fas fa-xmark"></i>
                                                </button>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <div class="d-flex justify-content-end mb-4">
                                    <button type="button" class="btn btn-success add-row">
                                        <i class="fas fa-plus"></i> Məhsul əlavə et
                                    </button>
                                </div>

                                <div class="d-grid mt-4">
                                    <button class="btn btn-primary" type="submit">
                                        Satışı tamamla
                                    </button>
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
        const items = document.getElementById("items");

        let index = 1;

        async function loadProducts(row) {

            const kind = row.querySelector(".kind-select").value;
            const select = row.querySelector(".product-select");

            select.innerHTML = '<option>Yüklənir...</option>';

            const response = await fetch(
                "./ajax/get_products.php?kind=" + encodeURIComponent(kind)
            );

            const data = await response.json();

            select.innerHTML = "";

            if (!data.success) {

                select.innerHTML =
                    "<option>Məhsul tapılmadı</option>";

                return;
            }

            data.products.forEach(product => {

                const option = document.createElement("option");

                option.value = product.id;
                option.textContent = product.text;

                select.appendChild(option);

            });

        }


        function updateRemoveButtons() {

            const rows = document.querySelectorAll(".item-row");

            rows.forEach((row, i) => {
                const btn = row.querySelector(".remove-row");
                if (rows.length === 1) {
                    btn.style.visibility = "hidden";
                } else {
                    btn.style.visibility = "visible";
                }
            });

        }


        document.addEventListener("DOMContentLoaded", () => {
            loadProducts(document.querySelector(".item-row"));
            updateRemoveButtons();
        });

        document.addEventListener("change", function (e) {
            if (e.target.classList.contains("kind-select")) {
                loadProducts(
                    e.target.closest(".item-row")
                );
            }
        });

        document.addEventListener("click", function (e) {

            if (e.target.closest(".add-row")) {

                const clone =
                    document.querySelector(".item-row")
                        .cloneNode(true);

                clone.querySelectorAll("[name]").forEach(el => {

                    el.name = el.name.replace(
                        /\[\d+\]/,
                        `[${index}]`
                    );

                    if (el.tagName === "INPUT") {
                        el.value = "";
                    }

                });

                clone.querySelector(".product-select").innerHTML = "";

                items.appendChild(clone);

                loadProducts(clone);

                index++;

                updateRemoveButtons();

                // yeni sətirə scroll
                clone.scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });

            }


            if (e.target.closest(".remove-row")) {

                e.target
                    .closest(".item-row")
                    .remove();

                updateRemoveButtons();

            }

        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const form = document.getElementById("create_order");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/create_order.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {

                            Swal.fire({
                                title: "Məhsul satıldı.",
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