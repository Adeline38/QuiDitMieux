<?php // CONTRÔLEUR : enregistrer_creation_annonce.php
// Rôle : Valider et enregistrer une annonce avec zéro à trois photographies.
// Paramètres : 
//      - POST fournit titre, identifiant de categorie, description, etat, prix de depart, date de fin, heure de fin, photo principale et jeton_csrf 
//      - la session fournit l'utilisateur connecté (le vendeur)
//      - FILES peut contenir zéro à trois photographies
// Retour : Une redirection vers le détail créé ou le template annonce.php avec des erreurs fonctionnelles



// Les listes communes évitent de recopier les mêmes champs dans plusieurs contrôleurs.
require_once 'library/validation_annonce.php';

// Étape 1 : On prépare toutes les valeurs nécessaires pour pouvoir réafficher le formulaire après une erreur.
$est_connecte = false;
$pseudo_connecte = '';
$jeton_csrf = '';
$categories = [];
$publication_disponible = false;
$erreur_generale = '';
$mode_modification = false;
$annonce_id = 0;
$photographies_existantes = [];
$identifiants_photos_supprimees = [];
$photo_principale_choisie = '';
$date_minimale = date('Y-m-d');
$etats_annonce = obtenir_etats_annonce();
$valeurs_annonce = obtenir_valeurs_annonce_vides();
$erreurs_annonce = obtenir_erreurs_annonce_vides();

// Étape 2 : La création modifie la base et accepte donc uniquement le formulaire POST.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Cette action attend l’envoi du formulaire de publication.');
}

// Étape 3 : On charge la session, la sécurité, les validations et les modèles nécessaires.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'model/annonce.php';
require_once 'model/photo.php';
require_once 'model/categorie.php';
require_once 'model/utilisateur.php';

// Étape 4 : Publier une annonce est une action privée, donc un compte existant est obligatoire.
$compte_connecte = exiger_compte_connecte();

$est_connecte = true;
$pseudo_connecte = $compte_connecte->get('pseudo');
$jeton_csrf = obtenir_jeton_csrf();

// Étape 5 : La boucle lit les champs non sensibles pour pouvoir les remettre dans le formulaire en erreur.
foreach ($valeurs_annonce as $nom_champ => $valeur_initiale) {
    $valeurs_annonce[$nom_champ] = lire_champ_annonce($nom_champ);
}

$photo_principale_choisie = lire_champ_annonce('photo_principale');

// Étape 6 : L'API fournit les catégories et permettra de vérifier le choix reçu.
$modele_categorie = new categorie();
$categories_recues = $modele_categorie->lister();

if ($categories_recues === null) {
    $erreur_generale = 'Les catégories ne peuvent pas être vérifiées pour le moment.';
} else {
    $categories = $categories_recues;
    $publication_disponible = true;
}

$jeton_recu = lire_champ_annonce('jeton_csrf');
$prix_prepare = null;
$fin_preparee = '';
$photographies_preparees = [
    'photographies' => [],
    'erreur' => ''
];

// Étape 7 : Le jeton est vérifié avant les règles métier et la préparation des photographies.
if (!verifier_jeton_csrf($jeton_recu)) {
    $erreur_generale = 'Le formulaire a expiré ou la requête n’est pas autorisée. Veuillez réessayer.';
} elseif ($publication_disponible === true) {
    // On contrôle les textes, l'état, la catégorie, le prix et la date de fin.
    $validation = valider_donnees_annonce($valeurs_annonce, $etats_annonce, $modele_categorie, $categories);
    $erreurs_annonce = $validation['erreurs'];
    $categorie_selectionnee = $validation['categorie'];
    $prix_prepare = $validation['prix'];
    $fin_preparee = $validation['fin'];

    $photographies_preparees = preparer_photographies_annonce();
    $erreurs_annonce['photographies'] = $photographies_preparees['erreur'];

    if ($erreurs_annonce['photographies'] === '') {
        $choix_principale = preparer_choix_photo_principale(
            $photo_principale_choisie,
            [],
            count($photographies_preparees['photographies'])
        );

        if ($choix_principale === null) {
            $erreurs_annonce['photographies'] = 'Le choix de la photographie principale est invalide.';
            $photo_principale_choisie = '';
        }
    }
}

// Étape 8 : La boucle vérifie qu'aucun message d'erreur n'a été préparé.
$formulaire_valide = $erreur_generale === '';

foreach ($erreurs_annonce as $erreur_annonce) {
    if ($erreur_annonce !== '') {
        $formulaire_valide = false;
    }
}

// Étape 9 : Si tout est valide, une transaction réunit l'annonce et ses photographies.
if ($formulaire_valide === true) {
    $modele_annonce = new annonce();
    $transaction_commencee = $modele_annonce->commencer_transaction();

    if (!$transaction_commencee) {
        $erreur_generale = 'L’annonce ne peut pas être enregistrée pour le moment.';
    } else {
        $creation_reussie = $modele_annonce->creer_annonce([
            'titre' => $valeurs_annonce['titre'],
            'description' => $valeurs_annonce['description'],
            'etat' => $valeurs_annonce['etat'],
            'prix_depart' => $prix_prepare,
            'date_heure_fin' => $fin_preparee,
            'categorie_id' => (int) $categorie_selectionnee['id'],
            'utilisateur_id' => idConnected()
        ]);

        $annonce_id = (int) $modele_annonce->id();
        $chemins_deplaces = [];
        $identifiants_nouvelles_photos = [];

        if (!$creation_reussie || $annonce_id < 1) {
            $erreur_generale = 'L’annonce ne peut pas être enregistrée pour le moment.';
        }

        $dossier_photos = 'public/assets/images/photo-objet';

        if ($erreur_generale === '' && !empty($photographies_preparees['photographies']) && (!is_dir($dossier_photos) || !is_writable($dossier_photos))) {
            $erreur_generale = 'Les photographies ne peuvent pas être enregistrées pour le moment.';
        }

        // Étape 10 : Chaque fichier valide est déplacé puis son nom est enregistré dans la base.
        if ($erreur_generale === '') {
            foreach ($photographies_preparees['photographies'] as $index => $photographie_preparee) {
                $position = $index + 1;
                $nom_photo = 'annonce-' . $annonce_id . '-' . bin2hex(random_bytes(8));
                $nom_photo .= '.' . $photographie_preparee['extension'];
                $chemin_photo = $dossier_photos . '/' . $nom_photo;

                if (!move_uploaded_file($photographie_preparee['chemin_temporaire'], $chemin_photo)) {
                    $erreur_generale = 'Une photographie ne peut pas être enregistrée pour le moment.';
                    break;
                }

                $chemins_deplaces[] = $chemin_photo;
                $modele_photo = new photo();

                if (!$modele_photo->enregistrer_photo($nom_photo, $position, $annonce_id)) {
                    $erreur_generale = 'Une photographie ne peut pas être enregistrée pour le moment.';
                    break;
                }

                $identifiants_nouvelles_photos[] = (int) $modele_photo->id();
            }
        }

        // Étape 11 : La photographie principale est choisie après la création des lignes PHOTO.
        if ($erreur_generale === '' && !empty($identifiants_nouvelles_photos)) {
            $photo_principale_id = $identifiants_nouvelles_photos[0];

            if ($choix_principale['type'] === 'nouvelle') {
                $photo_principale_id = $identifiants_nouvelles_photos[$choix_principale['valeur']];
            }

            if (!$modele_photo->definir_principale($annonce_id, $photo_principale_id)) {
                $erreur_generale = 'La photographie principale ne peut pas être enregistrée pour le moment.';
            }
        }

        // Étape 12 : Une erreur annule la transaction et retire seulement les fichiers de cette tentative.
        if ($erreur_generale !== '') {
            $modele_annonce->annuler_transaction();

            // Seuls les fichiers déplacés pendant cette tentative inachevée sont supprimés.
            supprimer_fichiers_annonce($chemins_deplaces);
        } elseif (!$modele_annonce->valider_transaction()) {
            $modele_annonce->annuler_transaction();
            supprimer_fichiers_annonce($chemins_deplaces);
            $erreur_generale = 'L’annonce ne peut pas être enregistrée pour le moment.';
        } else {
            // Si tout réussit, on valide les données puis on affiche le détail de la nouvelle annonce.
            header('Location: afficher_detail_annonce.php?id=' . $annonce_id);
            exit;
        }
    }
}

// Étape 13 : En cas d'erreur, le template réaffiche les valeurs autorisées et les messages préparés.
require_once 'templates/pages/annonce.php';
