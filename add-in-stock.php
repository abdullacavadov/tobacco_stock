<?php require_once('inc/db.php'); ?>
<?php require_once('inc/check_session.php'); ?>

<?php $products = $pdo->query(" SELECT * FROM raw_materials ORDER BY name ASC")->fetchAll(); ?>

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
                    <h1 class="mt-4">Xammal qəbulu</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="raw.php">Xammallar</a></li>
                        <li class="breadcrumb-item active">Xammal qəbulu</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Anbar mədaxil
                            </span>


                            <a class="btn btn-success" href="raw.php">
                                <i class="fas fa-list"></i>
                                Xammallar
                            </a>
                        </div>
                        <div class="card-body">
                            <form id="raw_add">

                                <div class="row mb-3">
                                    <div class="col-10">

                                        <div class="form-floating mb-3 mb-md-0" id="selectable">
                                            <select class="form-control" id="nameSelect" name="name">
                                                <?php foreach ($products as $item): ?>

                                                    <?php

                                                    if ($item['type'] == 'raw') {
                                                        $type = 'xammal';
                                                    } elseif ($item['type'] == 'flavour') {
                                                        $type = 'aromat';
                                                    } elseif ($item['type'] == 'package') {
                                                        $type = 'qab';
                                                    } elseif ($item['type'] == 'label') {
                                                        $type = 'etiket';
                                                    }

                                                    ?>
                                                    <option value="<?= $item['name']; ?>">
                                                        <?= $item['name'] . ' (' . $type . ')'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="nameSelect">Məhsul</label>
                                        </div>

                                        <div class="form-floating" id="manually" style="display:none;">
                                            <input class="form-control" id="nameInput" name="custom_name" type="text"
                                                placeholder="Xammal adı">
                                            <label for="nameInput">Məhsul adı</label>
                                        </div>

                                    </div>

                                    <div class="col-2">
                                        <button type="button" id="changer"
                                            class="btn btn-primary d-flex align-items-center justify-content-center"
                                            style="height:58px;width:100%;font-size:24px">
                                            +
                                        </button>
                                    </div>

                                </div>


                                <div class="row mb-3">
                                    <div class="col-12">

                                        <div class="form-floating mb-3 mb-md-0">
                                            <select class="form-control" id="type" name="type">
                                                <option value="">-- seç --</option>
                                                <option value="raw">Xammal</option>
                                                <option value="flavour">Aroma</option>
                                                <option value="package">Qab</option>
                                                <option value="label">Etiket</option>
                                            </select>
                                            <label for="type">Növü</label>
                                        </div>
                                    </div>



                                </div>




                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-floating">
                                            <input class="form-control" id="stock" name="stock" type="number"
                                                step="0.0001" min="0.0001" placeholder="100 kq" />
                                            <label for="stock">Həcm (kq / əd)</label>
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="price" type="number" name="price"
                                                step="0.0001" min="0.0001" placeholder="100 ₼" />
                                            <label for="price">Qiymət (AZN)</label>
                                        </div>
                                    </div>
                                </div>





                                <div class="mt-4 mb-0">
                                    <div class="d-grid"><button type="submit" class="btn btn-primary btn-block">Əlavə
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

            const form = document.getElementById("raw_add");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/add_raw.php", {
                    method: "POST",
                    body: formData
                })

                    .then(response => response.text())

                    .then(response => {

                        if (response.trim() === "success") {

                            Swal.fire({
                                title: "Xammal anbara əlavə edildi.",
                                icon: "success",
                                draggable: true
                            });
                            form.reset();
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


    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const changer = document.getElementById('changer');
            const selectable = document.getElementById('selectable');
            const manually = document.getElementById('manually');

            let manualMode = false;

            changer.addEventListener('click', () => {

                manualMode = !manualMode;

                if (manualMode) {

                    selectable.style.display = 'none';
                    manually.style.display = 'block';

                    changer.textContent = 'x';
                    changer.classList.remove('btn-primary')
                    changer.classList.add('btn-danger')

                } else {

                    selectable.style.display = 'block';
                    manually.style.display = 'none';

                    changer.textContent = '+';
                    changer.classList.remove('btn-danger')
                    changer.classList.add('btn-primary')
                }

            });

        });
    </script>
</body>

</html>