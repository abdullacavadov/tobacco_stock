<?php require_once('inc/check_session.php'); ?>
<?php require_once('inc/db.php'); ?>

<?php

$stocks = [

    'raw' => [],
    'sauce' => [],
    'flavour' => [],
    'product' => []

];

/*
|--------------------------------------------------------------------------
| RAW
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
SELECT
    id,
    name,
    stock,
    unit,
    price,
    in_stock
FROM raw_materials
WHERE stock>0
ORDER BY name,in_stock
");

$stocks['raw'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| SAUCE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
SELECT
    id,
    type,
    stock,
    price,
    created_at
FROM sauce_stock
WHERE stock>0
ORDER BY created_at
");

$stocks['sauce'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| FLAVOUR
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
SELECT
    id,
    flavour_name,
    sauce_type,
    qty,
    cost,
    created_at
FROM sauce_with_flavour
WHERE qty>0
ORDER BY created_at
");

$stocks['flavour'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PRODUCT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
SELECT
    id,
    name,
    weight,
    stock,
    type,
    price,
    production_date
FROM products
WHERE stock>0
ORDER BY production_date
");

$stocks['product'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="az">

<head>
    <?php require_once('inc/head.php'); ?>

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="sb-nav-fixed">

    <?php require_once('inc/navbar.php'); ?>

    <div id="layoutSidenav">

        <?php require_once('inc/sidebar.php'); ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Nümunə sous hazırlanması</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="sauces.php">Souslar</a></li>
                        <li class="breadcrumb-item active">Nümunə sous hazırlanması</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Nümunə sous istehsalı
                            </span>


                            <a class="btn btn-success" href="sample-sauce.php">
                                <i class="fas fa-list"></i>
                                Nümunə souslar
                            </a>
                        </div>
                        <div class="card-body">

                            <form id="productionLossForm">

                                <div class="row">

                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-control" name="reason">

                                                <option value="test">Yeni dad sınağı</option>
                                                <option value="quality_control">Keyfiyyət yoxlanışı</option>
                                                <option value="waste">İstehsal itkisi</option>
                                                <option value="damaged">Zədələnmiş</option>
                                                <option value="other">Digər</option>

                                            </select>

                                            <label>Səbəb</label>
                                        </div>
                                    </div>

                                    <div class="col-md-8">

                                        <div class="form-floating">

                                            <input type="text" class="form-control" name="note"
                                                placeholder="Qeyd (Məs: 13.07.26 - Dejavu 1ci dad sınağı)">

                                            <label>Qeyd (Məs: 13.07.26 - Dejavu 1 dad sınağı)</label>

                                        </div>

                                    </div>

                                </div>

                                <hr>


                                <div id="materialsContainer"></div>

                                <div class="alert alert-warning">
                                    Məhsulun yanında qeyd edilən qiymət vahidin (1kq / 1 ədəd) qiymətidir. İstifadə
                                    edilən xammalın miqdarı ilə vurularaq ümumi xərci hesablanacaq.
                                </div>

                                <button type="button" class="btn btn-success mt-2" id="addMaterial">

                                    <i class="fas fa-plus"></i> Xammal əlavə et

                                </button>

                                <div class="d-grid mt-4">

                                    <button type="submit" class="btn btn-primary">

                                        İtkini qeyd et

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

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>


    <script>
        const stocks =
            <?= json_encode($stocks, JSON_UNESCAPED_UNICODE) ?>;


        function addRow() {

            const row = document.createElement("div");

            row.className = "row mb-2 material-row";

            row.innerHTML = `<div class="col-md-3"> 
                                <select class="form-control warehouse-type" name="source[]"> 
                                    <option value="">-- Anbar seç --</option> 
                                    <option value="raw"> Xammal </option> 
                                    <option value="sauce"> Hazır sous </option> 
                                    <option value="flavour"> Dadlandırılmış sous </option> 
                                    <option value="product"> Hazır məhsul </option> 
                                </select> </div> <div class="col-md-5"> 
                                
                                <select class="form-control material-select" name="item[]"> </select> 
                            </div> 
                            
                            <div class="col-md-2"> 
                                <input class="form-control" type="number" step="0.01" min="0.01" name="qty[]"> 
                            </div> 

                            <div class="col-md-2"> 
                                <button type="button" class="btn btn-danger removeRow"> 
                                    <i class="fas fa-trash"></i>
                                </button> 
                            </div>`;

            container.appendChild(row);

            const warehouse = row.querySelector(".warehouse-type");
            const material = row.querySelector(".material-select");

            warehouse.addEventListener("change", function () {

                if (material.tomselect) {
                    material.tomselect.destroy();
                }

                material.innerHTML = "";

                const list = stocks[this.value] || [];

                list.forEach(item => {

                    let text = "";

                    switch (this.value) {

                        case "raw":
                            try {
                                const d = new Date(item.in_stock);
                                const dd = String(d.getDate()).padStart(2, '0');
                                const mm = String(d.getMonth() + 1).padStart(2, '0');
                                const yy = String(d.getFullYear()).slice(-2);
                                const hh = String(d.getHours()).padStart(2, '0');
                                const ii = String(d.getMinutes()).padStart(2, '0');
                                const formatted = `${dd}.${mm}.${yy} ${hh}:${ii}`;
                                text = `${item.name} | ${parseFloat(item.stock).toFixed(2)} ${item.unit} | ${formatted} | ${parseFloat(item.price).toFixed(2)} ₼`;
                            } catch (e) {
                                text = `${item.name} | ${parseFloat(item.stock).toFixed(2)} ${item.unit} | ${item.in_stock} | ${parseFloat(item.price).toFixed(2)} ₼`;
                            }
                            break;

                        case "sauce":
                            // format created_at as dd.mm.yy h:i
                            try {
                                const d = new Date(item.created_at);
                                const dd = String(d.getDate()).padStart(2, '0');
                                const mm = String(d.getMonth() + 1).padStart(2, '0');
                                const yy = String(d.getFullYear()).slice(-2);
                                const hh = String(d.getHours()).padStart(2, '0');
                                const ii = String(d.getMinutes()).padStart(2, '0');
                                const formatted = `${dd}.${mm}.${yy} ${hh}:${ii}`;
                                text = `${item.type} | ${parseFloat(item.stock).toFixed(2)} kq | ${formatted}`;
                            } catch (e) {
                                text = `${item.type} | ${parseFloat(item.stock).toFixed(2)} kq | ${item.created_at}`;
                            }
                            break;

                        case "flavour":
                            text = `${item.flavour_name} (${item.sauce_type}) | ${parseFloat(item.qty).toFixed(2)} kq`;
                            break;

                        case "product":
                            text = `${item.name} ${item.weight} kq | ${item.stock} əd`;
                            break;
                    }

                    material.add(new Option(text, item.id));
                });

                new TomSelect(material, {
                    create: false,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    }
                });

            });

            row.querySelector(".removeRow").addEventListener("click", function () {

                if (material.tomselect) {
                    material.tomselect.destroy();
                }

                row.remove();

            });

        }

        const container = document.getElementById("materialsContainer");

        document
            .getElementById("addMaterial")
            .addEventListener("click", addRow);

        addRow();
    </script>

    <script>
        document
            .getElementById("productionLossForm")
            .addEventListener("submit", function (e) {

                e.preventDefault();

                const form = this;

                const selectReason = form.querySelector("select[name='reason']");

                const btn = form.querySelector("button[type='submit']");

                btn.disabled = true;

                fetch("ajax/create_production_loss.php", {

                    method: "POST",

                    body: new FormData(form)

                })

                    .then(r => r.json())

                    .then(data => {

                        if (data.success) {

                            Swal.fire({

                                icon: "success",

                                title: selectReason.options[selectReason.selectedIndex].text + " qeyd edildi"

                            }).then(() => {

                                window.location.reload();

                            });

                        } else {

                            Swal.fire({

                                icon: "error",

                                title: "Xəta",

                                text: data.message

                            });

                        }

                    })

                    .catch(() => {

                        Swal.fire({

                            icon: "error",

                            title: "Server xətası"

                        });

                    })

                    .finally(() => {

                        btn.disabled = false;

                    });

            });
    </script>

</body>

</html>