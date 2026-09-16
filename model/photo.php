<?php
// MODÈLE : photo.php
// Rôle : Définir le modèle qui gère les photographies associées aux annonces.
// Paramètres : Les méthodes reçoivent l'identifiant de l'annonce concernée.
// Retour : Les méthodes retournent des objets photo ou une absence exploitable par le contrôleur.

class photo extends _model
{
    // Ces informations relient ce modèle à la table PHOTO et limitent les colonnes qu'il peut manipuler.
    protected $table = 'PHOTO';

    protected $fields = [
        'nom',
        'position',
        'annonce_id'
    ];

    public function enregistrer_photo($nom, $position, $annonce_id) {
        // Rôle : Enregistrer le nom et la position d'une photographie liée à une annonce.
        // Paramètres : $nom est le nom sécurisé, $position va de 1 à 3 et $annonce_id désigne l'annonce.
        // Retour : true si l'enregistrement réussit, sinon false.
        
        $this->set('nom', $nom);
        $this->set('position', $position);
        $this->set('annonce_id', $annonce_id);

        return $this->insert();
    }

    public function lister_par_annonce($annonce_id) {
        // Rôle : Lister toutes les photographies d'une annonce dans leur ordre d'affichage.
        // Paramètres : $annonce_id est l'identifiant de l'annonce consultée.
        // Retour : Un tableau d'objets photo, ou un tableau vide si aucune photographie n'existe.
        
        $sql = "SELECT `id`, `nom`, `position`, `annonce_id`
                FROM `PHOTO`
                WHERE `annonce_id` = :annonce_id
                ORDER BY `position` ASC, `id` ASC";

        return $this->sqlToTab(
            $sql,
            [':annonce_id' => $annonce_id]
        );
    }

    public function trouver_principale($annonce_id) {
        // Rôle : Trouver la première photographie d'une annonce pour illustrer sa carte.
        // Paramètres : $annonce_id est l'identifiant de l'annonce recherchée.
        // Retour : Un objet photo si une photographie existe, sinon null.
        
        // Le classement par position puis par identifiant rend le choix stable lorsqu'une annonce possède plusieurs photos.
        $sql = "SELECT `id`, `nom`, `position`, `annonce_id`
                FROM `PHOTO`
                WHERE `annonce_id` = :annonce_id
                ORDER BY `position` ASC, `id` ASC
                LIMIT 1";

        // L'identifiant reste séparé du texte SQL afin que PDO le transmette comme paramètre préparé.
        $parametres = [
            ':annonce_id' => $annonce_id
        ];

        // Une seule photographie est attendue ; la classe parente retourne donc un objet ou null.
        return $this->sqlToObject($sql, $parametres);
    }

    public function reordonner_par_annonce($annonce_id) {
        // Rôle : Recalculer les positions après la suppression d'une ou plusieurs photographies.
        // Paramètres : $annonce_id désigne l'annonce dont les photographies restantes doivent être compactées.
        // Retour : true si toutes les positions sont cohérentes, sinon false.
        
        $photographies = $this->lister_par_annonce($annonce_id);
        $position = 1;

        // Les positions diminuent seulement vers une place libre, ce qui respecte l'unicité imposée par la base.
        foreach ($photographies as $photographie) {
            if ((int) $photographie->get('position') !== $position) {
                $photographie->set('position', $position);

                if (!$photographie->update()) {
                    return false;
                }
            }

            $position++;
        }

        return true;
    }

    public function definir_principale($annonce_id, $photo_id) {
        // Rôle : Placer une photographie choisie en première position sans enfreindre l'unicité des positions 1 à 3.
        // Paramètres : $annonce_id désigne l'annonce et $photo_id désigne une photographie qui lui appartient.
        // Retour : true si la photographie est déjà principale ou si son contenu a été échangé avec la première, sinon false.
        
        $photographies = $this->lister_par_annonce($annonce_id);
        $photographie_principale = null;
        $photographie_choisie = null;

        // Les objets sont recherchés dans la liste de l'annonce afin d'interdire l'identifiant d'une autre annonce.
        foreach ($photographies as $photographie) {
            if ((int) $photographie->get('position') === 1) {
                $photographie_principale = $photographie;
            }

            if ((int) $photographie->id() === (int) $photo_id) {
                $photographie_choisie = $photographie;
            }
        }

        if ($photographie_principale === null || $photographie_choisie === null) {
            return false;
        }

        if ((int) $photographie_principale->id() === (int) $photographie_choisie->id()) {
            return true;
        }

        // La base interdit deux photos à la même position et limite les positions à 1, 2 ou 3.
        // Échanger leurs noms déplace donc les deux images sans créer une position temporaire interdite.
        $nom_principal = $photographie_principale->get('nom');
        $nom_choisi = $photographie_choisie->get('nom');
        $photographie_principale->set('nom', $nom_choisi);
        $photographie_choisie->set('nom', $nom_principal);

        if (!$photographie_principale->update()) {
            return false;
        }

        return $photographie_choisie->update();
    }

}
