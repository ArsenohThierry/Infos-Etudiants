<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recherche d'étudiants</title>
    <link rel="stylesheet" href="/assets/css/global.css">
    <script>
    function openModal(id, nom, mac, hostname, mdp) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nom_display').textContent = nom;
        document.getElementById('edit_mac').value = mac;
        document.getElementById('edit_hostname').value = hostname;
        document.getElementById('edit_mdp').value = mdp;
        document.getElementById('modal').classList.add('show');
    }
    function closeModal() {
        document.getElementById('modal').classList.remove('show');
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    });
    </script>
</head>
<body class="page-flow">
    <div class="page-shell">
        <div class="page-header">
            <span class="heading">Recherche multi-critères</span>
            <a href="/deconnexion" class="logout-btn">Déconnexion</a>
        </div>

        <div class="success-banner <?= $modifier_success ? 'show' : '' ?>"><?= esc($modifier_success) ?></div>

        <form method="post" class="form-container search-bar">
            <div class="form-group">
                <label for="id_etudiant">ID</label>
                <input type="number" name="id_etudiant" id="id_etudiant" class="input" placeholder="ID étudiant" value="<?= set_value('id_etudiant') ?>">
            </div>
            <div class="form-group">
                <label for="etu">ETU</label>
                <input type="number" name="etu" id="etu" class="input" placeholder="Numéro ETU" value="<?= set_value('etu') ?>">
            </div>
            <div class="form-group">
                <label for="hostname">Hostname</label>
                <input type="text" name="hostname" id="hostname" class="input" placeholder="Hostname" value="<?= set_value('hostname') ?>">
            </div>
            <div class="form-group">
                <label for="mac">MAC</label>
                <input type="text" name="mac" id="mac" class="input" placeholder="Adresse MAC" value="<?= set_value('mac') ?>">
            </div>
            <div class="form-group">
                <label for="mdp">Mot de passe</label>
                <input type="text" name="mdp" id="mdp" class="input" placeholder="Mot de passe" value="<?= set_value('mdp') ?>">
            </div>
            <div class="form-group">
                <label for="quota_min">Quota min</label>
                <input type="number" name="quota_min" id="quota_min" class="input" placeholder="Min" value="<?= set_value('quota_min') ?>">
            </div>
            <div class="form-group">
                <label for="quota_max">Quota max</label>
                <input type="number" name="quota_max" id="quota_max" class="input" placeholder="Max" value="<?= set_value('quota_max') ?>">
            </div>
            <input type="submit" value="Rechercher" class="send-button">
        </form>
        <a href="/accueil" class="back-link">&larr; Retour à l'accueil</a>

        <?php if (! empty($results)): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>ETU</th>
                            <th>MAC</th>
                            <th>Hostname</th>
                            <th>Mot de passe</th>
                            <th>Quota consommé</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?= $row['id_etudiant'] ?></td>
                                <td><?= esc($row['nom']) ?></td>
                                <td><?= $row['etu'] ?></td>
                                <td><span class="badge"><?= esc($row['mac']) ?></span></td>
                                <td><?= esc($row['hostname']) ?></td>
                                <td><span class="badge"><?= esc($row['mdp']) ?></span></td>
                                <td><?= round(($row['quota_consomme']/1024/1024), 2) ?> MB</td>
                                <td><button class="btn-edit" onclick="openModal(<?= $row['id_etudiant'] ?>, '<?= esc(addslashes($row['nom'])) ?>', '<?= esc($row['mac']) ?>', '<?= esc($row['hostname']) ?>', '<?= esc($row['mdp']) ?>')">Modifier</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($method === 'POST'): ?>
            <p class="empty">Aucun résultat trouvé.</p>
        <?php endif; ?>
    </div>

    <div id="modal" class="modal-overlay">
        <div class="modal">
            <span class="heading">Modifier l'étudiant</span>
            <span class="sub" id="edit_nom_display"></span>
            <form method="post" action="/modifier">
                <input type="hidden" name="id_etudiant" id="edit_id">

                <div class="form-group">
                    <label for="edit_mac">Adresse MAC</label>
                    <input type="text" name="mac" id="edit_mac" class="input">
                </div>
                <div class="form-group">
                    <label for="edit_hostname">Hostname</label>
                    <input type="text" name="hostname" id="edit_hostname" class="input">
                </div>
                <div class="form-group">
                    <label for="edit_mdp">Mot de passe</label>
                    <input type="text" name="mdp" id="edit_mdp" class="input">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn-save">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
