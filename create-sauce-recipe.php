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
                    <h1 class="mt-4">Resept hazırlanması</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="sauce-recipes.php">Sous reseptləri</a></li>
                        <li class="breadcrumb-item active">Resept hazırlanması</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Sous resepturasının hazırlanması
                            </span>


                            <a class="btn btn-success" href="sauce-recipes.php">
                                <i class="fas fa-list"></i>
                                Reseptlər
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="recipeForm">

                                <div class="row mb-3">

                                    <div class="col-md-6">
                                        <label class="form-label">Resept adı</label>
                                        <input type="text" class="form-control" name="name">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Sous növü</label>
                                        <select class="form-control" name="type">
                                            <option value="premium">Qırmızı</option>
                                            <option value="strong">Qara</option>
                                        </select>
                                    </div>

                                </div>

                                <hr>

                                <h5>Xammallar</h5>

                                <?php

                                $raws = $pdo->query("
    SELECT *
    FROM raw_materials
    WHERE type='raw' AND stock > 0
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <div id="rawsContainer"></div>

                                <div class="mt-3">
                                    <button type="button" class="btn btn-secondary" id="addRaw">
                                        + Xammal əlavə et
                                    </button>
                                </div>

                                <div class="mt-3">
                                    <strong>Cəm faiz:</strong>
                                    <span id="totalPercent">0</span>%
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        Yadda saxla
                                    </button>
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

            const form = document.getElementById("recipeForm");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/create_sauce_recipe.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {

                            Swal.fire({
                                title: "Yeni resept uğurla yaradıldı",
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

    <script>

        const raws = <?= json_encode($raws, JSON_UNESCAPED_UNICODE) ?>;

        const container = document.getElementById('rawsContainer');

        function formatDate(dateString) {
            if (!dateString) {
                return '';
            }

            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return dateString;
            }

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${day}.${month}.${year}`;
        }

        function updateTotalPercent() {

            let total = 0;

            document.querySelectorAll('.percentage-input')
                .forEach(input => {

                    total += parseFloat(input.value || 0);

                });

            document.getElementById('totalPercent').innerText =
                total.toFixed(2);

        }

        function addRawRow() {

            let options = '';

            raws.forEach(raw => {

                options += `
            <option value="${raw.name}">
                ${raw.name} / Stok: ${raw.stock} / Qiymət: ${parseFloat(raw.price).toFixed(2)} AZN / ${formatDate(raw.in_stock)}
            </option>
        `;

            });

            const row = document.createElement('div');

            row.className = 'row mb-2 raw-row';

            row.innerHTML = `

        <div class="col-md-7">

            <select
                class="form-control raw-name"
                name="raw_name[]">

                ${options}

            </select>

        </div>

        <div class="col-md-3">

            <input
                type="number"
                step="0.01"
                min="0"
                class="form-control percentage-input"
                name="percentage[]"
                placeholder="%">

        </div>

        <div class="col-md-2">

            <button
                type="button"
                class="btn btn-danger remove-row">

                ×

            </button>

        </div>

    `;

            container.appendChild(row);

            row.querySelector('.percentage-input')
                .addEventListener('input', updateTotalPercent);

            row.querySelector('.remove-row')
                .addEventListener('click', function () {

                    row.remove();

                    updateTotalPercent();

                });

        }

        document
            .getElementById('addRaw')
            .addEventListener('click', addRawRow);

        addRawRow();

    </script>
</body>

</html>