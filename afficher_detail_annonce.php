<?php // CONTRÔLEUR : afficher_detail_annonce.php
// Rôle : Préparer le détail public d'une annonce et les informations privées autorisées selon le rôle.
// Paramètres : 
//      - GET fournit l’identifiant de l’annonce
//      - la session est facultative et sert à déterminer les actions autorisées
// Retour : Le template detail_annonce.php reçoit le détail, les actions permises, l'historique autorisé ou un état introuvable.



// Étape 1 : On prépare toutes les valeurs nécessaires aux différents états possibles de la page.
$est_connecte = false;
$pseudo_connecte = '';
$jeton_csrf = '';
$annonce_detail = null;
$photographies_detail = [];
$erreur_detail = '';
$message_action = '';
$message_succes = '';
$message_erreur = '';
$est_vendeur = false;
$est_participant = false;
$est_plus_offrant = false;
$peut_encherir = false;
$peut_modifier = false;
$suit_annonce = false;
$peut_suivre = false;
$peut_arreter_suivi = false;
$historique_autorise = false;
$historique_encheres = [];
$gagnant = '';
$montant_minimum = '';
// Étape 2 : Le détail consulte une annonce et accepte donc uniquement la méthode GET.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    exit('Cette page est accessible uniquement en consultation.');
}
// Étape 3 : On charge la session, la sécurité et les modèles nécessaires au détail.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'model/annonce.php';
require_once 'model/photo.php';
require_once 'model/enchere.php';
require_once 'model/categorie.php';
require_once 'model/utilisateur.php';
// Étape 4 : La page reste publique, mais une session valide permet de calculer les droits de l'utilisateur.
if (isConnected()) {
    $compte_connecte = userConnected();
    if ($compte_connecte === null) {
        disconnect();
    } else {
        $est_connecte = true;
        $pseudo_connecte = $compte_connecte->get('pseudo');
        $jeton_csrf = obtenir_jeton_csrf();
    }
}
// Étape 5 : On récupère une seule fois les messages laissés par la dernière action.
$message_succes = lire_message_flash('detail_annonce_succes');
$message_erreur = lire_message_flash('detail_annonce_erreur');
// Étape 6 : On accepte uniquement un identifiant d'annonce entier et positif.
$annonce_id = 0;
$annonce_id = lire_entier_positif($_GET, 'id');
if ($annonce_id < 1) {
    $erreur_detail = 'L’annonce demandée est introuvable.';
} else {
    $annonce = new annonce($annonce_id);
    if (!$annonce->is()) {
        $erreur_detail = 'L’annonce demandée est introuvable.';
    }
}
// Étape 7 : On charge le vendeur et le résumé des enchères nécessaires au prix et aux droits.
if ($erreur_detail === '') {
    $vendeur = new utilisateur($annonce->get('utilisateur_id'));
    $modele_enchere = new enchere();
    $resume_enchere = $modele_enchere->resumer_annonce($annonce_id);
    if (!$vendeur->is() || $resume_enchere === null) {
        $erreur_detail = 'L’annonce ne peut pas être affichée pour le moment.';
    }
}
// Étape 8 : Si les premières lectures ont réussi, on prépare la catégorie, l'état et le prix courant.
if ($erreur_detail === '') {
    // La catégorie reste publique, mais son libellé est résolu depuis la ressource externe.
    $libelle_categorie = 'Catégorie indisponible';
    $modele_categorie = new categorie();
    $categories = $modele_categorie->lister();
    if ($categories !== null) {
        $categorie = $modele_categorie->trouver_dans_liste(
            $annonce->get('categorie_id'),
            $categories
        );
        if ($categorie !== null) {
            $libelle_categorie = $categorie['libelle'];
        }
    }
    // Le libellé visible est associé à la valeur technique enregistrée dans la table ANNONCE.
    $libelles_etats = [
        'neuf' => 'Neuf',
        'tres_bon_etat' => 'Très bon état',
        'bon_etat' => 'Bon état',
        'etat_correct' => 'État correct'
    ];
    $etat_annonce = $annonce->get('etat');
    $libelle_etat = 'État non renseigné';
    if (isset($libelles_etats[$etat_annonce])) {
        $libelle_etat = $libelles_etats[$etat_annonce];
    }
    // Le prix courant reste le prix de départ jusqu'à la première enchère, puis devient le montant maximal.
    $nombre_encheres = (int) $resume_enchere['nombre_encheres'];
    $prix_courant = $annonce->get('prix_depart');
    if ($resume_enchere['prix_courant'] !== null) {
        $prix_courant = $resume_enchere['prix_courant'];
    }
    $meilleure_enchere = $modele_enchere->trouver_meilleure_enchere($annonce_id);
    if ($nombre_encheres > 0 && $meilleure_enchere === null) {
        $erreur_detail = 'L’annonce ne peut pas être affichée pour le moment.';
    }
}
// Étape 9 : On prépare les photographies, les informations publiques et les autorisations privées.
if ($erreur_detail === '') {
    // MySQL détermine si la vente est ouverte afin que l'affichage et l'enregistrement utilisent la même horloge.
    $date_heure_fin = $annonce->get('date_heure_fin');
    $fin_affichee = $date_heure_fin;
    $fin = DateTime::createFromFormat('Y-m-d H:i:s', $date_heure_fin);
    $statut = 'En cours';
    if ($fin !== false) {
        $fin_affichee = $fin->format('d/m/Y à H:i');
    }
    if ((int) $resume_enchere['vente_ouverte'] !== 1) {
        if ($nombre_encheres > 0) {
            $statut = 'Vendu';
        } else {
            $statut = 'Non vendu';
        }
    }
    // Seuls les chemins de photographies validés sont transmis au template public.
    $modele_photo = new photo();
    $photographies = $modele_photo->lister_par_annonce($annonce_id);
    foreach ($photographies as $photographie) {
        $url_photo = preparer_url_photo_annonce($photographie->get('nom'));
        if ($url_photo !== '') {
            $photographies_detail[] = [
                'url' => $url_photo,
                'alt' => 'Photographie de ' . $annonce->get('titre'),
                'position' => (int) $photographie->get('position')
            ];
        }
    }
    // Cette première structure contient uniquement les informations publiques de l'annonce.
    $annonce_detail = [
        'id' => $annonce_id,
        'titre' => $annonce->get('titre'),
        'description' => $annonce->get('description'),
        'categorie' => $libelle_categorie,
        'etat' => $libelle_etat,
        'prix_courant' => number_format($prix_courant, 2, ',', ' '),
        'fin_vente' => $fin_affichee,
        'vendeur' => $vendeur->get('pseudo'),
        'nombre_encheres' => $nombre_encheres,
        'statut' => $statut
    ];
    // Le rôle dépend de la relation entre l'utilisateur connecté, le vendeur et les enchères déjà enregistrées.
    $utilisateur_id = idConnected();
    $est_vendeur = $est_connecte === true && $utilisateur_id === (int) $annonce->get('utilisateur_id');
    if ($est_connecte === true && $est_vendeur === false) {
        $est_participant = $modele_enchere->utilisateur_a_participe(
            $annonce_id,
            $utilisateur_id
        );
        // Le suivi volontaire est distinct d'une participation : il peut être retiré sans supprimer les enchères.
        $suit_annonce = $annonce->est_suivie_par($annonce_id, $utilisateur_id);
        $peut_suivre = $statut === 'En cours' && $suit_annonce === false;
        $peut_arreter_suivi = $suit_annonce === true;
    }
    if ($est_connecte === true && $meilleure_enchere !== null) {
        $est_plus_offrant = $utilisateur_id === (int) $meilleure_enchere['utilisateur_id'];
    }
    // Le vendeur et tous les participants voient l'historique ; les autres utilisateurs ne reçoivent aucune de ses lignes.
    $historique_autorise = $est_vendeur === true || $est_participant === true;
    if ($historique_autorise === true) {
        $historique_recu = $modele_enchere->lister_historique($annonce_id);
        foreach ($historique_recu as $ligne_historique) {
            $date_historique = $ligne_historique['date_heure'];
            $date_preparee = DateTime::createFromFormat('Y-m-d H:i:s', $date_historique);
            if ($date_preparee !== false) {
                $date_historique = $date_preparee->format('d/m/Y à H:i');
            }
            $historique_encheres[] = [
                'pseudo' => $ligne_historique['pseudo'],
                'montant' => number_format($ligne_historique['montant'], 2, ',', ' '),
                'date_heure' => $date_historique
            ];
        }
    }
    // Après la fin, le pseudo du gagnant est transmis uniquement au vendeur et aux participants autorisés.
    if ($statut === 'Vendu' && $historique_autorise === true && $meilleure_enchere !== null) {
        $gagnant = $meilleure_enchere['pseudo'];
    }
    // Avec deux décimales, le premier montant possible est un centime au-dessus du prix courant.
    $minimum_calcule = (float) $prix_courant + 0.01;
    if ($minimum_calcule <= 9999.99) {
        $montant_minimum = number_format($minimum_calcule, 2, '.', '');
    }
    $peut_encherir = $est_connecte === true
        && $statut === 'En cours'
        && $est_vendeur === false
        && $est_plus_offrant === false
        && $montant_minimum !== '';
    // Les commandes vendeur apparaissent seulement pour une vente ouverte qui n'a encore reçu aucune enchère.
    $peut_modifier = $est_vendeur === true
        && $statut === 'En cours'
        && $nombre_encheres === 0;
    // Étape 10 : Un message explique l'action possible ou la raison simple de son refus.
    if ($statut !== 'En cours') {
        $message_action = 'Cette vente est terminée.';
    } elseif ($est_connecte === false) {
        $message_action = 'Connectez-vous pour participer à cette vente.';
    } elseif ($est_vendeur === true) {
        $message_action = 'Vous êtes le vendeur de cette annonce.';
    } elseif ($est_plus_offrant === true) {
        $message_action = 'Vous êtes actuellement le plus-offrant. Vous pourrez enchérir de nouveau si un autre participant vous dépasse.';
    } elseif ($montant_minimum === '') {
        $message_action = 'Aucun montant supérieur ne peut être enregistré pour cette vente.';
    } elseif ($est_participant === true) {
        $message_action = 'Vous avez été dépassé. Vous pouvez proposer une nouvelle enchère.';
    } else {
        $message_action = 'Cette vente est ouverte aux enchères.';
    }
}
// Étape 11 : Le template affiche uniquement les données et les autorisations préparées ci-dessus.
require_once 'templates/pages/detail_annonce.php';
