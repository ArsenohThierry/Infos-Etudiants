<?php

namespace App\Controllers;

use App\Libraries\XlsxReader;
use App\Models\EtudiantModel;
use RuntimeException;
use Throwable;

class Home extends BaseController
{
    public function index()
    {
        if ($this->request->getMethod() === 'POST') {
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            if ($username === 'AdminRohySafe' && $password === 'AdminRohySafe') {
                session()->set('logged_in', true);
                return $this->redirectRel('/accueil');
            }

            return view('welcome_message', ['error' => 'Identifiants incorrects']);
        }

        if (session()->get('logged_in')) {
            return $this->redirectRel('/accueil');
        }

        return view('welcome_message');
    }

    public function accueil()
    {
        if (! session()->get('logged_in')) {
            return $this->redirectRel('/');
        }

        return view('accueil');
    }

    public function ajouter()
    {
        if (! session()->get('logged_in')) {
            return $this->redirectRel('/');
        }

        helper('form');

        $data = [
            'errors'  => [],
            'old'     => [],
            'success' => session()->getFlashdata('success') ?? '',
        ];

        if ($this->request->getMethod() === 'POST') {
            $nom      = trim((string) $this->request->getPost('nom'));
            $etu      = strtoupper(trim((string) $this->request->getPost('etu')));
            $mac      = strtolower(trim((string) $this->request->getPost('mac')));
            $hostname = trim((string) $this->request->getPost('hostname'));
            $mdp      = trim((string) $this->request->getPost('mdp'));

            $data['old'] = compact('nom', 'etu', 'mac', 'hostname', 'mdp');

            $errors = [];

            if ($nom === '') {
                $errors['nom'] = 'Le nom est obligatoire.';
            }

            $etuDigits = preg_replace('/\D+/', '', $etu);
            if ($etu === '') {
                $errors['etu'] = 'Le numéro ETU est obligatoire.';
            } elseif ($etuDigits === '') {
                $errors['etu'] = "L'ETU doit contenir des chiffres (ex : ETU004567).";
            } elseif ((new EtudiantModel())->etuExists((int) $etuDigits)) {
                $errors['etu'] = "L'ETU {$etu} est déjà enregistré.";
            }

            $filled = count(array_filter([$mac, $hostname, $mdp], static fn ($v) => $v !== ''));
            if ($filled > 0 && $filled < 3) {
                $errors['machine'] = "Si l'un des champs Adresse MAC, Hostname ou Mot de passe est renseigné, les trois doivent l'être.";
            }

            if ($mac !== '') {
                if (! preg_match('/^[0-9a-f]{2}(:[0-9a-f]{2}){5}$/i', $mac)) {
                    $errors['mac'] = 'Adresse MAC invalide (ex : 00:1A:2B:3C:4D:5E).';
                } elseif ((new EtudiantModel())->macExists($mac)) {
                    $errors['mac'] = 'Cette adresse MAC est déjà attribuée.';
                }
            }

            if (empty($errors)) {
                $model = new EtudiantModel();

                $machine = $filled === 3 ? [
                    'mac'      => $mac,
                    'hostname' => $hostname,
                    'mdp'      => $mdp,
                ] : null;

                $idEtudiant = $model->createWithMachine([
                    'nom' => $nom,
                    'etu' => (int) $etuDigits,
                ], $machine);

                if ($idEtudiant !== false) {
                    session()->setFlashdata('success', "Étudiant #{$idEtudiant} ajouté avec succès.");
                    return $this->redirectRel('/ajouter');
                }

                $errors['global'] = 'Erreur lors de l\'enregistrement. Veuillez réessayer.';
            }

            $data['errors'] = $errors;
        }

        return view('ajouter_etudiant', $data);
    }

    public function importer()
    {
        if (! session()->get('logged_in')) {
            return $this->redirectRel('/');
        }

        helper('form');

        $data = [
            'error'  => '',
            'report' => session()->getFlashdata('report'),
        ];

        if ($this->request->getMethod() === 'POST') {
            $file = $this->request->getFile('fichier');

            try {
                if (! $file || ! $file->isValid()) {
                    throw new RuntimeException('Fichier manquant ou invalide.');
                }
                if (strtolower($file->getClientExtension()) !== 'xlsx') {
                    throw new RuntimeException('Seuls les fichiers .xlsx sont acceptés.');
                }

                $report = $this->processImportExcel($file->getTempName());

                session()->setFlashdata('report', $report);
                return $this->redirectRel('/importer');
            } catch (Throwable $e) {
                $data['error'] = $e->getMessage() !== '' ? $e->getMessage() : 'Impossible de lire ce fichier Excel.';
            }
        }

        return view('importer_etudiant', $data);
    }

    private function processImportExcel(string $path): array
    {
        $sheets = (new XlsxReader())->read($path);

        if ($sheets === []) {
            throw new RuntimeException('Aucune feuille lisible dans ce fichier Excel.');
        }

        $report = [
            'sheets'         => [],
            'total_importes' => 0,
            'total_doublons' => 0,
            'total_ignores'  => 0,
        ];

        $pending = [];

        foreach ($sheets as $i => $sheet) {
            $stat = [
                'name'     => $sheet['name'],
                'reconnu'  => false,
                'importes' => 0,
                'doublons' => 0,
                'ignores'  => 0,
            ];

            $rows = $sheet['rows'];
            $map  = self::detectColumns($rows[0] ?? []);

            if ($map === null) {
                $stat['ignores'] = count($rows) > 1 ? count($rows) - 1 : 0;
                $report['sheets'][] = $stat;
                continue;
            }
            $stat['reconnu'] = true;

            foreach (array_slice($rows, 1) as $cells) {
                $etuDigits = preg_replace('/\D+/', '', $cells[$map['id']] ?? '');

                $parts = [$cells[$map['nom']] ?? ''];
                if ($map['prenom'] !== null) {
                    $parts[] = $cells[$map['prenom']] ?? '';
                }
                $nom = trim((string) preg_replace('/\s+/u', ' ', implode(' ', $parts)));

                if ($etuDigits === '' || $nom === '') {
                    $stat['ignores']++;
                    continue;
                }

                $etu = (int) $etuDigits;
                if (isset($pending[$etu])) {
                    $stat['doublons']++;
                    continue;
                }

                $pending[$etu] = ['nom' => $nom, 'sheet' => $i];
            }

            $report['sheets'][] = $stat;
        }

        $model    = new EtudiantModel();
        $existing = $model->existingEtus(array_keys($pending));
        $toInsert = [];

        foreach ($pending as $etu => $info) {
            if (in_array($etu, $existing, true)) {
                $report['sheets'][$info['sheet']]['doublons']++;
            } else {
                $toInsert[] = [
                    'etu'   => $etu,
                    'nom'   => $info['nom'],
                    'sheet' => $info['sheet'],
                ];
            }
        }

        $insertedCount = $model->importMany(array_map(
            static fn (array $r) => ['etu' => $r['etu'], 'nom' => $r['nom']],
            $toInsert
        ));

        for ($i = 0; $i < $insertedCount; $i++) {
            $report['sheets'][$toInsert[$i]['sheet']]['importes']++;
        }

        if ($insertedCount < count($toInsert)) {
            $remaining = count($toInsert) - $insertedCount;
            foreach (array_slice($toInsert, $insertedCount) as $r) {
                $report['sheets'][$r['sheet']]['ignores']++;
            }
            $report['erreur_partielle'] = "{$remaining} ligne(s) n'ont pas pu être enregistrée(s).";
        }

        foreach ($report['sheets'] as $stat) {
            $report['total_importes'] += $stat['importes'];
            $report['total_doublons'] += $stat['doublons'];
            $report['total_ignores'] += $stat['ignores'];
        }

        return $report;
    }

    /**
     * Détecte les colonnes d'une feuille d'après sa ligne d'en-tête.
     *
     * Formats gérés :
     *  - Id | Nom | Prénom (+ colonnes annexes)
     *  - id | nom | prenom
     *  - idetudiant | etudiant (nom déjà fusionné)
     *
     * @return array{id: string, nom: string, prenom: string|null}|null
     */
    private static function detectColumns(array $header): ?array
    {
        if ($header === []) {
            return null;
        }

        $norm = [];
        foreach ($header as $col => $label) {
            $stripped = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $label);
            $norm[$col] = preg_replace('/[^a-z]/', '', strtolower($stripped !== false ? $stripped : $label));
        }

        $find = static function (array $candidates) use ($norm): ?string {
            foreach ($norm as $col => $value) {
                if (in_array($value, $candidates, true)) {
                    return $col;
                }
            }
            return null;
        };

        $idCol     = $find(['id', 'idetudiant']);
        $nomCol    = $find(['nom']);
        $prenomCol = $find(['prenom']);
        $etuCol    = $find(['etudiant']);

        if ($idCol !== null && $nomCol !== null && $prenomCol !== null) {
            return ['id' => $idCol, 'nom' => $nomCol, 'prenom' => $prenomCol];
        }

        if ($idCol !== null && $etuCol !== null && $etuCol !== $idCol) {
            return ['id' => $idCol, 'nom' => $etuCol, 'prenom' => null];
        }

        return null;
    }

    public function logout()
    {
        session()->destroy();
        return $this->redirectRel('/');
    }
}
