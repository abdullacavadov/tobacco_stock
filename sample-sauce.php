<?php require_once('inc/check_session.php'); ?>
<?php

require 'inc/db.php';

$stmt = $pdo->query("
SELECT
    pl.id AS loss_id,
    pl.reason,
    pl.note,
    pl.created_at,

    pli.source,
    pli.source_id,
    pli.qty,
    pli.unit_price,
    pli.cost,

    COALESCE(
        rm.name,
        CONCAT(ss.type,' sous'),
        swf.flavour_name,
        p.name
    ) AS material_name,

    CASE
        WHEN pli.source='raw' THEN rm.unit
        WHEN pli.source='sauce' THEN 'kq'
        WHEN pli.source='flavour' THEN 'kq'
        WHEN pli.source='product' THEN 'əd'
    END AS unit

FROM production_loss pl

LEFT JOIN production_loss_items pli
    ON pli.loss_id = pl.id

LEFT JOIN raw_materials rm
    ON pli.source='raw'
    AND rm.id=pli.source_id

LEFT JOIN sauce_stock ss
    ON pli.source='sauce'
    AND ss.id=pli.source_id

LEFT JOIN sauce_with_flavour swf
    ON pli.source='flavour'
    AND swf.id=pli.source_id

LEFT JOIN products p
    ON pli.source='product'
    AND p.id=pli.source_id

ORDER BY
    pl.created_at DESC,
    pli.id ASC;
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


$production_loss = [];

foreach ($rows as $row) {

    $lossID = $row['loss_id'];

    if (!isset($production_loss[$lossID])) {

        $production_loss[$lossID] = [
            'id' => $row['loss_id'],
            'note' => $row['note'],
            'source' => $row['DB_name'],
            'reason' => $row['reason'],
            'created_at' => $row['created_at'],
            'items' => []
        ];

    }

    if (!empty($row['source_id'])) {

        $production_loss[$lossID]['items'][] = [
            'name' => $row['material_name'],
            'unit' => $row['unit'],
            'unit_price' => $row['unit_price'],
            'qty' => $row['qty'],
            'cost' => $row['cost']
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
                    <h1 class="mt-4">Nümunə Souslar</h1>


                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Nümunə Souslar</li>
                    </ol>


                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Nümunə Sousların tərkibi
                            </span>


                            <a class="btn btn-success" href="create-sample-sauce.php">
                                <i class="fas fa-plus"></i>
                                Nümunə hazırla
                            </a>
                        </div>
                        <div class="card-body">

                            <div class="accordion" id="recipesAccordion">

                                <?php foreach ($production_loss as $loss): ?>

                                    <?php
                                    if ($loss['reason'] == 'test') {
                                        $badgeClass = 'bg-primary';
                                        $badgeText = 'Test';
                                    } elseif ($loss['reason'] == 'quality_control') {
                                        $badgeClass = 'bg-warning';
                                        $badgeText = 'Keyfiyyət yoxlanışı';
                                    } elseif ($loss['reason'] == 'waste') {
                                        $badgeClass = 'bg-danger';
                                        $badgeText = 'İstehsalat itkisi';
                                    } elseif ($loss['reason'] == 'damaged') {
                                        $badgeClass = 'bg-dark';
                                        $badgeText = 'Zədələnmiş';
                                    } else {
                                        $badgeClass = 'bg-info';
                                        $badgeText = 'Digər';
                                    }

                                    ?>

                                    <div class="accordion-item">

                                        <h2 class="accordion-header">

                                            <style>
                                                .accordion-button::after {
                                                    filter: invert(1);
                                                }
                                            </style>

                                            <button class="accordion-button collapsed text-light <?= $badgeClass ?>"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#loss<?= $loss['id'] ?>">

                                                <?php
                                                $reasons = [
                                                    'test' => 'Yeni dad sınağı',
                                                    'quality_control' => 'Keyfiyyət yoxlanışı',
                                                    'waste' => 'İstehsalat itkisi',
                                                    'damaged' => 'Zədələnmiş',
                                                    'other' => 'Digər'
                                                ];
                                                ?>

                                                <strong><?= $reasons[$loss['reason']] ?></strong>

                                                <?php if ($loss['note'] != ''): ?>

                                                    &nbsp;—&nbsp;<?= htmlspecialchars($loss['note']) ?>
                                                    | <?= date('d.m.Y H:i', strtotime($loss['created_at'])); ?>

                                                <?php endif; ?>

                                            </button>

                                        </h2>

                                        <div id="loss<?= $loss['id'] ?>" class="accordion-collapse collapse"
                                            data-bs-parent="#productionLossAccordion">

                                            <div class="accordion-body">


                                                <table class="table table-sm table-bordered align-middle w-50">

                                                    <thead>
                                                        <tr>
                                                            <th>Xammal</th>
                                                            <th>Miqdar</th>
                                                            <th>Qiymət (kq/əd)</th>
                                                            <th>Ümumi dəyər</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>

                                                        <?php foreach ($loss['items'] as $item): ?>

                                                            <tr>
                                                                <td><?= htmlspecialchars($item['name']) ?></td>
                                                                <td>
                                                                    <?= number_format($item['qty'], 2) ?>
                                                                    <?= htmlspecialchars($item['unit']) ?>
                                                                </td>
                                                                <td><?= number_format($item['unit_price'], 2) ?>₼</td>
                                                                <td><?= number_format($item['cost'], 2) ?>₼</td>
                                                            </tr>

                                                        <?php endforeach; ?>

                                                    </tbody>

                                                </table>

                                                <div class="text-end">

                                                    <strong>
                                                        Cəm:
                                                        <?= array_sum(array_column($loss['items'], 'cost')) ?> ₼
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
</body>

</html>