<?php require_once('inc/db.php'); ?>

<!DOCTYPE html>
<html lang="az">

<head>
    <?php require_once('inc/head.php'); ?>
</head>

<body class="bg-primary">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header">
                                    <h3 class="text-center font-weight-light my-4">Login</h3>
                                </div>
                                <div class="card-body">
                                    <form id="login">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="username" name="username" type="text"
                                                placeholder="admin" />
                                            <label for="username">İstifadəçi adı</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="password" name="password" type="password"
                                                placeholder="Password" />
                                            <label for="password">Şifrə</label>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between mt-4 mb-0">

                                            <button type="submit" class="btn btn-primary">Daxil ol</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div id="layoutAuthentication_footer">
            <?php require_once('inc/footer.php'); ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="assets/js/scripts.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const form = document.getElementById("login");

            form.addEventListener("submit", function (e) {

                e.preventDefault();

                const submitBtn = form.querySelector("button[type='submit']");
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch("./ajax/login.php", {
                    method: "POST",
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {

                            Swal.fire({
                                icon: "success",
                                title: "Məlumatlar doğrudur. Yönləndirilir...",
                                timer: 2500,
                                showConfirmButton: false
                            }).then(() => {

                                window.location.href = "index.php";

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
                            title: "Server xətası",
                            text: "Zəhmət olmasa yenidən cəhd edin."
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