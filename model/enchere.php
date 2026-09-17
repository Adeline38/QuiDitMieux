<?php
// MODÈLE : enchere.php
// Rôle : Définir le modèle qui consulte les enchères et les informations calculées d'une vente.
// Paramètres : Les méthodes reçoivent l'identifiant de l'annonce concernée.
// Retour : Les méthodes retournent des données d'enchères préparées pour un contrôleur.

class enchere extends _model
{
    // Ces informations relient ce modèle à la table ENCHERE et limitent les colonnes qu'il peut manipuler.
    protected $table = 'enchere';

    protected $fields = [
        'montant',
        'date_heure',
        'annonce_id',
        'utilisateur_id'
    ];

    public function resumer_annonce($annonce_id) {
        // Rôle : Calculer le nombre d'enchères et le prix courant d'une annonce.
        // Paramètres : $annonce_id est l'identifiant de l'annonce recherchée.
        // Retour : Un tableau récapitulatif, ou null si la lecture échoue.
        
        // La jointure conserve l'annonce même sans enchère et utilise l'heure MySQL comme référence commune pour sa fin.
        $sql = "SELECT
                    COUNT(`enchere`.`id`) AS `nombre_encheres`,
                    MAX(`enchere`.`montant`) AS `prix_courant`,
                    MAX(`enchere`.`date_heure`) AS `derniere_enchere`,
                    (`annonce`.`date_heure_fin` > NOW()) AS `vente_ouverte`
                FROM `annonce`
                LEFT JOIN `enchere`
                    ON `enchere`.`annonce_id` = `annonce`.`id`
                WHERE `annonce`.`id` = :annonce_id
                GROUP BY `annonce`.`id`, `annonce`.`date_heure_fin`";

        // L'identifiant reste séparé du texte SQL afin que PDO le transmette comme paramètre préparé.
        $parametres = [
            ':annonce_id' => $annonce_id
        ];

        // Le contrôleur a besoin d'un tableau récapitulatif et non d'un objet enchère individuel.
        return $this->sqlToLigne($sql, $parametres);
    }

    public function trouver_meilleure_enchere($annonce_id) {
        // Rôle : Trouver l'enchère la plus élevée d'une annonce avec le pseudo de son auteur.
        // Paramètres : $annonce_id est l'identifiant de l'annonce recherchée.
        // Retour : Un tableau décrivant la meilleure enchère, ou null lorsqu'aucune enchère n'existe.

        // Le tri place le montant le plus élevé en premier ; la limite évite de charger les autres enchères.
        $sql = "SELECT
                    `enchere`.`id` AS `id`,
                    `enchere`.`montant` AS `montant`,
                    `enchere`.`date_heure` AS `date_heure`,
                    `enchere`.`utilisateur_id` AS `utilisateur_id`,
                    `utilisateur`.`pseudo` AS `pseudo`
                FROM `enchere`
                INNER JOIN `utilisateur`
                    ON `utilisateur`.`id` = `enchere`.`utilisateur_id`
                WHERE `enchere`.`annonce_id` = :annonce_id
                ORDER BY `enchere`.`montant` DESC
                LIMIT 1";

        return $this->sqlToLigne($sql, [
            ':annonce_id' => $annonce_id
        ]);
    }

    public function utilisateur_a_participe($annonce_id, $utilisateur_id) {
        // Rôle : Vérifier si un utilisateur a déjà placé au moins une enchère sur une annonce.
        // Paramètres : $annonce_id identifie l'annonce et $utilisateur_id identifie l'utilisateur.
        // Retour : true si une participation existe, sinon false.
        
        // COUNT permet de répondre sans transmettre les montants ou les autres participants.
        $sql = "SELECT COUNT(`id`) AS `nombre`
                FROM `enchere`
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

    public function lister_historique($annonce_id) {
        // Rôle : Lister l'historique privé d'une annonce de la plus récente à la plus ancienne.
        // Paramètres : $annonce_id est l'identifiant de l'annonce dont l'historique est autorisé.
        // Retour : Un tableau de lignes contenant seulement le pseudo, le montant et la date, ou un tableau vide.

        // La jointure récupère uniquement le pseudo public de chaque enchérisseur, jamais son courriel.
        $sql = "SELECT
                    `utilisateur`.`pseudo` AS `pseudo`,
                    `enchere`.`montant` AS `montant`,
                    `enchere`.`date_heure` AS `date_heure`
                FROM `enchere`
                INNER JOIN `utilisateur`
                    ON `utilisateur`.`id` = `enchere`.`utilisateur_id`
                WHERE `enchere`.`annonce_id` = :annonce_id
                ORDER BY `enchere`.`date_heure` DESC, `enchere`.`id` DESC";

        $requete = $this->execute($sql, [
            ':annonce_id' => $annonce_id
        ]);

        if ($requete === false) {
            return [];
        }

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public function enregistrer_enchere($annonce_id, $utilisateur_id, $montant) {
        // Rôle : Enregistrer une enchère après avoir revérifié toutes les règles dans une transaction verrouillée.
        // Paramètres : Les identifiants désignent l'annonce et l'utilisateur ; $montant contient un montant validé à deux décimales.
        // Retour : Un code simple indique la réussite ou la raison fonctionnelle du refus.
        
        global $bdd;

        // Une transaction déjà ouverte ne doit pas être mélangée avec le placement de l'enchère.
        if ($bdd->inTransaction()) {
            return 'erreur';
        }

        if (!$bdd->beginTransaction()) {
            return 'erreur';
        }

        // FOR UPDATE réserve temporairement cette annonce : une autre enchère attendra la fin de cette transaction.
        $sql_annonce = "SELECT
                            `id`,
                            `prix_depart`,
                            `utilisateur_id`,
                            (`date_heure_fin` > NOW()) AS `vente_ouverte`
                        FROM `annonce`
                        WHERE `id` = :annonce_id
                        FOR UPDATE";

        $annonce = $this->sqlToLigne($sql_annonce, [
            ':annonce_id' => $annonce_id
        ]);

        if ($annonce === null) {
            $bdd->rollBack();
            return 'annonce_introuvable';
        }

        // Les droits sont revérifiés après le verrouillage, au moment réel de l'écriture.
        if ((int) $annonce['utilisateur_id'] === (int) $utilisateur_id) {
            $bdd->rollBack();
            return 'annonce_personnelle';
        }

        if ((int) $annonce['vente_ouverte'] !== 1) {
            $bdd->rollBack();
            return 'vente_terminee';
        }

        // La meilleure enchère est relue pendant la transaction pour obtenir le véritable prix courant.
        $sql_meilleure = "SELECT `montant`, `utilisateur_id`
                          FROM `enchere`
                          WHERE `annonce_id` = :annonce_id
                          ORDER BY `montant` DESC
                          LIMIT 1";

        $meilleure_enchere = $this->sqlToLigne($sql_meilleure, [
            ':annonce_id' => $annonce_id
        ]);
        $prix_courant = $annonce['prix_depart'];

        if ($meilleure_enchere !== null) {
            $prix_courant = $meilleure_enchere['montant'];

            if ((int) $meilleure_enchere['utilisateur_id'] === (int) $utilisateur_id) {
                $bdd->rollBack();
                return 'deja_plus_offrant';
            }
        }

        // Les valeurs ont deux décimales : leur conversion en centimes permet une comparaison entière simple.
        $montant_centimes = $this->convertir_en_centimes($montant);
        $prix_courant_centimes = $this->convertir_en_centimes($prix_courant);

        if ($montant_centimes <= $prix_courant_centimes) {
            $bdd->rollBack();
            return 'montant_insuffisant';
        }

        // La première participation doit aussi créer le suivi prévu par le cahier des charges.
        $sql_participation = "SELECT COUNT(`id`) AS `nombre`
                              FROM `enchere`
                              WHERE `annonce_id` = :annonce_id
                              AND `utilisateur_id` = :utilisateur_id";

        $participation = $this->sqlToLigne($sql_participation, [
            ':annonce_id' => $annonce_id,
            ':utilisateur_id' => $utilisateur_id
        ]);

        if ($participation === null) {
            $bdd->rollBack();
            return 'erreur';
        }

        $premiere_participation = (int) $participation['nombre'] === 0;

        // Toutes les valeurs variables restent séparées du SQL dans des paramètres préparés.
        $sql_insertion = "INSERT INTO `enchere`
                            (`montant`, `date_heure`, `annonce_id`, `utilisateur_id`)
                          VALUES
                            (:montant, NOW(), :annonce_id, :utilisateur_id)";

        $insertion = $this->execute($sql_insertion, [
            ':montant' => $montant,
            ':annonce_id' => $annonce_id,
            ':utilisateur_id' => $utilisateur_id
        ]);

        if ($insertion === false) {
            $bdd->rollBack();
            return 'erreur';
        }

        // La première enchère ajoute le suivi seulement s'il n'existait pas déjà volontairement.
        if ($premiere_participation === true) {
            $sql_suivi_existant = "SELECT COUNT(`id`) AS `nombre`
                                    FROM `asso_utilisateur_annonce`
                                    WHERE `annonce_id` = :annonce_id
                                    AND `utilisateur_id` = :utilisateur_id";

            $suivi_existant = $this->sqlToLigne($sql_suivi_existant, [
                ':annonce_id' => $annonce_id,
                ':utilisateur_id' => $utilisateur_id
            ]);

            if ($suivi_existant === null) {
                $bdd->rollBack();
                return 'erreur';
            }

            if ((int) $suivi_existant['nombre'] === 0) {
                $sql_suivi = "INSERT INTO `asso_utilisateur_annonce`
                                (`annonce_id`, `utilisateur_id`)
                              VALUES
                                (:annonce_id, :utilisateur_id)";

                $suivi = $this->execute($sql_suivi, [
                    ':annonce_id' => $annonce_id,
                    ':utilisateur_id' => $utilisateur_id
                ]);

                if ($suivi === false) {
                    $bdd->rollBack();
                    return 'erreur';
                }
            }
        }

        if (!$bdd->commit()) {
            if ($bdd->inTransaction()) {
                $bdd->rollBack();
            }

            return 'erreur';
        }

        return 'succes';
    }

    private function convertir_en_centimes($montant) {
        // Rôle : Transformer un montant possédant deux décimales en un nombre entier de centimes.
        // Paramètres : $montant contient une valeur DECIMAL provenant de la validation ou de MySQL.
        // Retour : Un nombre entier utilisable pour comparer deux montants sans arrondi intermédiaire.
        
        $parties = explode('.', (string) $montant);
        $euros = (int) $parties[0];
        $centimes = '00';

        if (isset($parties[1])) {
            $centimes = str_pad($parties[1], 2, '0');
            $centimes = substr($centimes, 0, 2);
        }

        return ($euros * 100) + (int) $centimes;
    }

}
