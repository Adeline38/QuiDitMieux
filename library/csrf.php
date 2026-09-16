<?php
//// TYPE : name.php
// Rôle : Fournir les outils communs qui protègent les formulaires contre les requêtes envoyées depuis un autre site.
// Paramètres : Les fonctions utilisent la session PHP déjà démarrée par init.php.
// Retour : Les fonctions retournent un jeton CSRF ou le résultat de sa vérification.

// Rôle : Obtenir le jeton CSRF de la session et le créer lorsqu'il n'existe pas encore.
// Paramètres : Aucun paramètre n'est nécessaire car le jeton appartient à la session courante.
// Retour : Une chaîne aléatoire utilisable dans un formulaire POST.
function obtenir_jeton_csrf()
{
    // Un jeton aléatoire est créé une seule fois puis conservé pendant la session courante.
    if (empty($_SESSION['jeton_csrf'])) {
        $_SESSION['jeton_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['jeton_csrf'];
}

// Rôle : Vérifier que le jeton reçu correspond au jeton enregistré dans la session.
// Paramètres : $jeton_recu contient la valeur envoyée par un formulaire POST.
// Retour : true si les deux jetons correspondent, sinon false.
function verifier_jeton_csrf($jeton_recu)
{
    // La valeur envoyée par le formulaire doit être un texte non vide avant toute comparaison.
    if (!is_string($jeton_recu) || $jeton_recu === '') {
        return false;
    }

    // La vérification est impossible si aucun jeton n'a auparavant été créé dans la session.
    if (!isset($_SESSION['jeton_csrf'])) {
        return false;
    }

    // Le jeton de session est lui aussi contrôlé pour éviter une comparaison avec une donnée altérée.
    if (!is_string($_SESSION['jeton_csrf']) || $_SESSION['jeton_csrf'] === '') {
        return false;
    }

    // hash_equals() compare les jetons d'une manière adaptée aux valeurs sensibles.
    return hash_equals($_SESSION['jeton_csrf'], $jeton_recu);
}
