<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark mt-5" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

            <style>
                .sb-nav-link-icon{
                    width: 20px;
                    text-align: left;
                }
            </style>
                <a class="nav-link" href="index.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>

                <a class="nav-link" href="raw.php">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-warehouse"></i></div>
                    Xammal
                </a>

                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts2"
                    aria-expanded="false" aria-controls="collapseLayouts2">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-flask"></i></div>
                    Souslar
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseLayouts2" aria-labelledby="headingOne"
                    data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="sauce-recipes.php">Sous reseptləri</a>
                        <a class="nav-link" href="sauces.php">Sous stoku</a>
                    </nav>
                </div>

                <a class="nav-link" href="sample-sauce.php">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-trash-can"></i></div>
                    İstehsalat itkisi
                </a>

                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts"
                    aria-expanded="false" aria-controls="collapseLayouts3">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-spray-can-sparkles"></i></div>
                    Dadlandırma
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseLayouts3" aria-labelledby="headingOne"
                    data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="recipes.php">Reseptlər</a>
                        <a class="nav-link" href="flavours.php">Hazır dadlar</a>
                    </nav>
                </div>

                <a class="nav-link" href="products.php">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-box-open"></i></div>
                    Qablaşdırma
                </a>


                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts"
                    aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-manat-sign"></i></div>
                    Satış
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne"
                    data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="raw-orders.php">Xammal</a>
                        <a class="nav-link" href="sauce-orders.php">Sous</a>
                        <a class="nav-link" href="flavour-orders.php">Aromatlı sous</a>
                        <a class="nav-link" href="orders.php">Qablaşdırılmış məhsul</a>
                    </nav>
                </div>

                <a class="nav-link" href="statistics.php">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-chart-line"></i></div>
                    Statistika
                </a>

                <a class="nav-link btn btn-danger" href="./ajax/logout.php">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
                    Çıxış
                </a>


            </div>
        </div>
    </nav>
</div>