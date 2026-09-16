<?php // CONTRÔLEUR : afficher_accueil.php

// Rôle : Préparer les annonces visibles sur la page d'accueil.
// Paramètres : 
//      - Néant
//      - La session est facultative et sert à adapter la navigation et à lire un éventuel message temporaire
// Retour : Le template accueil.php reçoit les données prêtes à afficher



// Étape 1 : LA PRÉPARATION DES VARIABLES ---
// On prépare des tiroirs vides, ou avec des valeurs de base
// Ils sont des boîtes d'informations pour ranger la liste des annonces, les messages d'erreurs, le prénom de l'invité, et tout le matériel nécessaire pour faire des recherches plus tard (mots-clés, prix mini, prix maxi...)
$annonces_accueil = [];
$erreur_accueil = '';
$message_accueil = '';
$est_connecte = false; // Cette boîte dit "FAUX" car l'utilisateur n'a pas encore écrit son mot de passe
$pseudo_connecte = ''; // Cette boîte est vide, elle contiendra le prénom de l'utilisateur plus tard
$jeton_csrf = '';
$mode_recherche = false;
$recherche_valide = true;
$nombre_resultats = 0; // Cette boîte va contenir le numéro secret de l'annonce qu'on cherche
$page_courante = 1;
$nombre_pages = 1;
$pages_pagination = [];
$url_page_precedente = '';
$url_page_suivante = '';
$categories_recherche = [];
$erreur_categories = '';
$valeurs_recherche = [
    'mots_cles' => '', 'categorie_id' => '', 'etat' => '',
    'prix_minimum' => '', 'prix_maximum' => '', 'statut' => 'en_cours'
];
$erreurs_recherche = [
    'mots_cles' => '', 'categorie_id' => '', 'etat' => '',
    'prix_minimum' => '', 'prix_maximum' => '', 'statut' => ''
];

// Étape 2 : LA SÉCURITÉ L'ENTRÉE (LA MÉTHODE HTTP) ---
// La fonction d'affichage reste disponible si la méthode HTTP est refusée
// On va chercher le premier cahier d'outils
// on vérifie la façon dont le visiteur arrive
// Si le visiteur essaie d'entrer en poussant une porte interdite (autre chose que la méthode "GET" qui sert à regarder), on note une erreur dans la boîte, on dessine la page d'accueil vide et on arrête immédiatement le programme
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') { // Si l'ordinateur ne sait pas comment le visiteur a cliqué ou si la porte d'entrée utilisée n'est pas "GET" (qui sert uniquement à regarder)
    $erreur_accueil = 'Cette page est accessible uniquement en consultation.'; // Alors on remplit notre champ avec un texte rouge pour expliquer qu'on a le droit de lire mais pas de tricher
    require_once 'templates/pages/accueil.php'; // On appelle tout de suite le template pour afficher l'écran d'accueil avec notre message d'erreur bien visible
    exit; // On force la sortie pour ne pas lire la suite du code
}


// Étape 3 : UTILISER LES OUTILS DU SITE ---
// Charger tous les fichiers importants rangés dans les dossiers
// Connecter les outils qui savent gérer la sécurité, lire les annonces, trouver les photos, compter l'argent des enchères, reconnaître les membres et lister les catégories
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'model/annonce.php';
require_once 'model/photo.php';
require_once 'model/enchere.php';
require_once 'model/utilisateur.php';
require_once 'model/categorie.php';

// Étape 4 : REGARDER SI LE VISITEUR EST UN MEMBRE DU SITE ---
// L'accueil est public, mais le menu change lorsqu'un compte est connecté.
// On va donc vérifier si le visiteur est connecté. Si la réponse est VRAI, on va chercher sa fiche. Si sa fiche a disparu, on le déconnecte par sécurité
// Sinon, on active l'interrupteur sur VRAI, on recopie son pseudo et on lui donne un badge secret de sécurité (le jeton)
// Enfin, on regarde s'il y a un petit mot de réussite laissé par une action précédente dans notre boîte "flash"
$est_connecte = isConnected(); 
if ($est_connecte === true) {
    $compte_connecte = userConnected();
    if ($compte_connecte === null) {
        disconnect();
        $est_connecte = false;
    } else {
        $pseudo_connecte = $compte_connecte->get('pseudo');
        $jeton_csrf = obtenir_jeton_csrf();
    }
}
$message_accueil = lire_message_flash('accueil_succes');

// Étape 5 : DEMANDER LA LISTE DES CATÉGORIES À L'API ---
// L'API fournit une fois les catégories utilisées par le filtre et les cartes
// On utilise l'API pour récupérer toute la liste
// Si la machine est en panne et renvoie du vide (null), on écrit une alerte rouge
// Sinon, on distribue les catégories dans notre tiroir de recherche et on crée un dictionnaire pour associer le numéro secret de chaque catégorie à son vrai nom écrit en toutes lettres
$modele_categorie = new categorie();
$categories_recues = $modele_categorie->lister();
$libelles_categories = [];
if ($categories_recues === null) {
    $erreur_categories = 'Les catégories sont temporairement indisponibles.';
} else {
    $categories_recherche = $categories_recues;
    foreach ($categories_recues as $categorie_recue) {
        $libelles_categories[(int) $categorie_recue['id']] = $categorie_recue['libelle'];
    }
}

// Étape 6 : LES 6 ANNONCES CHOSISES POUR L'ACCUEIL ---
// On réveille les outils spécialistes des annonces, des photos et des prix
// Le premier outil va chercher les 6 meilleures annonces sélectionnées pour la page de démarrage
// on utilise une boucle "foreach" pour analyser ces annonces une par une, comme un inspecteur
$modele_annonce = new annonce();
$modele_photo = new photo();
$modele_enchere = new enchere();
$annonces_selectionnees = $modele_annonce->lister_selection_accueil();
foreach ($annonces_selectionnees as $annonce_selectionnee) {
    $annonce_id = (int) $annonce_selectionnee->id();
    $resume = $modele_enchere->resumer_annonce($annonce_id);
    if ($resume === null) {
        $annonces_accueil = [];
        $erreur_accueil = 'Les annonces ne peuvent pas être affichées pour le moment.';
        break;
    }

    // Étape 7 : L'HABILLAGE DES ANNONCES (PHOTO, DATE, PRIX ET CATEGORIE) ---
    // Pour l'annonce en cours d'examen, on cherche son image principale et on prépare son adresse internet
    // On transforme la date de fin en une date facile à lire
    // On règle le prix : c'est le prix de départ, sauf si quelqu'un a déjà proposé plus d'argent
    // On utilise le dictionnaire de l'étape 5 pour retrouver le nom écrit de la catégorie
    // Pour finir, on fabrique une fiche bien propre contenant toutes ces infos bien rangées et on l'ajoute à notre collection
    $photo = $modele_photo->trouver_principale($annonce_id);
    $url_photo = '';
    if ($photo !== null) {
        $url_photo = preparer_url_photo_annonce($photo->get('nom'));
    }
    $fin_vente = $annonce_selectionnee->get('date_heure_fin');
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $fin_vente);
    if ($date !== false) {
        $fin_vente = $date->format('d/m/Y à H:i');
    }
    $prix = $annonce_selectionnee->get('prix_depart');
    if ($resume['prix_courant'] !== null) {
        $prix = $resume['prix_courant'];
    }
    $categorie_id = (int) $annonce_selectionnee->get('categorie_id');
    $categorie = 'Catégorie indisponible';
    if (isset($libelles_categories[$categorie_id])) {
        $categorie = $libelles_categories[$categorie_id];
    }
    $annonces_accueil[] = [
        'id' => $annonce_id, 
        'titre' => $annonce_selectionnee->get('titre'),
        'categorie' => $categorie, 
        'prix_courant' => number_format($prix, 2, ',', ' '),
        'nombre_encheres' => (int) $resume['nombre_encheres'], 
        'fin_vente' => $fin_vente,
        'url_photo' => $url_photo, 
        'statut' => 'En cours'
    ];
}

// Étape 8 : ENVOYER LES DONNÉES AU TEMPLATE ---
// Toutes nos boîtes d'informations sont maintenant prêtes et remplies
// On appelle le template "accueil.php" pour qu'il affiche ces infos sur l'écran du visiteur
require_once 'templates/pages/accueil.php';
