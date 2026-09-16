<?php
// MODÈLE : annonce.php
// Rôle : Définir le modèle qui lit et modifie les annonces enregistrées en base de données.
// Paramètres : Les méthodes reçoivent uniquement les informations nécessaires à leur traitement.
// Retour : Les méthodes retournent des objets annonce ou des résultats exploitables par un contrôleur.

class annonce extends _model
{
    // Ces informations relient ce modèle à la table ANNONCE et limitent les colonnes qu'il peut manipuler.
    protected $table = 'ANNONCE';

    protected $fields = [
        'titre',
        'description',
        'etat',
        'prix_depart',
        'date_heure_fin',
        'categorie_id',
        'utilisateur_id'
    ];
    
    public function creer_annonce($donnees) {
        // Rôle : Enregistrer une annonce à partir de données déjà validées par le contrôleur.
        // Paramètres : $donnees associe chaque colonne obligatoire à sa valeur prête pour MySQL.
        // Retour : true lorsque l'annonce est créée et reçoit un identifiant, sinon false.

        foreach ($this->fields as $champ) {
            if (!array_key_exists($champ, $donnees)) {
                return false;
            }

            $this->set($champ, $donnees[$champ]);
        }

        return $this->insert();
    }

    public function commencer_transaction() {
        // Rôle : Démarrer la transaction qui réunit une annonce et ses photographies.
        // Paramètres : Aucun paramètre car la connexion PDO est commune à tous les modèles.
        // Retour : true si la transaction commence, sinon false.

        global $bdd;

        if ($bdd->inTransaction()) {
            return false;
        }

        return $bdd->beginTransaction();
    }

    public function valider_transaction() {
        // Rôle : Valider définitivement toutes les écritures de la transaction courante.
        // Paramètres : Aucun paramètre car la transaction appartient à la connexion commune.
        // Retour : true si les écritures sont validées, sinon false.
        
        global $bdd;

        if (!$bdd->inTransaction()) {
            return false;
        }

        return $bdd->commit();
    }
    
    public function annuler_transaction() {
        // Rôle : Annuler toutes les écritures encore comprises dans la transaction courante.
        // Paramètres : Aucun paramètre car la transaction appartient à la connexion commune.
        // Retour : true si l'annulation est effectuée ou si aucune transaction n'est active.
        
        global $bdd;

        if (!$bdd->inTransaction()) {
            return true;
        }

        return $bdd->rollBack();
    }

    public function lister_selection_accueil() {
        // Rôle : Lister au maximum six annonces actives ayant reçu au moins une enchère.
        // Paramètres : Aucun paramètre n'est nécessaire pour la sélection de l'accueil.
        // Retour : Un tableau d'objets annonce, classés de l'enchère la plus récente à la plus ancienne.
        
        // La sous-requête trouve la dernière enchère de chaque annonce et écarte automatiquement les annonces sans enchère.
        // La condition sur la fin de vente conserve uniquement les ventes ouvertes, puis LIMIT respecte les six cartes prévues.
        $sql = "SELECT
                    `ANNONCE`.`id` AS `id`,
                    `ANNONCE`.`titre` AS `titre`,
                    `ANNONCE`.`description` AS `description`,
                    `ANNONCE`.`etat` AS `etat`,
                    `ANNONCE`.`prix_depart` AS `prix_depart`,
                    `ANNONCE`.`date_heure_fin` AS `date_heure_fin`,
                    `ANNONCE`.`categorie_id` AS `categorie_id`,
                    `ANNONCE`.`utilisateur_id` AS `utilisateur_id`
                FROM `ANNONCE`
                INNER JOIN (
                    SELECT
                        `annonce_id`,
                        MAX(`date_heure`) AS `derniere_enchere`
                    FROM `ENCHERE`
                    GROUP BY `annonce_id`
                ) AS `resume_enchere`
                    ON `resume_enchere`.`annonce_id` = `ANNONCE`.`id`
                WHERE `ANNONCE`.`date_heure_fin` > NOW()
                ORDER BY `resume_enchere`.`derniere_enchere` DESC
                LIMIT 6";

        // La classe parente exécute la requête et transforme chaque ligne en objet annonce.
        return $this->sqlToTab($sql);
    }

    public function rechercher($criteres, $limite, $decalage) {
        // Rôle : Rechercher une page d'annonces à partir de plusieurs critères combinables.
        // Paramètres : $criteres contient les filtres validés ; $limite et $decalage définissent la page demandée.
        // Retour : Un tableau de lignes préparées pour les cartes, ou false si la requête échoue.
        
        $recherche = $this->preparer_conditions_recherche($criteres);

        // Les deux nombres sont contrôlés avant d'être placés dans LIMIT, qui n'accepte ici aucune saisie brute.
        $limite_validee = filter_var($limite, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $decalage_valide = filter_var($decalage, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($limite_validee === false) {
            $limite_validee = 6;
        }

        if ($decalage_valide === false) {
            $decalage_valide = 0;
        }

        // La jointure réunit les enchères afin de calculer le prix courant et leur nombre.
        // La sous-requête de PHOTO retient uniquement la première image de chaque annonce.
        $sql = "SELECT
                    `ANNONCE`.`id` AS `id`,
                    `ANNONCE`.`titre` AS `titre`,
                    `ANNONCE`.`date_heure_fin` AS `date_heure_fin`,
                    `ANNONCE`.`categorie_id` AS `categorie_id`,
                    COALESCE(MAX(`ENCHERE`.`montant`), `ANNONCE`.`prix_depart`) AS `prix_courant`,
                    COUNT(`ENCHERE`.`id`) AS `nombre_encheres`,
                    (`ANNONCE`.`date_heure_fin` > NOW()) AS `vente_ouverte`,
                    `PHOTO`.`nom` AS `nom_photo`
                FROM `ANNONCE`
                LEFT JOIN `ENCHERE`
                    ON `ENCHERE`.`annonce_id` = `ANNONCE`.`id`
                LEFT JOIN `PHOTO`
                    ON `PHOTO`.`id` = (
                        SELECT `PHOTO_PRINCIPALE`.`id`
                        FROM `PHOTO` AS `PHOTO_PRINCIPALE`
                        WHERE `PHOTO_PRINCIPALE`.`annonce_id` = `ANNONCE`.`id`
                        ORDER BY `PHOTO_PRINCIPALE`.`position` ASC, `PHOTO_PRINCIPALE`.`id` ASC
                        LIMIT 1
                    )
                " . $recherche['where'] . "
                GROUP BY
                    `ANNONCE`.`id`,
                    `ANNONCE`.`titre`,
                    `ANNONCE`.`prix_depart`,
                    `ANNONCE`.`date_heure_fin`,
                    `ANNONCE`.`categorie_id`,
                    `PHOTO`.`nom`
                " . $recherche['having'] . "
                ORDER BY
                    CASE WHEN `ANNONCE`.`date_heure_fin` > NOW() THEN 0 ELSE 1 END ASC,
                    CASE WHEN `ANNONCE`.`date_heure_fin` > NOW() THEN `ANNONCE`.`date_heure_fin` END ASC,
                    CASE WHEN `ANNONCE`.`date_heure_fin` <= NOW() THEN `ANNONCE`.`date_heure_fin` END DESC,
                    `ANNONCE`.`id` DESC
                LIMIT " . $limite_validee . " OFFSET " . $decalage_valide;

        $requete = $this->execute($sql, $recherche['parametres']);

        if ($requete === false) {
            return false;
        }

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public function compter_recherche($criteres) {
        // Rôle : Compter toutes les annonces correspondant aux critères avant la pagination.
        // Paramètres : $criteres contient les mêmes filtres validés que la recherche paginée.
        // Retour : Le nombre total d'annonces, ou null si la requête échoue.
        
        $recherche = $this->preparer_conditions_recherche($criteres);

        // La requête intérieure calcule le prix courant de chaque annonce avant d'appliquer la fourchette.
        $sql = "SELECT COUNT(*) AS `total`
                FROM (
                    SELECT `ANNONCE`.`id`
                    FROM `ANNONCE`
                    LEFT JOIN `ENCHERE`
                        ON `ENCHERE`.`annonce_id` = `ANNONCE`.`id`
                    " . $recherche['where'] . "
                    GROUP BY `ANNONCE`.`id`, `ANNONCE`.`prix_depart`
                    " . $recherche['having'] . "
                ) AS `ANNONCES_TROUVEES`";

        $resultat = $this->sqlToLigne($sql, $recherche['parametres']);

        if ($resultat === null) {
            return null;
        }

        return (int) $resultat['total'];
    }

    public function consulter_situation_action($annonce_id) {
        // Rôle : Consulter la propriété, l'échéance et le nombre d'enchères avant d'afficher une action vendeur.
        // Paramètres : $annonce_id est l'identifiant entier positif de l'annonce demandée.
        // Retour : Une ligne décrivant l'annonce et son autorisation actuelle, ou null si elle est absente.
        
        return $this->lire_situation_action($annonce_id, false);
    }

    public function verrouiller_situation_action($annonce_id) {
        // Rôle : Verrouiller une annonce pendant une transaction avant sa modification ou sa suppression.
        // Paramètres : $annonce_id est l'identifiant entier positif de l'annonce concernée.
        // Retour : Une ligne décrivant la situation au moment de l'écriture, ou null si l'annonce est absente.
        
        return $this->lire_situation_action($annonce_id, true);
    }

    public function modifier_annonce($donnees) {
        // Rôle : Remplacer les informations modifiables d'une annonce déjà chargée.
        // Paramètres : $donnees contient les six champs validés pouvant être changés par le vendeur.
        // Retour : true si l'UPDATE réussit, sinon false.
        
        $champs_modifiables = [
            'titre',
            'description',
            'etat',
            'prix_depart',
            'date_heure_fin',
            'categorie_id'
        ];

        foreach ($champs_modifiables as $champ) {
            if (!array_key_exists($champ, $donnees)) {
                return false;
            }

            if (!$this->set($champ, $donnees[$champ])) {
                return false;
            }
        }

        return $this->update();
    }

    public function est_suivie_par($annonce_id, $utilisateur_id) {
        // Rôle : Vérifier si un utilisateur suit volontairement une annonce.
        // Paramètres : $annonce_id et $utilisateur_id sont les deux identifiants de l'association recherchée.
        // Retour : true si l'association existe, sinon false.
        
        $sql = "SELECT COUNT(`id`) AS `nombre`
                FROM `ASSO_UTILISATEUR_ANNONCE`
                WHERE `annonce_id` = :annonce_id
                AND `utilisateur_id` = :utilisateur_id";

        $resultat = $this->sqlToLigne($sql, [
            ':annonce_id' => $annonce_id,
            ':utilisateur_id' => $utilisateur_id
        ]);

        if ($resultat === null) {
            return false;
        }

        return (int) $resultat['nombre'] > 0;
    }

    public function ajouter_suivi($annonce_id, $utilisateur_id) {
        // Rôle : Ajouter le suivi d'une annonce après avoir revérifié les règles métier au moment de l'écriture.
        // Paramètres : $annonce_id désigne l'annonce et $utilisateur_id désigne le compte connecté.
        // Retour : Un code simple indique la réussite ou la raison du refus.
        
        global $bdd;

        if ($bdd->inTransaction() || !$bdd->beginTransaction()) {
            return 'erreur';
        }

        // Le verrou empêche la vente de changer pendant les contrôles qui précèdent l'ajout.
        $sql_annonce = "SELECT
                            `utilisateur_id`,
                            (`date_heure_fin` > NOW()) AS `vente_ouverte`
                        FROM `ANNONCE`
                        WHERE `id` = :annonce_id
                        FOR UPDATE";

        $annonce = $this->sqlToLigne($sql_annonce, [
            ':annonce_id' => $annonce_id
        ]);

        if ($annonce === null) {
            $bdd->rollBack();
            return 'annonce_introuvable';
        }

        if ((int) $annonce['utilisateur_id'] === (int) $utilisateur_id) {
            $bdd->rollBack();
            return 'annonce_personnelle';
        }

        if ((int) $annonce['vente_ouverte'] !== 1) {
            $bdd->rollBack();
            return 'vente_terminee';
        }

        if ($this->est_suivie_par($annonce_id, $utilisateur_id)) {
            $bdd->rollBack();
            return 'deja_suivie';
        }

        $sql_insertion = "INSERT INTO `ASSO_UTILISATEUR_ANNONCE`
                            (`annonce_id`, `utilisateur_id`)
                          VALUES
                            (:annonce_id, :utilisateur_id)";

        $insertion = $this->execute($sql_insertion, [
            ':annonce_id' => $annonce_id,
            ':utilisateur_id' => $utilisateur_id
        ]);

        if ($insertion === false || !$bdd->commit()) {
            if ($bdd->inTransaction()) {
                $bdd->rollBack();
            }

            return 'erreur';
        }

        return 'succes';
    }

    public function supprimer_suivi($annonce_id, $utilisateur_id) {
        // Rôle : Retirer un suivi volontaire, y compris lorsque la vente est terminée.
        // Paramètres : $annonce_id désigne l'annonce et $utilisateur_id désigne le compte connecté.
        // Retour : Un code simple indique la réussite, l'absence de suivi ou une erreur.
        
        // Le DELETE contient les deux identifiants : un utilisateur ne peut retirer que sa propre association.
        $sql = "DELETE FROM `ASSO_UTILISATEUR_ANNONCE`
                WHERE `annonce_id` = :annonce_id
                AND `utilisateur_id` = :utilisateur_id";

        $requete = $this->execute($sql, [
            ':annonce_id' => $annonce_id,
            ':utilisateur_id' => $utilisateur_id
        ]);

        if ($requete === false) {
            return 'erreur';
        }

        if ($requete->rowCount() === 0) {
            return 'non_suivie';
        }

        return 'succes';
    }

    public function lister_tableau_vendeur($utilisateur_id) {
        // Rôle : Lister toutes les annonces publiées par un vendeur avec leur état actuel.
        // Paramètres : $utilisateur_id identifie le vendeur connecté.
        // Retour : Un tableau de lignes agrégées destiné au tableau de bord, ou false en cas d'échec.
        
        $sql = "SELECT
                    `ANNONCE`.`id`,
                    `ANNONCE`.`titre`,
                    `ANNONCE`.`date_heure_fin`,
                    COALESCE(MAX(`ENCHERE`.`montant`), `ANNONCE`.`prix_depart`) AS `prix_courant`,
                    COUNT(`ENCHERE`.`id`) AS `nombre_encheres`,
                    (`ANNONCE`.`date_heure_fin` > NOW()) AS `vente_ouverte`,
                    `PHOTO`.`nom` AS `nom_photo`
                FROM `ANNONCE`
                LEFT JOIN `ENCHERE`
                    ON `ENCHERE`.`annonce_id` = `ANNONCE`.`id`
                LEFT JOIN `PHOTO`
                    ON `PHOTO`.`id` = (
                        SELECT `PHOTO_PRINCIPALE`.`id`
                        FROM `PHOTO` AS `PHOTO_PRINCIPALE`
                        WHERE `PHOTO_PRINCIPALE`.`annonce_id` = `ANNONCE`.`id`
                        ORDER BY `PHOTO_PRINCIPALE`.`position` ASC, `PHOTO_PRINCIPALE`.`id` ASC
                        LIMIT 1
                    )
                WHERE `ANNONCE`.`utilisateur_id` = :utilisateur_id
                GROUP BY
                    `ANNONCE`.`id`,
                    `ANNONCE`.`titre`,
                    `ANNONCE`.`prix_depart`,
                    `ANNONCE`.`date_heure_fin`,
                    `PHOTO`.`nom`
                ORDER BY
                    CASE WHEN `ANNONCE`.`date_heure_fin` > NOW() THEN 0 ELSE 1 END,
                    CASE WHEN `ANNONCE`.`date_heure_fin` > NOW() THEN `ANNONCE`.`date_heure_fin` END ASC,
                    CASE WHEN `ANNONCE`.`date_heure_fin` <= NOW() THEN `ANNONCE`.`date_heure_fin` END DESC";

        $requete = $this->execute($sql, [
            ':utilisateur_id' => $utilisateur_id
        ]);

        if ($requete === false) {
            return false;
        }

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function lister_tableau_acheteur($utilisateur_id) {
        // Rôle : Lister les annonces suivies ou enchéries et calculer la position privée de l'utilisateur.
        // Paramètres : $utilisateur_id identifie le compte connecté.
        // Retour : Un tableau de lignes permettant de séparer les suivis, participations et ventes remportées.

        // Les sous-requêtes vérifient les relations sans dupliquer les lignes d'enchères utilisées pour les calculs.
        $sql = "SELECT
                    `ANNONCE`.`id`,
                    `ANNONCE`.`titre`,
                    `ANNONCE`.`date_heure_fin`,
                    COALESCE(MAX(`ENCHERE`.`montant`), `ANNONCE`.`prix_depart`) AS `prix_courant`,
                    COUNT(`ENCHERE`.`id`) AS `nombre_encheres`,
                    (`ANNONCE`.`date_heure_fin` > NOW()) AS `vente_ouverte`,
                    MAX(CASE
                        WHEN `ENCHERE`.`utilisateur_id` = :utilisateur_id_position
                        THEN `ENCHERE`.`montant`
                    END) AS `meilleur_montant_utilisateur`,
                    SUM(CASE
                        WHEN `ENCHERE`.`utilisateur_id` = :utilisateur_id_nombre
                        THEN 1 ELSE 0
                    END) AS `nombre_encheres_utilisateur`,
                    EXISTS(
                        SELECT 1
                        FROM `ASSO_UTILISATEUR_ANNONCE`
                        WHERE `ASSO_UTILISATEUR_ANNONCE`.`annonce_id` = `ANNONCE`.`id`
                        AND `ASSO_UTILISATEUR_ANNONCE`.`utilisateur_id` = :utilisateur_id_suivi
                    ) AS `suivi_volontaire`,
                    `PHOTO`.`nom` AS `nom_photo`
                FROM `ANNONCE`
                LEFT JOIN `ENCHERE`
                    ON `ENCHERE`.`annonce_id` = `ANNONCE`.`id`
                LEFT JOIN `PHOTO`
                    ON `PHOTO`.`id` = (
                        SELECT `PHOTO_PRINCIPALE`.`id`
                        FROM `PHOTO` AS `PHOTO_PRINCIPALE`
                        WHERE `PHOTO_PRINCIPALE`.`annonce_id` = `ANNONCE`.`id`
                        ORDER BY `PHOTO_PRINCIPALE`.`position` ASC, `PHOTO_PRINCIPALE`.`id` ASC
                        LIMIT 1
                    )
                WHERE `ANNONCE`.`utilisateur_id` <> :utilisateur_id_proprietaire
                AND (
                    EXISTS(
                        SELECT 1
                        FROM `ASSO_UTILISATEUR_ANNONCE` AS `SUIVI`
                        WHERE `SUIVI`.`annonce_id` = `ANNONCE`.`id`
                        AND `SUIVI`.`utilisateur_id` = :utilisateur_id_relation
                    )
                    OR EXISTS(
                        SELECT 1
                        FROM `ENCHERE` AS `PARTICIPATION`
                        WHERE `PARTICIPATION`.`annonce_id` = `ANNONCE`.`id`
                        AND `PARTICIPATION`.`utilisateur_id` = :utilisateur_id_participation
                    )
                )
                GROUP BY
                    `ANNONCE`.`id`,
                    `ANNONCE`.`titre`,
                    `ANNONCE`.`prix_depart`,
                    `ANNONCE`.`date_heure_fin`,
                    `PHOTO`.`nom`
                ORDER BY
                    CASE WHEN `ANNONCE`.`date_heure_fin` > NOW() THEN 0 ELSE 1 END,
                    CASE WHEN `ANNONCE`.`date_heure_fin` > NOW() THEN `ANNONCE`.`date_heure_fin` END ASC,
                    CASE WHEN `ANNONCE`.`date_heure_fin` <= NOW() THEN `ANNONCE`.`date_heure_fin` END DESC";

        $requete = $this->execute($sql, [
            ':utilisateur_id_position' => $utilisateur_id,
            ':utilisateur_id_nombre' => $utilisateur_id,
            ':utilisateur_id_suivi' => $utilisateur_id,
            ':utilisateur_id_proprietaire' => $utilisateur_id,
            ':utilisateur_id_relation' => $utilisateur_id,
            ':utilisateur_id_participation' => $utilisateur_id
        ]);

        if ($requete === false) {
            return false;
        }

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    private function lire_situation_action($annonce_id, $avec_verrou) {
        // Rôle : Lire les données nécessaires à une action vendeur, avec ou sans verrou de transaction.
        // Paramètres : $annonce_id désigne l'annonce et $avec_verrou indique si sa ligne doit être réservée jusqu'à la fin de la transaction.
        // Retour : Une ligne complète avec le nombre d'enchères et l'état de la vente, ou null.
        
        // Le nombre d'enchères est calculé sans charger leurs données privées.
        $sql = "SELECT
                    `ANNONCE`.`id`,
                    `ANNONCE`.`titre`,
                    `ANNONCE`.`description`,
                    `ANNONCE`.`etat`,
                    `ANNONCE`.`prix_depart`,
                    `ANNONCE`.`date_heure_fin`,
                    `ANNONCE`.`categorie_id`,
                    `ANNONCE`.`utilisateur_id`,
                    (SELECT COUNT(*)
                     FROM `ENCHERE`
                     WHERE `ENCHERE`.`annonce_id` = `ANNONCE`.`id`) AS `nombre_encheres`,
                    (`ANNONCE`.`date_heure_fin` > NOW()) AS `vente_ouverte`
                FROM `ANNONCE`
                WHERE `ANNONCE`.`id` = :annonce_id";

        // FOR UPDATE est ajouté uniquement par la méthode interne et jamais depuis une donnée utilisateur.
        if ($avec_verrou === true) {
            $sql .= ' FOR UPDATE';
        }

        return $this->sqlToLigne($sql, [
            ':annonce_id' => $annonce_id
        ]);
    }

    private function preparer_conditions_recherche($criteres) {
        // Rôle : Construire les conditions SQL communes au comptage et à la liste des résultats.
        // Paramètres : $criteres contient seulement des valeurs déjà validées par le contrôleur.
        // Retour : Les clauses WHERE et HAVING accompagnées de leurs paramètres préparés.
        
        $conditions_where = [];
        $conditions_having = [];
        $parametres = [];

        // Chaque mot doit apparaître dans le titre ou la description ; tous les blocs sont ensuite reliés par AND.
        // La collation utf8mb4_unicode_ci des colonnes rend cette comparaison insensible à la casse et aux accents.
        foreach ($criteres['mots'] as $numero => $mot) {
            $repere_titre = ':mot_titre_' . $numero;
            $repere_description = ':mot_description_' . $numero;
            $mot_protege = str_replace(['=', '%', '_'], ['==', '=%', '=_'], $mot);
            $conditions_where[] = "(`ANNONCE`.`titre` LIKE " . $repere_titre . " ESCAPE '=' OR `ANNONCE`.`description` LIKE " . $repere_description . " ESCAPE '=')";
            $parametres[$repere_titre] = '%' . $mot_protege . '%';
            $parametres[$repere_description] = '%' . $mot_protege . '%';
        }

        if ($criteres['categorie_id'] !== '') {
            $conditions_where[] = '`ANNONCE`.`categorie_id` = :categorie_id';
            $parametres[':categorie_id'] = $criteres['categorie_id'];
        }

        if ($criteres['etat'] !== '') {
            $conditions_where[] = '`ANNONCE`.`etat` = :etat';
            $parametres[':etat'] = $criteres['etat'];
        }

        if ($criteres['statut'] === 'en_cours') {
            $conditions_where[] = '`ANNONCE`.`date_heure_fin` > NOW()';
        }

        if ($criteres['statut'] === 'terminees') {
            $conditions_where[] = '`ANNONCE`.`date_heure_fin` <= NOW()';
        }

        if ($criteres['prix_minimum'] !== '') {
            $conditions_having[] = 'COALESCE(MAX(`ENCHERE`.`montant`), `ANNONCE`.`prix_depart`) >= :prix_minimum';
            $parametres[':prix_minimum'] = $criteres['prix_minimum'];
        }

        if ($criteres['prix_maximum'] !== '') {
            $conditions_having[] = 'COALESCE(MAX(`ENCHERE`.`montant`), `ANNONCE`.`prix_depart`) <= :prix_maximum';
            $parametres[':prix_maximum'] = $criteres['prix_maximum'];
        }

        // Le cahier des charges demande zéro résultat, sans erreur, lorsque le minimum dépasse le maximum.
        if ($criteres['fourchette_inversee'] === true) {
            $conditions_having[] = '1 = 0';
        }

        $where = '';
        $having = '';

        if (!empty($conditions_where)) {
            $where = 'WHERE ' . implode(' AND ', $conditions_where);
        }

        if (!empty($conditions_having)) {
            $having = 'HAVING ' . implode(' AND ', $conditions_having);
        }

        return [
            'where' => $where,
            'having' => $having,
            'parametres' => $parametres
        ];
    }

}
