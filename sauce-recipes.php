<?php require_once('inc/check_session.php'); ?>
<?php

require 'inc/db.php';

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
                    <h1 class="mt-4">Sous reseptləri</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Sous reseptləri</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Sous reseptlərin tərkibi
                            </span>


                            <a class="btn btn-success" href="create-sauce-recipe.php">
                                <i class="fas fa-plus"></i>
                                Sous resepti əlavə et
                            </a>
                        </div>
                        <div class="card-body">

                            <div class="accordion" id="recipesAccordion">

                                <?php foreach ($recipes as $recipe): ?>

                                    <?php
                                    $badgeClass = $recipe['type'] === 'premium'
                                        ? 'bg-danger'
                                        : 'bg-dark';

                                    $badgeText = $recipe['type'] === 'premium'
                                        ? 'Qırmızı'
                                        : 'Qara';
                                    ?>

                                    <div class="accordion-item">

                                        <h2 class="accordion-header">

                                            <style>
                                                .accordion-button::after {
                                                    filter: invert(1);
                                                }
                                            </style>

                                            <button class="accordion-button collapsed text-light <?= $badgeClass ?> 
                                                       d-flex justify-content-between align-items-center" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#recipe<?= $recipe['id'] ?>">



                                                <span class="d-flex gap-5">

                                                    <b style="width: 400px">
                                                        <?= htmlspecialchars($recipe['name']) ?>
                                                    </b>



                                                </span>



                                            </button>

                                        </h2>

                                        <div id="recipe<?= $recipe['id'] ?>" class="accordion-collapse collapse"
                                            data-bs-parent="#recipesAccordion">

                                            <div class="accordion-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    Yaradılıb: <?= date('d.m.Y H:i', strtotime($recipe['created_at'])); ?>

                                                    <span class="d-flex gap-3">
                                                        <a href="sauce-recipe-edit.php?id=<?= $recipe['id'] ?>"
                                                            class=" text-primary ms-3 text-decoration-none">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <a href="ajax/delete_sauce_recipe.php?srid=<?= $recipe['id']; ?>"
                                                            class="text-danger ms-2 text-decoration-none delete-btn"
                                                            type="button">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </span>
                                                </div>


                                                <hr>

                                                <table class="table table-sm table-bordered align-middle w-50">

                                                    <thead>
                                                        <tr>
                                                            <th>Xammal</th>
                                                            <th width="120">Faiz</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>

                                                        <?php foreach ($recipe['items'] as $item): ?>

                                                            <tr>
                                                                <td><?= htmlspecialchars($item['raw_material_name']) ?></td>
                                                                <td><?= number_format($item['percentage'], 2) ?>%</td>
                                                            </tr>

                                                        <?php endforeach; ?>

                                                    </tbody>

                                                </table>

                                                <div class="text-end">

                                                    <strong>
                                                        Cəm:
                                                        <?= array_sum(array_column($recipe['items'], 'percentage')) ?>%
                                                    </strong>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>
                    </div>
                </div>
            </main>
            <?php require_once('inc/footer.php'); ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="assets/js/scripts.js"></script>
    <script src="assets/js/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="assets/js/datatables-simple-demo.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("click", function (e) {
            const btn = e.target.closest(".delete-btn");
            if (!btn) return;

            e.preventDefault();
            const deleteUrl = btn.getAttribute("href");

            Swal.fire({
                title: "Silmək istədiyinizə əminsiniz?",
                text: "Bu əməliyyat geri qaytarıla bilməz!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Bəli, sil",
                cancelButtonText: "Ləğv et"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = deleteUrl;
                }
            });
        });
    </script>
</body>

</html>