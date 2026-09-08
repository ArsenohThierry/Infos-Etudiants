<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ajouter un étudiant</title>
    <link rel="stylesheet" href="/assets/css/global.css">
</head>
<body>
    <div class="form-container">
        <span class="heading">Ajouter un étudiant</span>
        <span class="subtitle">Veuillez compléter le formulaire ci-dessous</span>

        <?php if (! empty($success)): ?>
            <div class="success-banner show"><?= esc($success) ?></div>
        <?php endif; ?>

        <?php if (! empty($errors['global'])): ?>
            <div class="alert-error"><?= esc($errors['global']) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="form-group">
                <label for="nom">Nom complet <span class="required">*</span></label>
                <input type="text" name="nom" id="nom" class="input<?= isset($errors['nom']) ? ' input-error' : '' ?>" placeholder="ex: Rakoto Jean" required value="<?= esc($old['nom'] ?? '') ?>">
                <?php if (isset($errors['nom'])): ?>
                    <div class="error show"><?= esc($errors['nom']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="etu">Numéro ETU <span class="required">*</span></label>
                <input type="text" name="etu" id="etu" class="input<?= isset($errors['etu']) ? ' input-error' : '' ?>" placeholder="ex: ETU004567" required value="<?= esc($old['etu'] ?? '') ?>">
                <div class="form-help">L'ETU doit contenir « ETU00 » (ex : ETU004567)</div>
                <?php if (isset($errors['etu'])): ?>
                    <div class="error show"><?= esc($errors['etu']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="mac">Adresse MAC</label>
                <input type="text" name="mac" id="mac" class="input<?= isset($errors['mac']) ? ' input-error' : '' ?>" placeholder="ex: 80:c5:f2:fa:b4:47" value="<?= esc($old['mac'] ?? '') ?>">
                <div class="form-help">Format attendu : 6 paires hexadécimales séparées par des deux-points (ex&nbsp;: 00:1A:2B:3C:4D:5E)</div>
                <?php if (isset($errors['mac'])): ?>
                    <div class="error show"><?= esc($errors['mac']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="hostname">Hostname</label>
                <input type="text" name="hostname" id="hostname" class="input<?= isset($errors['machine']) ? ' input-error' : '' ?>" placeholder="ex: PC-JEAN-01" value="<?= esc($old['hostname'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="mdp">Mot de passe</label>
                <input type="text" name="mdp" id="mdp" class="input<?= isset($errors['machine']) ? ' input-error' : '' ?>" placeholder="Mot de passe machine" value="<?= esc($old['mdp'] ?? '') ?>">
                <?php if (isset($errors['machine'])): ?>
                    <div class="error show"><?= esc($errors['machine']) ?></div>
                <?php endif; ?>
            </div>

            <input type="submit" value="Ajouter" class="send-button-full">
        </form>

        <a href="/importer" class="reset-button-full">Importer une liste (.xlsx)</a>

        <a href="/accueil" class="back-link">&larr; Retour à l'accueil</a>
    </div>
</body>
</html>
