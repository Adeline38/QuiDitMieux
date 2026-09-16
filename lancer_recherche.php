<?php // CONTRÔLEUR : lancer_recherche.php
// Rôle : Valider les filtres publics, rechercher les annonces et préparer leur pagination.
// Paramètres : 
//      - GET fournit les mots-clés, l'id de la catégorie, l'état, le prix mini et maxi, le statut et le numéro de page
//      - la session est facultative
// Retour : Le template accueil.php reçoit les résultats ou des messages fonctionnels sans détail technique.



// Étape 1 : On prépare toutes les valeurs attendues par le template et les filtres de recherche.
$annonces_accueil = [];
$erreur_accueil = '';
$message_accueil = '';
$erreur_categories = '';
$est_connecte = false;
$pseudo_connecte = '';
$jeton_csrf = '';
$mode_recherche = true;
$nombre_resultats = 0;
$page_courante = 1;
$nombre_pages = 1;
$pages_pagination = [];
$url_page_precedente = '';
$url_page_suivante = '';
$categories_recherche = [];
$valeurs_recherche = [
    'mots_cles' => '',
    'categorie_id' => '',
    'etat' => '',
    'prix_minimum' => '',
    'prix_maximum' => '',
    'statut' => 'toutes'
];
$erreurs_recherche = [
    'mots_cles' => '',
    'categorie_id' => '',
    'etat' => '',
    'prix_minimum' => '',
    'prix_maximum' => '',
    'statut' => ''
];
// Étape 2 : On charge les fonctions d'affichage avant de contrôler la méthode reçue.
require_once 'library/fonctions.php';
$methode_http = '';
// Étape 3 : On lit la méthode utilisée pour envoyer les filtres.
if (isset($_SERVER['REQUEST_METHOD'])) {
    $methode_http = $_SERVER['REQUEST_METHOD'];
}
// Étape 4 : La recherche consulte des données et accepte donc uniquement la méthode GET.
if ($methode_http !== 'GET') {
    $erreur_accueil = 'La recherche est accessible uniquement en consultation.';
    require_once 'templates/pages/accueil.php';
    exit;
}
// Étape 5 : On charge la session, les validations et les modèles nécessaires aux résultats.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'library/validation_recherche.php';
require_once 'model/annonce.php';
require_once 'model/utilisateur.php';
require_once 'model/categorie.php';
// Étape 6 : La recherche reste publique, mais la session permet d'adapter le menu.
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
// Étape 7 : L'API fournit les catégories et permettra de vérifier le choix reçu.
$modele_categorie = new categorie();
$categories_recues = $modele_categorie->lister();
if ($categories_recues === null) {
    $erreur_categories = 'Les catégories sont temporairement indisponibles.';
} else {
    $categories_recherche = $categories_recues;
}
// Étape 8 : La boucle lit chaque filtre comme un texte avant toute utilisation par le modèle.
foreach (array_keys($valeurs_recherche) as $nom_critere) {
    $valeur_recue = lire_critere_recherche($nom_critere);
    if ($valeur_recue === null) {
        $erreurs_recherche[$nom_critere] = 'La valeur reçue est invalide.';
        continue;
    }
    $valeurs_recherche[$nom_critere] = $valeur_recue;
}
// Étape 9 : On vérifie les mots-clés, la catégorie, l'état, le statut et les deux prix.
// Une adresse sans statut explicite correspond à la recherche de toutes les ventes.
if ($erreurs_recherche['statut'] === '' && $valeurs_recherche['statut'] === '') {
    $valeurs_recherche['statut'] = 'toutes';
}
// Les mots-clés restent facultatifs, mais leur longueur est limitée à celle d'un titre d'annonce.
if ($erreurs_recherche['mots_cles'] === '' && mb_strlen($valeurs_recherche['mots_cles'], 'UTF-8') > 255) {
    $erreurs_recherche['mots_cles'] = 'La recherche ne peut pas dépasser 255 caractères.';
}
// Une catégorie non vide doit être un identifiant réellement fourni par l'API externe.
$categorie_selectionnee = null;
if ($erreurs_recherche['categorie_id'] === '' && $valeurs_recherche['categorie_id'] !== '') {
    $categorie_selectionnee = $modele_categorie->trouver_dans_liste(
        $valeurs_recherche['categorie_id'],
        $categories_recherche
    );
    if ($categorie_selectionnee === null) {
        $erreurs_recherche['categorie_id'] = 'Vous devez choisir une catégorie valide.';
    }
}
// Les listes blanches empêchent une valeur inventée de devenir un filtre SQL.
$etats_autorises = [
    'neuf',
    'tres_bon_etat',
    'bon_etat',
    'etat_correct'
];
$statuts_autorises = [
    'toutes',
    'en_cours',
    'terminees'
];
if ($erreurs_recherche['etat'] === '' && $valeurs_recherche['etat'] !== '' && !in_array($valeurs_recherche['etat'], $etats_autorises, true)) {
    $erreurs_recherche['etat'] = 'Vous devez choisir un état valide.';
}
if ($erreurs_recherche['statut'] === '' && !in_array($valeurs_recherche['statut'], $statuts_autorises, true)) {
    $erreurs_recherche['statut'] = 'Vous devez choisir un statut valide.';
}
// Les deux prix sont normalisés séparément afin de conserver les valeurs saisies dans le formulaire.
$prix_minimum_prepare = null;
$prix_maximum_prepare = null;
if ($erreurs_recherche['prix_minimum'] === '') {
    $prix_minimum_prepare = preparer_prix_recherche($valeurs_recherche['prix_minimum']);
    if ($prix_minimum_prepare === null) {
        $erreurs_recherche['prix_minimum'] = 'Le prix minimum doit être compris entre 0 et 9 999,99 €.';
    }
}
if ($erreurs_recherche['prix_maximum'] === '') {
    $prix_maximum_prepare = preparer_prix_recherche($valeurs_recherche['prix_maximum']);
    if ($prix_maximum_prepare === null) {
        $erreurs_recherche['prix_maximum'] = 'Le prix maximum doit être compris entre 0 et 9 999,99 €.';
    }
}
// Étape 10 : Un numéro de page absent ou incorrect revient simplement à la première page.
$page_recue = lire_critere_recherche('page');
$page_validee = filter_var($page_recue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($page_validee !== false) {
    $page_courante = $page_validee;
}
$recherche_valide = true;
// Étape 11 : La boucle cherche si au moins un filtre possède un message d'erreur.
foreach ($erreurs_recherche as $message_erreur) {
    if ($message_erreur !== '') {
        $recherche_valide = false;
        break;
    }
}
// Étape 12 : Si les filtres sont valides, on construit les critères transmis au modèle.
if ($recherche_valide === true) {
    $categorie_id = '';
    if ($categorie_selectionnee !== null) {
        $categorie_id = (int) $categorie_selectionnee['id'];
    }
    $fourchette_inversee = false;
    // Le cahier des charges impose un résultat vide, sans message d'erreur, si le minimum dépasse le maximum.
    if ($prix_minimum_prepare !== '' && $prix_maximum_prepare !== '' && (float) $prix_minimum_prepare > (float) $prix_maximum_prepare) {
        $fourchette_inversee = true;
    }
    $criteres = [
        'mots' => separer_mots_recherche($valeurs_recherche['mots_cles']),
        'categorie_id' => $categorie_id,
        'etat' => $valeurs_recherche['etat'],
        'prix_minimum' => $prix_minimum_prepare,
        'prix_maximum' => $prix_maximum_prepare,
        'statut' => $valeurs_recherche['statut'],
        'fourchette_inversee' => $fourchette_inversee
    ];
    $modele_annonce = new annonce();
    // Étape 13 : On compte d'abord tous les résultats pour calculer le nombre de pages.
    $nombre_resultats = $modele_annonce->compter_recherche($criteres);
    if ($nombre_resultats === null) {
        $nombre_resultats = 0;
        $erreur_accueil = 'La recherche ne peut pas être effectuée pour le moment.';
    } else {
        $nombre_pages = (int) ceil($nombre_resultats / 6);
        if ($nombre_pages < 1) {
            $nombre_pages = 1;
        }
        if ($page_courante > $nombre_pages) {
            $page_courante = $nombre_pages;
        }
        $decalage = ($page_courante - 1) * 6;
        // Étape 14 : Le modèle récupère seulement les six résultats de la page demandée.
        $resultats = $modele_annonce->rechercher($criteres, 6, $decalage);
        if ($resultats === false) {
            $erreur_accueil = 'La recherche ne peut pas être effectuée pour le moment.';
        } else {
            $libelles_categories = [];
            foreach ($categories_recherche as $categorie_recue) {
                $libelles_categories[(int) $categorie_recue['id']] = $categorie_recue['libelle'];
            }
            // Étape 15 : La boucle transforme chaque ligne reçue en une carte publique pour le template.
            foreach ($resultats as $resultat) {
                $fin_vente = $resultat['date_heure_fin'];
                $date_preparee = DateTime::createFromFormat('Y-m-d H:i:s', $resultat['date_heure_fin']);
                if ($date_preparee !== false) {
                    $fin_vente = $date_preparee->format('d/m/Y à H:i');
                }
                $categorie_id_resultat = (int) $resultat['categorie_id'];
                $libelle_categorie = 'Catégorie indisponible';
                if (isset($libelles_categories[$categorie_id_resultat])) {
                    $libelle_categorie = $libelles_categories[$categorie_id_resultat];
                }
                $statut_resultat = 'Terminée';
                if ((int) $resultat['vente_ouverte'] === 1) {
                    $statut_resultat = 'En cours';
                }
                $annonces_accueil[] = [
                    'id' => (int) $resultat['id'],
                    'titre' => $resultat['titre'],
                    'categorie' => $libelle_categorie,
                    'prix_courant' => number_format($resultat['prix_courant'], 2, ',', ' '),
                    'nombre_encheres' => (int) $resultat['nombre_encheres'],
                    'fin_vente' => $fin_vente,
                    'url_photo' => preparer_url_photo_annonce($resultat['nom_photo']),
                    'statut' => $statut_resultat
                ];
            }
        }
    }
}
// Étape 16 : Les liens de pagination conservent les filtres et changent seulement le numéro de page.
if ($recherche_valide === true && $erreur_accueil === '' && $nombre_pages > 1) {
    $parametres_url = $valeurs_recherche;
    for ($numero_page = 1; $numero_page <= $nombre_pages; $numero_page++) {
        $parametres_url['page'] = $numero_page;
        $pages_pagination[] = [
            'numero' => $numero_page,
            'url' => 'lancer_recherche.php?' . http_build_query($parametres_url),
            'active' => $numero_page === $page_courante
        ];
    }
    if ($page_courante > 1) {
        $parametres_url['page'] = $page_courante - 1;
        $url_page_precedente = 'lancer_recherche.php?' . http_build_query($parametres_url);
    }
    if ($page_courante < $nombre_pages) {
        $parametres_url['page'] = $page_courante + 1;
        $url_page_suivante = 'lancer_recherche.php?' . http_build_query($parametres_url);
    }
}
// Étape 17 : Le template affiche les cartes, les erreurs, la liste vide et la pagination préparées.
require_once 'templates/pages/accueil.php';
