<?php

namespace App\Models;

use CodeIgniter\Model;

class EtudiantModel extends Model
{
    protected $table      = 'vue_etudiant_machine';
    protected $primaryKey = 'id_etudiant';
    protected $allowedFields = [];

    public function search(array $criteria): array
    {
        $builder = $this->db->table($this->table);

        foreach ($criteria as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            if ($field === 'quota_min') {
                $builder->where('quota_consomme >=', (int) $value);
            } elseif ($field === 'quota_max') {
                $builder->where('quota_consomme <=', (int) $value);
            } elseif (in_array($field, ['id_etudiant', 'etu'])) {
                $escaped = $this->db->escapeLikeString($value);
                $builder->where("CAST($field AS TEXT) LIKE '%$escaped%'");
            } else {
                $builder->like($field, $value);
            }
        }

        $builder->orderBy('nom', 'ASC');

        return $builder->get()->getResultArray();
    }

    private function filterBuilder(array $criteria)
    {
        $builder = $this->db->table('etudiants');

        $nom = trim((string) ($criteria['nom'] ?? ''));
        if ($nom !== '') {
            $escaped = $this->db->escapeLikeString($nom);
            $builder->where("nom ILIKE '%$escaped%'");
        }

        $etu = trim((string) ($criteria['etu'] ?? ''));
        if ($etu !== '') {
            $escaped = $this->db->escapeLikeString($etu);
            $builder->where("CAST(etu AS TEXT) LIKE '%$escaped%'");
        }

        return $builder;
    }

    public function countAll(array $criteria = []): int
    {
        return $this->filterBuilder($criteria)->countAllResults();
    }

    public function all(array $criteria = [], int $perPage = 50, int $page = 1): array
    {
        $builder = $this->filterBuilder($criteria);
        $builder->orderBy('nom', 'ASC');
        $builder->limit($perPage, max(0, ($page - 1) * $perPage));

        return $builder->get()->getResultArray();
    }

    public function deleteMachineAndMacs(int $idEtudiant): bool
    {
        $this->db->transStart();

        $row = $this->db->table('macs')->where('id_etudiant', $idEtudiant)->get()->getRow();
        if (! $row) {
            $this->db->transRollback();
            return false;
        }

        $this->db->table('machine')->where('mac', $row->mac)->delete();
        $this->db->table('macs')->where('id_etudiant', $idEtudiant)->delete();

        return $this->db->transComplete();
    }

    public function updateMachine(int $idEtudiant, string $mac, string $hostname, string $mdp): bool
    {
        $this->db->transStart();

        $row = $this->db->table('macs')->where('id_etudiant', $idEtudiant)->get()->getRow();
        if (! $row) {
            $this->db->transRollback();
            return false;
        }
        $oldMac = $row->mac;

        $this->db->table('machine')->where('mac', $oldMac)->update([
            'mac'      => $mac,
            'hostname' => $hostname,
            'mdp'      => $mdp,
        ]);

        if ($mac !== $oldMac) {
            $this->db->table('macs')->where('id_etudiant', $idEtudiant)->update([
                'mac' => $mac,
            ]);
        }

        return $this->db->transComplete();
    }

    public function etuExists(int $etu): bool
    {
        return $this->db->table('etudiants')->where('etu', $etu)->countAllResults() > 0;
    }

    public function existingEtus(array $etus): array
    {
        if ($etus === []) {
            return [];
        }

        $rows = $this->db->table('etudiants')
            ->select('etu')
            ->whereIn('etu', $etus)
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'etu'));
    }

    public function importMany(array $rows): int
    {
        $inserted = 0;

        foreach (array_chunk($rows, 200) as $chunk) {
            $result = $this->db->table('etudiants')->insertBatch($chunk);
            if ($result === false) {
                break;
            }
            $inserted += count($chunk);
        }

        return $inserted;
    }

    public function macExists(string $mac): bool
    {
        return $this->db->table('machine')->where('mac', $mac)->countAllResults() > 0
            || $this->db->table('macs')->where('mac', $mac)->countAllResults() > 0;
    }

    public function createWithMachine(array $etudiant, ?array $machine)
    {
        $this->db->transStart();

        if (! $this->db->table('etudiants')->insert($etudiant)) {
            $this->db->transRollback();
            return false;
        }

        $idEtudiant = $this->db->insertID();

        if ($machine !== null) {
            $this->db->table('machine')->insert([
                'mac'       => $machine['mac'],
                'etu'       => (string) $etudiant['etu'],
                'hostname'  => $machine['hostname'],
                'mdp'       => $machine['mdp'],
                'type_user' => 1,
            ]);

            $this->db->table('macs')->insert([
                'id_etudiant' => $idEtudiant,
                'mac'         => $machine['mac'],
            ]);
        }

        return $this->db->transComplete() ? $idEtudiant : false;
    }
}
