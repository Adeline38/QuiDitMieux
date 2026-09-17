<?php
// MODÈLE : utilisateur.php
// Rôle : Représenter un compte utilisateur et centraliser ses accès à la table utilisateur.
// Paramètres : Les contrôleurs fournissent des données déjà validées pour rechercher ou enregistrer un compte.
// Retour : Le modèle retourne des objets utilisateur ou le résultat des écritures préparées.

class utilisateur extends _model
{
    // La table et ses colonnes correspondent exactement au MPD et à la structure MySQL contrôlée.
    protected $table = 'utilisateur';
    protected $fields = [
        'pseudo',
        'email',
        'mdp_hash'
    ];

    public function trouver_par_identifiant($identifiant) {
        // Rôle : Rechercher un compte avec un pseudo ou un courriel sans distinguer les majuscules des minuscules.
        // Paramètres : $identifiant contient le pseudo ou l'adresse de courriel saisi à la connexion.
        // Retour : Un objet utilisateur lorsque le compte existe, sinon null.

        // Deux repères différents sont nécessaires avec les vraies requêtes préparées de PDO.
        $sql = 'SELECT ' . $this->listFieldsForSelect() . '
                FROM `utilisateur`
                WHERE LOWER(`pseudo`) = LOWER(:identifiant_pseudo)
                   OR LOWER(`email`) = LOWER(:identifiant_email)
                LIMIT 1';

        return $this->sqlToObject(
            $sql,
            [
                ':identifiant_pseudo' => $identifiant,
                ':identifiant_email' => $identifiant
            ]
        );
    }

    public function pseudo_existe($pseudo, $id_exclu = 0) {
        // Rôle : Indiquer si un pseudo appartient déjà à un autre compte.
        // Paramètres : $pseudo contient la valeur recherchée et $id_exclu permet d'ignorer le compte modifié.
        // Retour : true si le pseudo existe déjà, sinon false.
        
        return $this->valeur_unique_existe('pseudo', $pseudo, $id_exclu);
    }

    public function courriel_existe($courriel, $id_exclu = 0) {
        // Rôle : Indiquer si une adresse de courriel appartient déjà à un autre compte.
        // Paramètres : $courriel contient la valeur recherchée et $id_exclu permet d'ignorer le compte modifié.
        // Retour : true si le courriel existe déjà, sinon false.
        
        return $this->valeur_unique_existe('email', $courriel, $id_exclu);
    }

    public function creer_compte($pseudo, $courriel, $mdp_hash) {
        // Rôle : Créer un compte à partir de valeurs validées et d'un mot de passe déjà haché.
        // Paramètres : $pseudo, $courriel et $mdp_hash contiennent les trois valeurs à enregistrer.
        // Retour : true si l'insertion réussit, sinon false.
        
        // Le modèle remplit uniquement les colonnes autorisées avant d'utiliser l'INSERT générique parent.
        $this->set('pseudo', $pseudo);
        $this->set('email', $courriel);
        $this->set('mdp_hash', $mdp_hash);

        return $this->insert();
    }

    public function modifier_compte($pseudo, $courriel, $nouveau_hash = null) {
        // Rôle : Modifier les informations d'un compte préalablement chargé en conservant éventuellement son mot de passe.
        // Paramètres : $pseudo et $courriel sont obligatoires ; $nouveau_hash vaut null si le mot de passe reste inchangé.
        // Retour : true si la mise à jour réussit, sinon false.
        
        // L'objet chargé contient déjà le hash actuel, qui reste intact lorsqu'aucun nouveau mot de passe n'est fourni.
        $this->set('pseudo', $pseudo);
        $this->set('email', $courriel);

        if ($nouveau_hash !== null) {
            $this->set('mdp_hash', $nouveau_hash);
        }

        return $this->update();
    }

    private function valeur_unique_existe($champ, $valeur, $id_exclu = 0) {
        // Rôle : Vérifier l'unicité d'une valeur à partir d'une colonne strictement autorisée.
        // Paramètres : $champ désigne pseudo ou email, $valeur est recherchée et $id_exclu peut ignorer un compte.
        // Retour : true lorsqu'une autre ligne possède déjà la valeur, sinon false.
        
        // Le nom SQL ne vient jamais directement d'une requête utilisateur : cette liste blanche le protège.
        $champs_autorises = [
            'pseudo',
            'email'
        ];

        if (!in_array($champ, $champs_autorises, true)) {
            return true;
        }

        $sql = 'SELECT `id`
                FROM `utilisateur`
                WHERE LOWER(`' . $champ . '`) = LOWER(:valeur)';
        $parametres = [
            ':valeur' => $valeur
        ];

        // Pendant une modification, le compte courant ne doit pas être considéré comme un doublon de lui-même.
        if (is_int($id_exclu) && $id_exclu > 0) {
            $sql .= ' AND `id` <> :id_exclu';
            $parametres[':id_exclu'] = $id_exclu;
        }

        $sql .= ' LIMIT 1';
        $ligne = $this->sqlToLigne($sql, $parametres);

        return $ligne !== null;
    }

}
