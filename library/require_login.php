<?php
// Rôle : Empêcher un visiteur non connecté de poursuivre vers une page privée.
// Paramètres : Aucun paramètre n'est nécessaire car l'état de connexion vient de la session.
// Retour : Aucun retour ; la fonction laisse continuer le contrôleur ou termine après la redirection.
//// TYPE : name.php
// Rôle : Fournir le contrôle commun qui réserve un contrôleur aux utilisateurs connectés.
// Paramètres : La fonction consulte l'état de la session préparée par session.php.
// Retour : Le traitement continue si l'utilisateur est connecté ou redirige le visiteur vers la connexion.

function requireLogin()
{
    // Le message temporaire expliquera pourquoi le visiteur arrive sur le formulaire de connexion.
    if (!isConnected()) {
        ajouter_message_flash('connexion', 'Vous devez être connecté pour accéder à cette page.');
        header('Location: afficher_connexion.php');

        // Le contrôleur privé ne doit jamais reprendre après la redirection.
        exit;
    }
}

// Rôle : Vérifier la connexion et charger le compte qui existe encore dans la base.
// Paramètres : Aucun paramètre car l'identifiant vient de la session.
// Retour : L'objet utilisateur connecté ou une redirection vers la connexion.
function exiger_compte_connecte()
{
    // La première vérification refuse les visiteurs qui ne sont pas connectés.
    requireLogin();
    $compte_connecte = userConnected();

    // Une ancienne session ne doit pas donner accès à un compte supprimé.
    if ($compte_connecte === null) {
        disconnect();
        initSession();
        ajouter_message_flash('connexion', 'Votre compte n’est plus disponible. Veuillez vous reconnecter.');
        header('Location: afficher_connexion.php');
        exit;
    }

    return $compte_connecte;
}
