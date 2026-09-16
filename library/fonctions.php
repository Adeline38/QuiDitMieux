<?php
// Rôle : Protéger une valeur avant son insertion dans du code HTML.
// Paramètres : $valeur contient le texte à afficher dans le template.
// Retour : Le texte dont les caractères HTML et les guillemets sont neutralisés.
//// TYPE : name.php
// Rôle : Fournir les petites fonctions communes utilisées dans plusieurs parties du projet.
// Paramètres : Les fonctions reçoivent les valeurs préparées par les contrôleurs.
// Retour : Les fonctions retournent des valeurs prêtes pour leur utilisation prévue.

function echapper_html($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

// Rôle : Vérifier le nom d'une photographie et préparer son adresse publique.
// Paramètres : $nom contient uniquement le nom enregistré dans la table PHOTO.
// Retour : L'adresse relative de la photographie existante, ou une chaîne vide si elle est absente.
function preparer_url_photo_annonce($nom)
{
    if (!is_string($nom) || $nom === '') {
        return '';
    }

    // basename() interdit au nom enregistré de sortir du dossier réservé aux photographies.
    $nom_fichier = basename($nom);
    $chemin = dirname(__DIR__) . '/public/assets/images/photo-objet/' . $nom_fichier;

    if ($nom_fichier === '' || !is_file($chemin)) {
        return '';
    }

    return 'public/assets/images/photo-objet/' . rawurlencode($nom_fichier);
}

// Rôle : Préparer le chemin local d'une photographie avant une suppression du système de fichiers.
// Paramètres : $nom contient uniquement le nom enregistré dans la table PHOTO.
// Retour : Le chemin absolu du fichier autorisé, ou une chaîne vide si le nom ou le fichier est invalide.
function preparer_chemin_photo_annonce($nom)
{
    if (!is_string($nom) || $nom === '') {
        return '';
    }

    // basename() retire tout dossier éventuellement présent afin de rester dans le répertoire réservé aux annonces.
    $nom_fichier = basename($nom);

    if ($nom_fichier !== $nom || $nom_fichier === '') {
        return '';
    }

    $chemin = dirname(__DIR__) . '/public/assets/images/photo-objet/' . $nom_fichier;

    if (!is_file($chemin)) {
        return '';
    }

    return $chemin;
}

// Rôle : Lire un identifiant entier et positif reçu par un contrôleur.
// Paramètres : $donnees contient GET ou POST et $nom contient le nom du champ.
// Retour : L'identifiant valide ou zéro si la valeur est absente ou incorrecte.
function lire_entier_positif($donnees, $nom)
{
    if (!isset($donnees[$nom]) || !is_string($donnees[$nom])) {
        return 0;
    }

    $identifiant = filter_var(
        $donnees[$nom],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($identifiant === false) {
        return 0;
    }

    return (int) $identifiant;
}

// Rôle : Supprimer les fichiers d'une annonce après une annulation ou une validation en base.
// Paramètres : $chemins contient uniquement les chemins préparés et contrôlés par l'application.
// Retour : Aucun retour car chaque fichier existant est simplement retiré.
function supprimer_fichiers_annonce($chemins)
{
    foreach ($chemins as $chemin) {
        if (is_file($chemin)) {
            unlink($chemin);
        }
    }
}
