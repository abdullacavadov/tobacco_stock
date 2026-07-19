<?php require_once('inc/check_session.php'); ?>
<?php require_once('inc/db.php');

if (!isset($_GET['fid'])) {
    header("Location: flavours.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT *
    FROM sauce_with_flavour
    WHERE id = ?
");
$stmt->execute([$_GET['fid']]);
$sauce = $stmt->fetch(PDO::FETCH_ASSOC);

$sauceName = $sauce['flavour_name'];
$sauceQty = $sauce['qty'];
$sauceType = $sauce['sauce_type'];
$sauceCost = $sauce['cost'];


$stmt = $pdo->query("
    SELECT
        r.id,
        r.name,
        r.created_at,
        r.sauce_type,
        i.flavour_name,
        i.percentage
    FROM flavour_recipes r
    LEFT JOIN flavour_recipe_items i
        ON i.recipe_id = r.id
    ORDER BY r.name, i.flavour_name
");



$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


$recipes = [];

foreach ($rows as $row) {

    $recipeId = $row['id'];
    $recipeName = $row['name'];

    if (!isset($recipes[$recipeId])) {

        $recipes[$recipeId] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'sauce_type' => $row['sauce_type'],
            'created_at' => $row['created_at'],
            'items' => []
        ];

    }

    if (!empty($row['flavour_name'])) {

        $recipes[$recipeId]['items'][] = [
            'flavour_name' => $row['flavour_name'],
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
                    <h1 class="mt-4">Sous dadlandırma</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="flavours.php">Hazır məhsullar</a></li>
                        <li class="breadcrumb-item active">Dadlandırılmış sous tənzimləmələri</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Sous dadlandırma redaktəsi
                            </span>


                            <a class="btn btn-success" href="flavours.php">
                                <i class="fas fa-list"></i>
                                Hazır məhsullar
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="edit_flavour">

                            <input type="hidden" name="fid" value="<?= htmlspecialchars($_GET['fid']) ?>">


                                <div class="row">

                                    <div class="col-md-12 mb-3">
                                        <div class="form-floating">
                                            <select class="form-control" id="recipe_id" name="recipe_id">

                                                <?php foreach ($recipes as $recipe): ?>

                                                    <option value="<?= htmlspecialchars($recipe['id']) ?>"
                                                    <?php if ($sauceName == $recipe['name']) echo ' selected'; ?>
                                                    >
                                                        <?= htmlspecialchars($recipe['name']) ?>
                                                        (<?php foreach ($recipe['items'] as $item): ?>

                                                            <span>
                                                                <?= htmlspecialchars($item['flavour_name']) . ' - ' . number_format($item['percentage'], 2) . '%' ?>

                                                            </span>

                                                        <?php endforeach; ?>)

                                                    </option>

                                                <?php endforeach; ?>
                                            </select>
                                            <label for="recipe_id">Dad</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-control" id="sauce_type" name="sauce_type">
                                                <option value="premium" <?php if ($sauceType == 'premium') echo ' selected'; ?>>Qırmızı</option>
                                                <option value="strong" <?php if ($sauceType == 'strong') echo ' selected'; ?>>Qara</option>
                                            </select>
                                            <label for="sauce_type">Növü</label>
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input class="form-control" id="qty" name="qty" type="number"
                                                step="0.01" placeholder="100 kq" value="<?= htmlspecialchars($sauceQty) ?>"/>
                                            <label for="qty">Həcm (kq)</label>
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input class="form-control" id="cost" name="cost" type="number"
                                                step="0.01" value="<?= number_format($sauceCost, 2, '.', '') ?>"/>
                                            <label for="cost">Ümumi həcmin qiyməti (AZN)</label>
                                        </div>
                                    </div>

                                </div>





                                <div class="mt-4 mb-0">
                                    <div class="d-grid"><button type="submit" class="btn btn-primary btn-block">Redaktə
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

            const form = document.getElementById("edit_flavour");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/edit_flavour.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {

                            Swal.fire({
                                title: "Məhsul redaktə edildi.",
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