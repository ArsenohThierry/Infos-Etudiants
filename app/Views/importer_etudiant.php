<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Importer des étudiants</title>
    <link rel="stylesheet" href="/assets/css/global.css">
</head>
<body>
    <div class="form-container wide">
        <span class="heading">Importer des étudiants</span>
        <span class="subtitle">Fichier Excel (.xlsx) — toutes les feuilles sont traitées</span>

        <?php if (! empty($error)): ?>
            <div class="alert-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <?php if (! empty($report)): ?>
            <?php
                $total = $report['total_importes'] + $report['total_doublons'] + $report['total_ignores'];
            ?>
            <div class="success-banner show">
                Import terminé : <?= (int) $report['total_importes'] ?> étudiant(s) ajouté(s),
                <?= (int) $report['total_doublons'] ?> doublon(s) ignoré(s),
                <?= (int) $report['total_ignores'] ?> ligne(s) ignorée(s)
                sur <?= (int) $total ?>.
            </div>

            <?php if (! empty($report['erreur_partielle'])): ?>
                <div class="alert-error"><?= esc($report['erreur_partielle']) ?></div>
            <?php endif; ?>

            <div class="table-wrap report-table">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Feuille</th>
                            <th>Statut</th>
                            <th>Importés</th>
                            <th>Doublons</th>
                            <th>Ignorés</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['sheets'] as $stat): ?>
                            <tr>
                                <td><?= esc($stat['name']) ?></td>
                                <td><span class="<?= $stat['reconnu'] ? 'status-ok' : 'status-skip' ?>"><?= $stat['reconnu'] ? 'OK' : 'Non reconnue' ?></span></td>
                                <td><?= (int) $stat['importes'] ?></td>
                                <td><?= (int) $stat['doublons'] ?></td>
                                <td><?= (int) $stat['ignores'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="fichier">Fichier Excel</label>
                <input type="file" name="fichier" id="fichier" class="input input-file" accept=".xlsx" required>
                <div class="form-help">Colonnes attendues : Id (ETU), Nom, Prénom — fusionnés en un seul champ « nom » lors de l'insertion.</div>
            </div>

            <input type="submit" value="Importer" class="send-button-full">
        </form>

        <a href="/accueil" class="back-link">&larr; Retour à l'accueil</a>
    </div>
</body>
</html>
