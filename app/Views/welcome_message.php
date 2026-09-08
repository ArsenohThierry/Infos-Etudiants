<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion</title>
    <link rel="stylesheet" href="/assets/css/global.css">
</head>
<body>
    <div class="form-container">
        <span class="heading">RohySafe</span>
        <span class="subtitle">Portail de recherche d'informations</span>

        <?php if (isset($error)): ?>
            <div class="alert-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Identifiant</label>
                <input type="text" name="username" id="username" class="input" placeholder="" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" name="password" id="password" class="input" placeholder="••••••••" required>
            </div>
            <input type="submit" value="Se connecter" class="send-button-full">
        </form>
    </div>
</body>
</html>
