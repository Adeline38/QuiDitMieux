<?php
//// TYPE : name.php
// Rôle : Transformer les résultats SQL du tableau de bord en données simples pour le template et les réponses JSON.
// Paramètres : Les fonctions reçoivent les lignes agrégées préparées par le modèle annonce.
// Retour : Des cartes prêtes à afficher, séparées selon le rôle de l'utilisateur.

// Rôle : Présenter une date MySQL dans le format français utilisé par la maquette.
// Paramètres : $date_heure contient une date et une heure venant de la base de données.
// Retour : La date lisible ou la valeur reçue si son format est inattendu.
function formater_date_tableau_de_bord($date_heure)
{
    $date_affichee = (string) $date_heure;
    $date_preparee = DateTime::createFromFormat('Y-m-d H:i:s', $date_affichee);

    if ($date_preparee !== false) {
        $date_affichee = $date_preparee->format('d/m/Y à H:i');
    }

    return $date_affichee;
}

// Rôle : Préparer les cartes des annonces appartenant au vendeur connecté.
// Paramètres : $lignes contient les annonces et leurs calculs d'enchères.
// Retour : Un tableau de cartes ne contenant aucune donnée personnelle d'un enchérisseur.
function preparer_cartes_vendeur($lignes)
{
    $cartes = [];

    if (!is_array($lignes)) {
        return $cartes;
    }

    foreach ($lignes as $ligne) {
        $vente_ouverte = (int) $ligne['vente_ouverte'] === 1;
        $nombre_encheres = (int) $ligne['nombre_encheres'];
        $statut = 'En cours';
        $couleur = 'neutre';

        if ($vente_ouverte === false && $nombre_encheres > 0) {
            $statut = 'Vendu';
            $couleur = 'positive';
        } elseif ($vente_ouverte === false) {
            $statut = 'Non vendu';
            $couleur = 'negative';
        }

        $cartes[] = [
            'id' => (int) $ligne['id'],
            'titre' => (string) $ligne['titre'],
            'prix_courant' => number_format((float) $ligne['prix_courant'], 2, ',', ' '),
            'nombre_encheres' => $nombre_encheres,
            'fin_vente' => formater_date_tableau_de_bord($ligne['date_heure_fin']),
            'statut' => $statut,
            'couleur' => $couleur,
            'photo_url' => preparer_url_photo_annonce($ligne['nom_photo']),
            'peut_modifier' => $vente_ouverte === true && $nombre_encheres === 0
        ];
    }

    return $cartes;
}

// Rôle : Séparer les annonces suivies ou enchéries des ventes remportées par l'utilisateur.
// Paramètres : $lignes contient les relations et montants calculés par le modèle annonce.
// Retour : Un tableau possédant les clés suivies et remportees.
function preparer_cartes_acheteur($lignes)
{
    $resultat = [
        'suivies' => [],
        'remportees' => []
    ];

    if (!is_array($lignes)) {
        return $resultat;
    }

    foreach ($lignes as $ligne) {
        $vente_ouverte = (int) $ligne['vente_ouverte'] === 1;
        $nombre_encheres_utilisateur = (int) $ligne['nombre_encheres_utilisateur'];
        $a_participe = $nombre_encheres_utilisateur > 0;
        $suivi_volontaire = (int) $ligne['suivi_volontaire'] === 1;
        $est_plus_offrant = false;

        if ($a_participe === true) {
            $prix_courant = number_format((float) $ligne['prix_courant'], 2, '.', '');
            $meilleur_montant = number_format((float) $ligne['meilleur_montant_utilisateur'], 2, '.', '');
            $est_plus_offrant = $prix_courant === $meilleur_montant;
        }

        $carte = [
            'id' => (int) $ligne['id'],
            'titre' => (string) $ligne['titre'],
            'prix_courant' => number_format((float) $ligne['prix_courant'], 2, ',', ' '),
            'nombre_encheres' => (int) $ligne['nombre_encheres'],
            'fin_vente' => formater_date_tableau_de_bord($ligne['date_heure_fin']),
            'photo_url' => preparer_url_photo_annonce($ligne['nom_photo']),
            'suivi_volontaire' => $suivi_volontaire
        ];

        // Une vente terminée remportée quitte la section des suivis et apparaît uniquement dans la troisième section.
        if ($vente_ouverte === false && $est_plus_offrant === true) {
            $carte['statut'] = 'Remportée';
            $carte['couleur'] = 'positive';
            $resultat['remportees'][] = $carte;
        } else {
            $carte['couleur'] = 'neutre';

            if ($a_participe === false && $vente_ouverte === true) {
                $carte['statut'] = 'Suivie';
            } elseif ($a_participe === false) {
                $carte['statut'] = 'Suivie · vente terminée';
            } elseif ($vente_ouverte === true && $est_plus_offrant === true) {
                $carte['statut'] = 'Plus-offrant';
                $carte['couleur'] = 'positive';
            } elseif ($vente_ouverte === true) {
                $carte['statut'] = 'Dépassé';
                $carte['couleur'] = 'negative';
            } else {
                $carte['statut'] = 'Perdu';
                $carte['couleur'] = 'negative';
            }

            $resultat['suivies'][] = $carte;
        }
    }

    return $resultat;
}
