<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Liste des étudiants</title>
    <link rel="stylesheet" href="/assets/css/global.css">
</head>
<body class="page-flow">
    <div class="page-shell">
        <div class="page-header">
            <span class="heading">Liste des étudiants</span>
            <a href="/deconnexion" class="logout-btn">Déconnexion</a>
        </div>

        <form method="get" class="form-container search-bar">
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" name="nom" id="nom" class="input" placeholder="Nom (partiel)" value="<?= esc($criteria['nom']) ?>">
            </div>
            <div class="form-group">
                <label for="etu">ETU</label>
                <input type="text" name="etu" id="etu" class="input" placeholder="Numéro ETU (partiel)" value="<?= esc($criteria['etu']) ?>">
            </div>
            <button type="submit" class="send-button">Filtrer</button>
            <?php if ($criteria['nom'] !== '' || $criteria['etu'] !== ''): ?>
                <a href="/liste" class="back-link">Réinitialiser</a>
            <?php endif; ?>
        </form>

        <?php if (! empty($results)): ?>
            <?php $columns = array_keys($results[0]); ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <th><?= esc(strtoupper(str_replace('_', ' ', $column))) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <td><?= esc((string) ($row[$column] ?? '')) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty"><?= ($criteria['nom'] !== '' || $criteria['etu'] !== '') ? 'Aucun étudiant ne correspond à votre recherche.' : 'Aucun étudiant enregistré.' ?></p>
        <?php endif; ?>

        <?php if ($total > 0): ?>
            <?php
                $base = array_filter([
                    'nom' => $criteria['nom'],
                    'etu' => $criteria['etu'],
                ], static fn ($v) => $v !== '');

                $pageUrl = static fn (int $p): string => '/liste?' . http_build_query($base + ['page' => $p]);
                $start   = max(1, $page - 2);
                $end     = min($totalPages, $page + 2);
                $first   = ($page - 1) * $perPage + 1;
                $last    = min($total, $page * $perPage);
            ?>
            <div class="pagination-info"><?= $first ?>–<?= $last ?> sur <?= $total ?> étudiant<?= $total > 1 ? 's' : '' ?></div>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <a class="page-link <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page <= 1 ? '#' : esc($pageUrl($page - 1), 'attr') ?>">Précédent</a>

                    <?php if ($start > 1): ?>
                        <a class="page-link" href="<?= esc($pageUrl(1), 'attr') ?>">1</a>
                        <?php if ($start > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>
                        <a class="page-link <?= $p === $page ? 'active' : '' ?>" href="<?= esc($pageUrl($p), 'attr') ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($end < $totalPages): ?>
                        <?php if ($end < $totalPages - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
                        <a class="page-link" href="<?= esc($pageUrl($totalPages), 'attr') ?>"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <a class="page-link <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : esc($pageUrl($page + 1), 'attr') ?>">Suivant</a>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

        <a href="/accueil" class="back-link">&larr; Retour à l'accueil</a>
    </div>
</body>
</html>
