<?php
// MODÈLE : categorie.php
// Rôle : Consulter en back-end l'API externe qui fournit les catégories de QuiDitMieux.
// Paramètres : Les méthodes construisent la requête HTTP nécessaire à partir de l'adresse publique de l'API.
// Retour : Le modèle retourne des catégories validées ou null lorsque la ressource est indisponible ou invalide.

class categorie
{
    private $url_api = 'https://api.mywebecom.ovh/play/qdm/categ.php';

    public function lister() {
        // Rôle : Récupérer et valider la liste complète des catégories proposées par l'API.
        // Paramètres : Aucun paramètre n'est nécessaire pour demander la liste complète.
        // Retour : Un tableau contenant les identifiants et libellés triés, ou null en cas d'échec.
        
        // Le modèle signale une indisponibilité si l'extension nécessaire n'est pas active sur le serveur.
        if (!function_exists('curl_init')) {
            return null;
        }

        // cURL prépare une requête HTTPS courte qui attend explicitement une réponse JSON.
        $requete = curl_init();

        if ($requete === false) {
            return null;
        }

        curl_setopt_array(
            $requete,
            [
                CURLOPT_URL => $this->url_api,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_HTTPHEADER => ['Accept: application/json']
            ]
        );

        // La réponse, son code HTTP et son type sont contrôlés avant de lire le JSON.
        $reponse = curl_exec($requete);
        $code_http = curl_getinfo($requete, CURLINFO_RESPONSE_CODE);
        $type_contenu = curl_getinfo($requete, CURLINFO_CONTENT_TYPE);
        curl_close($requete);

        if ($reponse === false || $code_http !== 200) {
            return null;
        }

        if (!is_string($type_contenu) || stripos($type_contenu, 'application/json') !== 0) {
            return null;
        }

        // Le deuxième paramètre de json_decode() demande un tableau associatif simple à parcourir.
        $donnees = json_decode($reponse, true);

        if (!is_array($donnees) || empty($donnees)) {
            return null;
        }

        $categories_validees = [];

        // Chaque entrée doit posséder un identifiant positif et un libellé non vide.
        foreach ($donnees as $identifiant => $libelle) {
            $identifiant_valide = filter_var(
                $identifiant,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($identifiant_valide === false || !is_string($libelle)) {
                return null;
            }

            $libelle = trim($libelle);

            if ($libelle === '') {
                return null;
            }

            $categories_validees[$identifiant_valide] = $libelle;
        }

        // Le tri naturel sans distinction de casse stabilise l'ordre présenté dans la liste déroulante.
        asort($categories_validees, SORT_NATURAL | SORT_FLAG_CASE);

        $categories = [];

        // Le tableau final nomme clairement les deux informations attendues par le contrôleur.
        foreach ($categories_validees as $identifiant => $libelle) {
            $categories[] = [
                'id' => $identifiant,
                'libelle' => $libelle
            ];
        }

        return $categories;
    }

    public function trouver_dans_liste($identifiant, $categories) {
        // Rôle : Rechercher un identifiant dans une liste de catégories validée par l'API.
        // Paramètres : $identifiant est la valeur reçue et $categories est la liste retournée par lister().
        // Retour : La catégorie correspondante, ou null si l'identifiant est inconnu ou mal formé.
        
        $identifiant_valide = filter_var(
            $identifiant,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($identifiant_valide === false || !is_array($categories)) {
            return null;
        }

        foreach ($categories as $categorie) {
            if (!isset($categorie['id'], $categorie['libelle'])) {
                continue;
            }

            if ((int) $categorie['id'] === (int) $identifiant_valide) {
                return $categorie;
            }
        }

        return null;
    }

}
