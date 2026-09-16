<?php // CONTRÔLEUR : afficher_modification_annonce.php
// Rôle : Vérifier le droit du vendeur et préparer le formulaire prérempli d'une annonce.
// Paramètres : 
//      - GET fournit l'identifiant de l'annonce
//      - la session fournit l'utilisateur connecté (le compte doit être propriétaire d’une annonce encore modifiable)
// Retour : Le template annonce.php est affiché ou le navigateur revient au détail avec un message.



// Les listes communes évitent de recopier les mêmes champs dans plusieurs contrôleurs.
require_once 'library/validation_annonce.php';

// Étape 1 : On prépare toutes les valeurs attendues par le formulaire de modification.
$est_connecte = false;
$pseudo_connecte = '';
$jeton_csrf = '';
$categories = [];
$publication_disponible = false;
$erreur_generale = '';
$methode_http = '';
$mode_modification = true;
$annonce_id = 0;
$photographies_existantes = [];
$identifiants_photos_supprimees = [];
$photo_principale_choisie = '';
$date_minimale = date('Y-m-d');
$etats_annonce = obtenir_etats_annonce();
$valeurs_annonce = obtenir_valeurs_annonce_vides();
$erreurs_annonce = obtenir_erreurs_annonce_vides();

// Étape 2 : On charge la fonction qui protège les textes affichés dans le template.
require_once 'library/fonctions.php';

// Étape 3 : On lit la méthode utilisée pour demander la page.
if (isset($_SERVER['REQUEST_METHOD'])) {
    $methode_http = $_SERVER['REQUEST_METHOD'];
}

// Étape 4 : Ce contrôleur consulte l'annonce et accepte donc uniquement la méthode GET.
if ($methode_http !== 'GET') {
    exit('Cette page est accessible uniquement en consultation.');
}

// Étape 5 : On charge la session, la sécurité, les validations et les modèles nécessaires.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'model/annonce.php';
require_once 'model/photo.php';
require_once 'model/categorie.php';
require_once 'model/utilisateur.php';

// Étape 6 : Modifier une annonce est une action privée, donc un compte existant est obligatoire.
$compte_connecte = exiger_compte_connecte();

$est_connecte = true;
$pseudo_connecte = $compte_connecte->get('pseudo');
$jeton_csrf = obtenir_jeton_csrf();

// Étape 7 : On accepte uniquement un identifiant d'annonce entier et positif.
$annonce_id = lire_entier_positif($_GET, 'id');

if ($annonce_id < 1) {
    header('Location: afficher_detail_annonce.php?id=0');
    exit;
}

// Étape 8 : On vérifie que l'annonce appartient au compte, qu'elle est ouverte et sans enchère.
$modele_annonce = new annonce();
$situation = $modele_annonce->consulter_situation_action($annonce_id);
$refus = determiner_refus_action_annonce($situation, idConnected());

if ($refus !== '') {
    if ($refus !== 'introuvable') {
        ajouter_message_flash('detail_annonce_erreur', 'Cette annonce ne peut pas être modifiée. Elle doit être en cours, sans enchère et vous appartenir.');
    }

    header('Location: afficher_detail_annonce.php?id=' . $annonce_id);
    exit;
}

// Étape 9 : On prépare les valeurs actuelles et on sépare la date de l'heure pour le formulaire.
$valeurs_annonce['titre'] = $situation['titre'];
$valeurs_annonce['categorie_id'] = (string) $situation['categorie_id'];
$valeurs_annonce['description'] = $situation['description'];
$valeurs_annonce['etat'] = $situation['etat'];
$valeurs_annonce['prix_depart'] = $situation['prix_depart'];
$fin = DateTime::createFromFormat('Y-m-d H:i:s', $situation['date_heure_fin']);

if ($fin !== false) {
    $valeurs_annonce['date_fin'] = $fin->format('Y-m-d');
    $valeurs_annonce['heure_fin'] = $fin->format('H:i');
}

// Étape 10 : La boucle prépare les photographies existantes et repère la photographie principale.
$modele_photo = new photo();
$photographies = $modele_photo->lister_par_annonce($annonce_id);

foreach ($photographies as $photographie) {
    $url_photo = preparer_url_photo_annonce($photographie->get('nom'));

    $photographies_existantes[] = [
        'id' => (int) $photographie->id(),
        'url' => $url_photo,
        'position' => (int) $photographie->get('position'),
        'alt' => 'Photographie de ' . $situation['titre']
    ];

    if ((int) $photographie->get('position') === 1) {
        $photo_principale_choisie = 'existante:' . (int) $photographie->id();
    }
}

// Étape 11 : L'API fournit les catégories autorisées pour la nouvelle validation du formulaire.
$modele_categorie = new categorie();
$categories_recues = $modele_categorie->lister();

if ($categories_recues === null) {
    $erreur_generale = 'Les catégories ne peuvent pas être chargées pour le moment.';
} else {
    $categories = $categories_recues;
    $publication_disponible = true;
}

// Étape 12 : Le template reçoit les informations actuelles et les droits préparés ci-dessus.
require_once 'templates/pages/annonce.php';
