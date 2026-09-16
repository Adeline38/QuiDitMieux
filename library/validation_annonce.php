<?php
// Rôle : Fournir les quatre états affichés dans le formulaire d'annonce.
// Paramètres : Aucun paramètre car cette liste est fixée par le projet.
// Retour : Un tableau contenant la valeur technique et le libellé de chaque état.
//// TYPE : name.php
// Rôle : Regrouper les contrôles serveur propres à la création et à la modification d'une annonce.
// Paramètres : Les fonctions reçoivent les valeurs POST ou les fichiers envoyés par le navigateur.
// Retour : Les fonctions fournissent des valeurs normalisées ou des messages fonctionnels.

function obtenir_etats_annonce()
{
    return [
        ['valeur' => 'neuf', 'libelle' => 'Neuf'],
        ['valeur' => 'tres_bon_etat', 'libelle' => 'Très bon état'],
        ['valeur' => 'bon_etat', 'libelle' => 'Bon état'],
        ['valeur' => 'etat_correct', 'libelle' => 'État correct']
    ];
}

// Rôle : Fournir les valeurs vides attendues par le formulaire d'annonce.
// Paramètres : Aucun paramètre car tous les champs sont connus.
// Retour : Un tableau vide prêt pour une création ou une modification.
function obtenir_valeurs_annonce_vides()
{
    return [
        'titre' => '', 'categorie_id' => '', 'description' => '', 'etat' => '',
        'prix_depart' => '', 'date_fin' => '', 'heure_fin' => ''
    ];
}

// Rôle : Fournir un emplacement vide pour chaque erreur du formulaire d'annonce.
// Paramètres : Aucun paramètre car tous les contrôles sont connus.
// Retour : Un tableau de messages vides prêt à être complété.
function obtenir_erreurs_annonce_vides()
{
    return [
        'titre' => '', 'categorie_id' => '', 'description' => '', 'etat' => '',
        'prix_depart' => '', 'date_fin' => '', 'heure_fin' => '', 'photographies' => ''
    ];
}

// Rôle : Lire un champ texte du formulaire sans provoquer d'erreur avec une donnée mal formée.
// Paramètres : $nom_champ contient le nom exact attendu dans POST.
// Retour : Le texte sans espaces extérieurs, ou une chaîne vide si le champ est absent ou invalide.
function lire_champ_annonce($nom_champ)
{
    if (!isset($_POST[$nom_champ]) || !is_string($_POST[$nom_champ])) {
        return '';
    }

    return trim($_POST[$nom_champ]);
}

// Rôle : Vérifier le titre obligatoire d'une annonce.
// Paramètres : $titre contient le texte nettoyé reçu par le contrôleur.
// Retour : Une chaîne vide si le titre est valide, sinon un message fonctionnel.
function valider_titre_annonce($titre)
{
    if ($titre === '') {
        return 'Le titre est obligatoire.';
    }

    if (mb_strlen($titre, 'UTF-8') > 255) {
        return 'Le titre ne peut pas dépasser 255 caractères.';
    }

    return '';
}

// Rôle : Vérifier la description obligatoire d'une annonce.
// Paramètres : $description contient le texte nettoyé reçu par le contrôleur.
// Retour : Une chaîne vide si la description est valide, sinon un message fonctionnel.
function valider_description_annonce($description)
{
    if ($description === '') {
        return 'La description est obligatoire.';
    }

    if (strlen($description) > 65535) {
        return 'La description est trop longue.';
    }

    return '';
}

// Rôle : Vérifier que l'état reçu appartient aux quatre valeurs enregistrables en base.
// Paramètres : $etat contient la valeur technique du formulaire et $etats_autorises la liste blanche.
// Retour : Une chaîne vide si l'état est autorisé, sinon un message fonctionnel.
function valider_etat_annonce($etat, $etats_autorises)
{
    if (!in_array($etat, $etats_autorises, true)) {
        return 'Vous devez choisir un état valide.';
    }

    return '';
}

// Rôle : Contrôler et normaliser un prix compatible avec DECIMAL(6,2).
// Paramètres : $prix contient la valeur reçue, avec un point ou une virgule comme séparateur.
// Retour : Le prix avec deux décimales, ou null si la valeur est invalide.
function preparer_prix_annonce($prix)
{
    $prix_normalise = str_replace(',', '.', $prix);

    if (!preg_match('/^[0-9]{1,4}([.][0-9]{1,2})?$/', $prix_normalise)) {
        return null;
    }

    $prix_nombre = (float) $prix_normalise;

    if ($prix_nombre <= 0 || $prix_nombre > 9999.99) {
        return null;
    }

    return number_format($prix_nombre, 2, '.', '');
}

// Rôle : Réunir et contrôler la date et l'heure futures choisies pour la fin de vente.
// Paramètres : $date et $heure contiennent les deux champs du formulaire.
// Retour : Une valeur DATETIME prête pour MySQL, ou une chaîne vide si l'échéance est invalide.
function preparer_fin_annonce($date, $heure)
{
    if ($date === '' || $heure === '') {
        return '';
    }

    $fin = DateTime::createFromFormat('!Y-m-d H:i', $date . ' ' . $heure);

    if ($fin === false || $fin->format('Y-m-d H:i') !== $date . ' ' . $heure) {
        return '';
    }

    if ($fin <= new DateTime()) {
        return '';
    }

    return $fin->format('Y-m-d H:i:s');
}

// Rôle : Contrôler ensemble les champs communs à la création et à la modification d'une annonce.
// Paramètres : Les valeurs viennent du formulaire, les états du projet et les catégories de l'API.
// Retour : Les erreurs et les trois valeurs préparées pour un futur enregistrement.
function valider_donnees_annonce($valeurs, $etats, $modele_categorie, $categories)
{
    $erreurs = obtenir_erreurs_annonce_vides();
    $categorie_selectionnee = null;
    $prix_prepare = null;
    $fin_preparee = '';

    // Les textes obligatoires et l'état sont vérifiés avant tout enregistrement.
    $erreurs['titre'] = valider_titre_annonce($valeurs['titre']);
    $erreurs['description'] = valider_description_annonce($valeurs['description']);
    $valeurs_etats = [];
    foreach ($etats as $etat) {
        $valeurs_etats[] = $etat['valeur'];
    }
    $erreurs['etat'] = valider_etat_annonce($valeurs['etat'], $valeurs_etats);

    // La catégorie doit appartenir à la liste réellement reçue depuis l'API.
    $categorie_selectionnee = $modele_categorie->trouver_dans_liste(
        $valeurs['categorie_id'],
        $categories
    );
    if ($categorie_selectionnee === null) {
        $erreurs['categorie_id'] = 'Vous devez choisir une catégorie valide.';
    }

    // Le prix et la date sont préparés dans les formats attendus par MySQL.
    $prix_prepare = preparer_prix_annonce($valeurs['prix_depart']);
    if ($prix_prepare === null) {
        $erreurs['prix_depart'] = 'Le prix doit être positif, valide et comporter au maximum deux décimales.';
    }
    $fin_preparee = preparer_fin_annonce($valeurs['date_fin'], $valeurs['heure_fin']);
    if ($valeurs['date_fin'] === '') {
        $erreurs['date_fin'] = 'La date de fin est obligatoire.';
    }
    if ($valeurs['heure_fin'] === '') {
        $erreurs['heure_fin'] = 'L’heure de fin est obligatoire.';
    }
    if ($fin_preparee === '' && $erreurs['date_fin'] === '' && $erreurs['heure_fin'] === '') {
        $erreurs['date_fin'] = 'La date et l’heure de fin doivent être valides et futures.';
    }

    return [
        'erreurs' => $erreurs,
        'categorie' => $categorie_selectionnee,
        'prix' => $prix_prepare,
        'fin' => $fin_preparee
    ];
}

// Rôle : Vérifier côté serveur les nouvelles photographies avant leur déplacement définitif.
// Paramètres : $nombre_maximum indique les places encore disponibles et les fichiers proviennent de FILES.
// Retour : Un tableau contenant les fichiers validés et un éventuel message d'erreur.
function preparer_photographies_annonce($nombre_maximum = 3)
{
    $resultat = [
        'photographies' => [],
        'erreur' => ''
    ];

    if (!isset($_FILES['photographies'])) {
        return $resultat;
    }

    $fichiers = $_FILES['photographies'];

    if (!isset($fichiers['name'], $fichiers['tmp_name'], $fichiers['error'], $fichiers['size'])) {
        $resultat['erreur'] = 'Les photographies reçues sont incomplètes.';
        return $resultat;
    }

    if (!is_array($fichiers['name']) || !is_array($fichiers['tmp_name']) || !is_array($fichiers['error']) || !is_array($fichiers['size'])) {
        $resultat['erreur'] = 'Les photographies reçues sont mal formées.';
        return $resultat;
    }

    $indices = array_keys($fichiers['name']);
    $indices_fichiers = [];

    foreach ($indices as $indice) {
        if (isset($fichiers['error'][$indice]) && $fichiers['error'][$indice] !== UPLOAD_ERR_NO_FILE) {
            $indices_fichiers[] = $indice;
        }
    }

    if (count($indices_fichiers) > $nombre_maximum) {
        $resultat['erreur'] = 'Le nombre total de photographies conservées et ajoutées ne peut pas dépasser trois.';
        return $resultat;
    }

    $analyseur_mime = finfo_open(FILEINFO_MIME_TYPE);

    if ($analyseur_mime === false && !empty($indices_fichiers)) {
        $resultat['erreur'] = 'Les photographies ne peuvent pas être vérifiées pour le moment.';
        return $resultat;
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    foreach ($indices_fichiers as $indice) {
        if (!isset($fichiers['tmp_name'][$indice], $fichiers['size'][$indice], $fichiers['error'][$indice])) {
            $resultat['erreur'] = 'Une photographie reçue est incomplète.';
            break;
        }

        if ($fichiers['error'][$indice] !== UPLOAD_ERR_OK) {
            $resultat['erreur'] = 'Une photographie n’a pas pu être envoyée correctement.';
            break;
        }

        if (!is_uploaded_file($fichiers['tmp_name'][$indice])) {
            $resultat['erreur'] = 'Une photographie reçue n’est pas un fichier téléversé valide.';
            break;
        }

        if ((int) $fichiers['size'][$indice] < 1 || (int) $fichiers['size'][$indice] > 5 * 1024 * 1024) {
            $resultat['erreur'] = 'Chaque photographie doit peser au maximum 5 Mo.';
            break;
        }

        $type_mime = finfo_file($analyseur_mime, $fichiers['tmp_name'][$indice]);

        if (!isset($extensions[$type_mime])) {
            $resultat['erreur'] = 'Seules les photographies JPEG, PNG et WebP sont acceptées.';
            break;
        }

        $resultat['photographies'][] = [
            'chemin_temporaire' => $fichiers['tmp_name'][$indice],
            'extension' => $extensions[$type_mime]
        ];
    }

    if ($analyseur_mime !== false) {
        finfo_close($analyseur_mime);
    }

    if ($resultat['erreur'] !== '') {
        $resultat['photographies'] = [];
    }

    return $resultat;
}

// Rôle : Vérifier les identifiants des photographies que le vendeur demande de supprimer.
// Paramètres : $valeurs_recues vient de POST et $photographies_existantes contient les objets liés à l'annonce.
// Retour : Un tableau d'identifiants uniques autorisés, ou null si une valeur est inconnue ou mal formée.
function preparer_photos_a_supprimer($valeurs_recues, $photographies_existantes)
{
    if ($valeurs_recues === null) {
        return [];
    }

    if (!is_array($valeurs_recues)) {
        return null;
    }

    $identifiants_autorises = [];

    foreach ($photographies_existantes as $photographie) {
        $identifiants_autorises[] = (int) $photographie->id();
    }

    $identifiants_valides = [];

    // Chaque identifiant doit être positif, appartenir à l'annonce et ne figurer qu'une seule fois.
    foreach ($valeurs_recues as $valeur_recue) {
        $identifiant = filter_var(
            $valeur_recue,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($identifiant === false || !in_array((int) $identifiant, $identifiants_autorises, true)) {
            return null;
        }

        if (!in_array((int) $identifiant, $identifiants_valides, true)) {
            $identifiants_valides[] = (int) $identifiant;
        }
    }

    return $identifiants_valides;
}

// Rôle : Vérifier que la photographie principale choisie fait bien partie des photographies finales.
// Paramètres : $choix vient de POST, $ids_existants contient les photos conservées et $nombre_nouvelles compte les nouveaux fichiers valides.
// Retour : Le type et la valeur du choix, un choix automatique si le champ est vide, ou null si le choix est incohérent.
function preparer_choix_photo_principale($choix, $ids_existants, $nombre_nouvelles)
{
    if (!is_string($choix)) {
        return null;
    }

    // Un formulaire sans JavaScript conserve le comportement simple : la première photographie devient principale.
    if ($choix === '') {
        return [
            'type' => 'automatique',
            'valeur' => 0
        ];
    }

    if (strpos($choix, 'existante:') === 0) {
        $identifiant_recu = substr($choix, strlen('existante:'));
        $identifiant = filter_var(
            $identifiant_recu,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($identifiant === false || !in_array((int) $identifiant, $ids_existants, true)) {
            return null;
        }

        return [
            'type' => 'existante',
            'valeur' => (int) $identifiant
        ];
    }

    if (strpos($choix, 'nouvelle:') === 0) {
        $index_recu = substr($choix, strlen('nouvelle:'));
        $index = filter_var(
            $index_recu,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        if ($index === false || (int) $index >= (int) $nombre_nouvelles) {
            return null;
        }

        return [
            'type' => 'nouvelle',
            'valeur' => (int) $index
        ];
    }

    return null;
}

// Rôle : Déterminer pourquoi une annonce ne peut pas être modifiée ou supprimée par un utilisateur.
// Paramètres : $situation vient du modèle annonce et $utilisateur_id vient de la session connectée.
// Retour : Une chaîne vide si l'action est autorisée, sinon un code fonctionnel simple.
function determiner_refus_action_annonce($situation, $utilisateur_id)
{
    if ($situation === null) {
        return 'introuvable';
    }

    if ((int) $situation['utilisateur_id'] !== (int) $utilisateur_id) {
        return 'autre_vendeur';
    }

    if ((int) $situation['vente_ouverte'] !== 1) {
        return 'terminee';
    }

    if ((int) $situation['nombre_encheres'] > 0) {
        return 'avec_enchere';
    }

    return '';
}
