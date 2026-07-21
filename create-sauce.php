<?php require_once('inc/check_session.php'); ?>
<?php require_once('inc/db.php'); ?>


<?php
$stmt = $pdo->query("
    SELECT
        r.id,
        r.name,
        r.created_at,
        r.type,
        i.raw_material_name,
        i.percentage
    FROM sauce_recipes r
    LEFT JOIN sauce_recipe_items i
        ON i.recipe_id = r.id
    ORDER BY r.name, i.raw_material_name
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


$recipes = [];

foreach ($rows as $row) {

    $recipeId = $row['id'];

    if (!isset($recipes[$recipeId])) {

        $recipes[$recipeId] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'type' => $row['type'],
            'created_at' => $row['created_at'],
            'items' => []
        ];

    }

    if (!empty($row['raw_material_name'])) {

        $recipes[$recipeId]['items'][] = [
            'raw_material_name' => $row['raw_material_name'],
            'percentage' => $row['percentage']
        ];

    }

}
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
                    <h1 class="mt-4">Sous hazırlanması</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="sauces.php">Souslar</a></li>
                        <li class="breadcrumb-item active">Sous hazırlanması</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Sous istehsalı
                            </span>


                            <a class="btn btn-success" href="sauces.php">
                                <i class="fas fa-list"></i>
                                Souslar
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="create_sauce">


                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-floating mb-3">
                                            <select class="form-control" id="recipe_id" name="recipe_id">

                                                <?php foreach ($recipes as $recipe): ?>

                                                    <option value="<?= htmlspecialchars($recipe['id']) ?>">
                                                        <?= htmlspecialchars($recipe['name']) ?> - <strong class="text-uppercase"><?= $recipe['type'] ?></strong>
                                                        (<?php foreach ($recipe['items'] as $item): ?>

                                                            <span>
                                                                <?= htmlspecialchars($item['raw_material_name']) . ' - ' . number_format($item['percentage'], 2) . '%' ?>

                                                            </span>

                                                        <?php endforeach; ?>)

                                                    </option>

                                                <?php endforeach; ?>
                                            </select>
                                            <label for="recipe_id">Sous resepti</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-control" id="type" name="type">
                                                <option value="premium" selected>Qırmızı</option>
                                                <option value="strong">Qara</option>
                                            </select>
                                            <label for="stock">Növü</label>
                                        </div>
                                    </div>


                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input class="form-control" id="stock" name="stock" type="number"
                                                step="0.0001" min="0.0001" placeholder="100 kq" />
                                            <label for="stock">Həcm (kq)</label>
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

            const form = document.getElementById("create_sauce");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/create_sauce.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {

                            Swal.fire({
                                title: "Sous anbara əlavə edildi.",
                                icon: "success"
                            });

                            form.reset();
                            document.getElementById('recipePreview').innerHTML = '';


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
        function loadRecipePreview() {

            let type = document.getElementById('type').value;
            let stock = document.getElementById('stock').value;
            let recipeId = document.getElementById('recipe_id').value;

            let recipeStock = parseFloat(stock);

            if (type === 'strong') {
                recipeStock = recipeStock / 0.85;
            }

            if (!stock || stock <= 0) {
                document.getElementById('recipePreview').innerHTML = '';
                return;
            }

            let formData = new FormData();
            formData.append('type', type);
            formData.append('stock', stock);
            formData.append('recipe_id', recipeId);

            fetch('./ajax/calculate_sauce.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {

                    if (!data.success) {

                        document.getElementById('recipePreview').innerHTML =
                            `<div class="alert alert-danger">${data.message}</div>`;

                        return;
                    }

                    let html = `
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Xammal</th>
                                    <th>Partiya tarixi</th>
                                    <th>Tələb olunan</th>
                                    <th>1 kq qiymət</th>
                                    <th>Məbləğ</th>
                                    <th>Qalıq</th>
                                </tr>
                            </thead>
                            <tbody>
                        `;

                    data.rows.forEach(row => {

                        html += `
                <tr>
                    <td>${row.name}</td>
                    <td>${row.in_stock}</td>
                    <td>${row.used}</td>
                    <td>${row.price}</td>
                    <td>${row.total_price}</td>
                    <td>${row.remaining}</td>
                </tr>
            `;
                    });

                    html += `
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4">Cəmi</th>
                    <th>${data.total_cost} AZN</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        `;
                    let infoHtml = '';

                    if (data.loss > 0) {
                        infoHtml = `
                            <div class="alert alert-warning">
                                <strong>Qeyd:</strong><br>
                                Hazır məhsul: ${data.finished_kg} kq<br>
                                Hesablama üçün istifadə olunan qarışıq: ${data.recipe_kg} kq<br>
                                (${data.loss}% istehsal itkisi nəzərə alınıb.)
                            </div>
                        `;
                    }

                    document.getElementById('recipePreview').innerHTML = infoHtml + html;

                });

        }

        document.getElementById('type')
            .addEventListener('change', loadRecipePreview);

        document.getElementById('stock')
            .addEventListener('input', loadRecipePreview);
    </script>
</body>

</html>