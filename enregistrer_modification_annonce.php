<?php // CONTRÔLEUR : enregistrer_modification_annonce.php

// Rôle : Valider puis enregistrer la modification autorisée d'une annonce et de ses photographies.
// Paramètres : 
//      - POST fournit les changements : annonce_id, les champs modifiés, jeton_csrf
//      - FILES fournit le choix de la photographie principale, les photos à supprimer et éventuellement de nouvelles photographies
//      - la session fournit l'identifiant du vendeur
// Retour : Une redirection vers le détail ou le template annonce.php avec des erreurs fonctionnelles



// Les listes communes évitent de recopier les mêmes champs dans plusieurs contrôleurs.
require_once 'library/validation_annonce.php';
// Étape 1 : On prépare toutes les valeurs nécessaires pour pouvoir réafficher le formulaire après une erreur.
$categories = []; $publication_disponible = false;
$erreur_generale = '';
$mode_modification = true;
$photographies_existantes = []; $identifiants_photos_supprimees = [];
$date_minimale = date('Y-m-d');
$etats_annonce = obtenir_etats_annonce();
$valeurs_annonce = obtenir_valeurs_annonce_vides();
$erreurs_annonce = obtenir_erreurs_annonce_vides();
// Étape 2 : La modification écrit dans la base et accepte uniquement le formulaire POST.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Cette action attend l’envoi du formulaire de modification.');
}
// Étape 3 : On charge la session, la sécurité, les validations et les modèles nécessaires.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'model/annonce.php';
require_once 'model/photo.php';
require_once 'model/categorie.php';
require_once 'model/utilisateur.php';
// Étape 4 : Modifier une annonce est une action privée, donc un compte existant est obligatoire.
$compte_connecte = exiger_compte_connecte();
$est_connecte = true;
$pseudo_connecte = $compte_connecte->get('pseudo');
$jeton_csrf = obtenir_jeton_csrf();
// Étape 5 : L'identifiant caché doit être un nombre entier positif avant de charger l'annonce.
$annonce_id = lire_entier_positif($_POST, 'annonce_id');
if ($annonce_id < 1) {
    header('Location: afficher_detail_annonce.php?id=0');
    exit;
}
$modele_annonce = new annonce();
$situation_initiale = $modele_annonce->consulter_situation_action($annonce_id);
$refus_initial = determiner_refus_action_annonce($situation_initiale, idConnected());
// Étape 6 : On vérifie une première fois que l'annonce appartient au compte, est ouverte et sans enchère.
if ($refus_initial !== '') {
    if ($refus_initial !== 'introuvable') {
        ajouter_message_flash('detail_annonce_erreur', 'Cette annonce ne peut plus être modifiée.');
    }
    header('Location: afficher_detail_annonce.php?id=' . $annonce_id);
    exit;
}
// Étape 7 : La boucle lit les champs non sensibles pour pouvoir les remettre dans le formulaire en erreur.
foreach ($valeurs_annonce as $nom_champ => $valeur_initiale) {
    $valeurs_annonce[$nom_champ] = lire_champ_annonce($nom_champ);
}
$photo_principale_choisie = lire_champ_annonce('photo_principale');
// Étape 8 : On charge les photographies actuelles pour vérifier les demandes de suppression.
$modele_photo = new photo();
$photographies_objets = $modele_photo->lister_par_annonce($annonce_id);
foreach ($photographies_objets as $photographie) {
    $photographies_existantes[] = [
        'id' => (int) $photographie->id(), 'url' => preparer_url_photo_annonce($photographie->get('nom')),
        'position' => (int) $photographie->get('position'), 'alt' => 'Photographie de ' . $situation_initiale['titre']
    ];
}
$photos_recues = null;
if (isset($_POST['supprimer_photos'])) {
    $photos_recues = $_POST['supprimer_photos'];
}
$photos_a_supprimer = preparer_photos_a_supprimer($photos_recues, $photographies_objets);
if ($photos_a_supprimer === null) {
    $erreurs_annonce['photographies'] = 'La sélection des photographies à supprimer est invalide.';
    $photos_a_supprimer = [];
} else {
    $identifiants_photos_supprimees = $photos_a_supprimer;
}
// Étape 9 : L'API fournit les catégories et confirme que le choix reçu existe toujours.
$modele_categorie = new categorie();
$categories_recues = $modele_categorie->lister();
if ($categories_recues === null) {
    $erreur_generale = 'Les catégories ne peuvent pas être vérifiées pour le moment.';
} else {
    $categories = $categories_recues;
    $publication_disponible = true;
}
$jeton_recu = lire_champ_annonce('jeton_csrf');
// Étape 10 : Le jeton est vérifié avant les règles métier et la préparation des photographies.
if (!verifier_jeton_csrf($jeton_recu)) {
    $erreur_generale = 'Le formulaire a expiré ou la requête n’est pas autorisée. Veuillez réessayer.';
} elseif ($publication_disponible === true) {
    $validation = valider_donnees_annonce($valeurs_annonce, $etats_annonce, $modele_categorie, $categories);
    $erreurs_communes = $validation['erreurs'];
    $erreurs_communes['photographies'] = $erreurs_annonce['photographies'];
    $erreurs_annonce = $erreurs_communes;
    $categorie_selectionnee = $validation['categorie'];
    $prix_prepare = $validation['prix'];
    $fin_preparee = $validation['fin'];
    $nombre_photos_conservees = count($photographies_objets) - count($photos_a_supprimer);
    $places_disponibles = 3 - $nombre_photos_conservees;
    $photographies_preparees = preparer_photographies_annonce($places_disponibles);
    if ($erreurs_annonce['photographies'] === '') {
        $erreurs_annonce['photographies'] = $photographies_preparees['erreur'];
    }
    // Le choix principal est accepté uniquement parmi les photos conservées et les nouveaux fichiers valides.
    $identifiants_photos_conservees = [];
    foreach ($photographies_objets as $photographie) {
        if (!in_array((int) $photographie->id(), $photos_a_supprimer, true)) {
            $identifiants_photos_conservees[] = (int) $photographie->id();
        }
    }
    if ($erreurs_annonce['photographies'] === '') {
        $choix_principale = preparer_choix_photo_principale(
            $photo_principale_choisie, $identifiants_photos_conservees,
            count($photographies_preparees['photographies'])
        );
        if ($choix_principale === null) {
            $erreurs_annonce['photographies'] = 'Le choix de la photographie principale est invalide.';
            $photo_principale_choisie = '';
        }
    }
}
// Étape 11 : La boucle vérifie qu'aucun message d'erreur n'a été préparé.
$formulaire_valide = $erreur_generale === '';
foreach ($erreurs_annonce as $erreur_annonce) {
    if ($erreur_annonce !== '') {
        $formulaire_valide = false;
    }
}
// Étape 12 : Si tout est valide, une transaction réunit toutes les modifications.
if ($formulaire_valide === true) {
    $transaction_commencee = $modele_annonce->commencer_transaction();
    if (!$transaction_commencee) {
        $erreur_generale = 'L’annonce ne peut pas être modifiée pour le moment.';
    } else {
        // Étape 13 : On bloque brièvement l'annonce et on revérifie les droits juste avant l'écriture.
        $situation_verrouillee = $modele_annonce->verrouiller_situation_action($annonce_id);
        $refus_final = determiner_refus_action_annonce($situation_verrouillee, idConnected());
        if ($refus_final !== '') {
            $modele_annonce->annuler_transaction();
            ajouter_message_flash('detail_annonce_erreur', 'La situation de l’annonce a changé. La modification a été refusée.');
            header('Location: afficher_detail_annonce.php?id=' . $annonce_id);
            exit;
        }
        // Étape 14 : Les photographies sont relues pour refuser un ancien formulaire devenu incorrect.
        $photographies_verrouillees = $modele_photo->lister_par_annonce($annonce_id);
        $photos_finales_a_supprimer = preparer_photos_a_supprimer(
            $photos_a_supprimer, $photographies_verrouillees
        );
        if ($photos_finales_a_supprimer === null) {
            $erreur_generale = 'La situation des photographies a changé. Veuillez recommencer.';
        }
        $nombre_final_conserve = count($photographies_verrouillees) - count($photos_a_supprimer);
        if ($nombre_final_conserve + count($photographies_preparees['photographies']) > 3) {
            $erreur_generale = 'Le nombre de photographies a changé. Veuillez recommencer.';
        }
        $dossier_photos = 'public/assets/images/photo-objet';
        if (!empty($photographies_preparees['photographies']) && (!is_dir($dossier_photos) || !is_writable($dossier_photos))) {
            $erreur_generale = 'Les photographies ne peuvent pas être enregistrées pour le moment.';
        }
        $chemins_nouveaux = [];
        $chemins_anciens = [];
        $identifiants_nouvelles_photos = [];
        // Étape 15 : Les lignes PHOTO choisies sont supprimées, mais les fichiers attendent la validation finale.
        if ($erreur_generale === '') {
            foreach ($photographies_verrouillees as $photographie) {
                if (!in_array((int) $photographie->id(), $photos_a_supprimer, true)) {
                    continue;
                }
                $chemin_ancien = preparer_chemin_photo_annonce($photographie->get('nom'));
                if ($chemin_ancien !== '') {
                    $chemins_anciens[] = $chemin_ancien;
                }
                if (!$photographie->delete()) {
                    $erreur_generale = 'Les photographies ne peuvent pas être modifiées pour le moment.';
                    break;
                }
            }
        }
        if ($erreur_generale === '' && !$modele_photo->reordonner_par_annonce($annonce_id)) {
            $erreur_generale = 'Les photographies ne peuvent pas être réorganisées pour le moment.';
        }
        // Étape 16 : Les nouveaux fichiers sont ajoutés après les photographies conservées.
        if ($erreur_generale === '') {
            $position = count($modele_photo->lister_par_annonce($annonce_id)) + 1;
            foreach ($photographies_preparees['photographies'] as $photographie_preparee) {
                $nom_photo = 'annonce-' . $annonce_id . '-' . bin2hex(random_bytes(8));
                $nom_photo .= '.' . $photographie_preparee['extension'];
                $chemin_photo = $dossier_photos . '/' . $nom_photo;
                if (!move_uploaded_file($photographie_preparee['chemin_temporaire'], $chemin_photo)) {
                    $erreur_generale = 'Une photographie ne peut pas être enregistrée pour le moment.';
                    break;
                }
                $chemins_nouveaux[] = $chemin_photo;
                $nouvelle_photo = new photo();
                if (!$nouvelle_photo->enregistrer_photo($nom_photo, $position, $annonce_id)) {
                    $erreur_generale = 'Une photographie ne peut pas être enregistrée pour le moment.';
                    break;
                }
                $identifiants_nouvelles_photos[] = (int) $nouvelle_photo->id();
                $position++;
            }
        }
        // Étape 17 : Le choix de la photographie principale est transformé en un identifiant encore valide.
        if ($erreur_generale === '') {
            $photo_principale_id = 0;
            if ($choix_principale['type'] === 'existante') {
                $photo_principale_id = $choix_principale['valeur'];
            } elseif ($choix_principale['type'] === 'nouvelle') {
                $photo_principale_id = $identifiants_nouvelles_photos[$choix_principale['valeur']];
            } else {
                $photographies_finales = $modele_photo->lister_par_annonce($annonce_id);
                if (!empty($photographies_finales)) {
                    $premiere_photographie = array_shift($photographies_finales);
                    $photo_principale_id = (int) $premiere_photographie->id();
                }
            }
            if ($photo_principale_id > 0 && !$modele_photo->definir_principale($annonce_id, $photo_principale_id)) {
                $erreur_generale = 'La photographie principale ne peut pas être enregistrée pour le moment.';
            }
        }
        // Étape 18 : L'objet complet est chargé puis seuls les champs autorisés sont modifiés.
        if ($erreur_generale === '') {
            $annonce_a_modifier = new annonce($annonce_id);
            $modification_reussie = $annonce_a_modifier->modifier_annonce([
                'titre' => $valeurs_annonce['titre'], 'description' => $valeurs_annonce['description'],
                'etat' => $valeurs_annonce['etat'], 'prix_depart' => $prix_prepare,
                'date_heure_fin' => $fin_preparee,
                'categorie_id' => (int) $categorie_selectionnee['id']
            ]);
            if (!$modification_reussie) {
                $erreur_generale = 'L’annonce ne peut pas être modifiée pour le moment.';
            }
        }
        if ($erreur_generale !== '') {
            // Une erreur annule la transaction et retire les nouveaux fichiers de cette tentative.
            $modele_annonce->annuler_transaction();
            supprimer_fichiers_annonce($chemins_nouveaux);
        } elseif (!$modele_annonce->valider_transaction()) {
            $modele_annonce->annuler_transaction();
            supprimer_fichiers_annonce($chemins_nouveaux);
            $erreur_generale = 'L’annonce ne peut pas être modifiée pour le moment.';
        } else {
            // Étape 19 : Après la validation en base, les anciens fichiers devenus inutiles sont retirés.
            supprimer_fichiers_annonce($chemins_anciens);
            ajouter_message_flash('detail_annonce_succes', 'Les modifications de l’annonce ont été enregistrées.');
            header('Location: afficher_detail_annonce.php?id=' . $annonce_id);
            exit;
        }
    }
}
// Étape 20 : En cas d'erreur, le template réaffiche les valeurs autorisées et les messages préparés.
require_once 'templates/pages/annonce.php';
