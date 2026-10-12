<?php

namespace App\Controllers;

use App\Models\EtudiantModel;

class ListeEtudiants extends BaseController
{
    public function index()
    {
        if (! session()->get('logged_in')) {
            return $this->redirectRel('/');
        }

        $criteria = [
            'nom' => (string) $this->request->getGet('nom'),
            'etu' => (string) $this->request->getGet('etu'),
        ];

        $perPage = 50;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $model = new EtudiantModel();
        $total = $model->countAll($criteria);

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = min($page, $totalPages);

        return view('liste_etudiants', [
            'results'    => $model->all($criteria, $perPage, $page),
            'criteria'   => $criteria,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ]);
    }
}
