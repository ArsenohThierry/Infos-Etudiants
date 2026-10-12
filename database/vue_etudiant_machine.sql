-- Vue attendue par App\Models\EtudiantModel (protected $table = 'vue_etudiant_machine')
-- Jointure : etudiants -> macs -> machine
-- quota_consomme n'existe pas dans la base : valeur fixe 0 (à remplacer si une source existe)
-- Exécution : psql -h localhost -U admin -d monitoring -f database/vue_etudiant_machine.sql

CREATE OR REPLACE VIEW public.vue_etudiant_machine AS
SELECT
    e.id            AS id_etudiant,
    e.nom           AS nom,
    e.etu           AS etu,
    mac.mac         AS mac,
    mach.hostname   AS hostname,
    mach.mdp        AS mdp,
    0::bigint       AS quota_consomme
FROM public.etudiants e
JOIN public.macs mac          ON mac.id_etudiant = e.id
LEFT JOIN public.machine mach ON mach.mac = mac.mac;
