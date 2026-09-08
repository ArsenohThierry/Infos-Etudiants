<?php

namespace App\Controllers;

use App\Models\EtudiantModel;

class RechercheEtudiant extends BaseController
{
    public function index()
    {
        if (! session()->get('logged_in')) {
            return $this->redirectRel('/');
        }

        helper('form');
        $model = new EtudiantModel();
        $results = [];

        if ($this->request->getMethod() === 'POST') {
            $criteria = [
                'id_etudiant' => $this->request->getPost('id_etudiant'),
                'etu'         => $this->request->getPost('etu'),
                'hostname'    => $this->request->getPost('hostname'),
                'mac'         => $this->request->getPost('mac'),
                'mdp'         => $this->request->getPost('mdp'),
                'quota_min'   => $this->request->getPost('quota_min'),
                'quota_max'   => $this->request->getPost('quota_max'),
            ];

            $results = $model->search($criteria);
        }

        return view('recherche_etudiant', [
            'results'  => $results,
            'method'   => $this->request->getMethod(),
            'modifier_success' => session()->getFlashdata('modifier_success') ?? '',
        ]);
    }

    public function modifier()
    {
        if (! session()->get('logged_in')) {
            return $this->redirectRel('/');
        }

        $id  = (int) $this->request->getPost('id_etudiant');
        $mac = trim($this->request->getPost('mac'));
        $hostname = trim($this->request->getPost('hostname'));
        $mdp = trim($this->request->getPost('mdp'));

        if ($id < 1) {
            session()->setFlashdata('modifier_success', 'ID étudiant invalide.');
            return $this->redirectRel('/recherche');
        }

        $model = new EtudiantModel();

        if ($model->updateMachine($id, $mac, $hostname, $mdp)) {
            session()->setFlashdata('modifier_success', "Étudiant #{$id} mis à jour avec succès.");
        } else {
            session()->setFlashdata('modifier_success', "Erreur : étudiant #{$id} introuvable.");
        }

        return $this->redirectRel('/recherche');
    }
}
