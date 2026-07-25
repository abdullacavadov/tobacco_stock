<?php require_once('inc/check_session.php'); ?>
<?php require_once('inc/db.php'); ?>

<?php
if (!isset($_GET['srid']) || !is_numeric($_GET['srid'])) {
    header("Location: sauce-recipes.php");
    exit;
}

$recipeId = (int) $_GET['srid'];

$stmt = $pdo->prepare("
SELECT *
FROM sauce_recipes
WHERE id=?
");

$stmt->execute([$recipeId]);

$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    die("Resept tapılmadı");
}
?>


<?php
$stmt = $pdo->prepare("
SELECT *
FROM sauce_recipe_items
WHERE recipe_id=?
ORDER BY id
");

$stmt->execute([$recipeId]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

                                <input type="hidden" name="recipe_id" value="<?= $recipeId ?>">

                                <div class="row mb-3">

                                    <div class="col-md-6">
                                        <label class="form-label">Resept adı</label>
                                        <input type="text" class="form-control" name="name"
                                            value="<?= htmlspecialchars($recipe['name']) ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Sous növü</label>
                                        <select class="form-control" name="type">
                                            <option value="premium" <?= $recipe['type'] === 'premium' ? 'selected' : '' ?>>
                                                Qırmızı</option>
                                            <option value="strong" <?= $recipe['type'] === 'strong' ? 'selected' : '' ?>>
                                                Qara</option>
                                        </select>
                                    </div>


                                    <div class="col-md-2">
                                        <label class="form-label">İstehsal itkisi (%)</label>
                                        <input type="number" class="form-control" name="loss" step="0.0001" min="0"
                                            value="<?= $recipe['loss'] ?>">
                                    </div>

                                </div>

                                <hr>

                                <h5>Xammallar</h5>

                                <?php

                                $raws = $pdo->query("
                                    SELECT *
                                    FROM raw_materials
                                    WHERE type='raw'
                                    ORDER BY name ASC, stock DESC
                                ")->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php foreach ($items as $item): ?>

                                    <div class="raw-row row mb-2">

                                        <div class="col-md-8">

                                            <select class="form-control" name="raw_name[]">

                                                <?php foreach ($raws as $raw): ?>

                                                    <option value="<?= $raw['name'] ?>"
                                                        <?= $raw['name'] == $item['raw_material_name'] ? 'selected' : '' ?>>

                                                        <?= htmlspecialchars($raw['name'] . ' | Stok: ' . $raw['stock'] . ' | Qiymət: ' . number_format($raw['price'], 2) . ' ₼ | ' . strftime('%d.%m.%Y', strtotime($raw['in_stock']))) ?>

                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>

                                        <div class="col-md-3">

                                            <input type="number" class="form-control percentage-input" name="percentage[]"
                                                value="<?= $item['percentage'] ?>">

                                        </div>

                                        <div class="col-md-1">

                                            <button class="btn btn-danger remove-row" type="button">
                                                <i class="fas fa-xmark"></i>
                                            </button>

                                        </div>

                                    </div>

                                <?php endforeach; ?>


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
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOm₼XBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
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

                fetch("./ajax/edit_sauce_recipe.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {

                            Swal.fire({
                                title: "Resept uğurla yeniləndi",
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

        document.querySelectorAll('.percentage-input').forEach(input => {
            input.addEventListener('input', updateTotalPercent);
        });

        updateTotalPercent();

        document.querySelectorAll('.raw-row .remove-row').forEach(button => {

            button.addEventListener('click', function () {

                this.closest('.raw-row').remove();

                updateTotalPercent();

            });

        });

        function addRawRow() {

            let options = '';

            raws.forEach(raw => {

                options += `
            <option value="${raw.name}">
                ${raw.name} | Stok: ${parseFloat(raw.stock).toFixed(2)} | Qiymət: ${parseFloat(raw.price).toFixed(2)} ₼ | ${formatDate(raw.in_stock)}
            </option>
        `;

            });

            const row = document.createElement('div');

            row.className = 'row mb-2 raw-row';

            row.innerHTML = `

        <div class="col-md-8">

            <select
                class="form-control raw-name"
                name="raw_name[]">

                ${options}

            </select>

        </div>

        <div class="col-md-3">

            <input
                type="number"
                step="0.0001"
                min="0"
                class="form-control percentage-input"
                name="percentage[]"
                placeholder="%">

        </div>

        <div class="col-md-1">

            <button
                type="button"
                class="btn btn-danger remove-row">
                <i class='fas fa-xmark'></i>
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

    </script>
</body>

</html>