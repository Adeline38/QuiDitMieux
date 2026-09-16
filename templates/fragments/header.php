<?php // FRAGMENT : head.php

// Rôle : Afficher la marque et la navigation correspondant à l'état de connexion préparé par le contrôleur.
// Paramètres : 
/**
        * @var mixed $est_connecte : Adapte le menu de navigation
        * @var mixed $pseudo_connecte : Identifiant du compte actif
        * @var mixed $navigation_active : Lien du menu actif (facultative)

        * @var mixed $jeton_csrf : Sécurise la déconnexion
*/


// Retour : L'en-tête commun visible en haut de la page.
?>
<header class="en-tete-principal">
    <?php // La marque sert aussi de lien stable pour revenir à l'accueil public. ?>
    <a class="marque" href="afficher_accueil.php" aria-label="QuiDitMieux, revenir à l'accueil">
        <span class="marque__monogramme" aria-hidden="true">Q</span>
        <span>QuiDitMieux</span>
    </a>

    <nav class="navigation-principale" aria-label="Navigation principale">
        <?php // La session préparée par le contrôleur détermine les actions proposées dans la navigation. ?>
        <?php if ($est_connecte === true) { ?>
            <a class="bouton bouton--publication" href="afficher_annonce.php"<?php if (isset($navigation_active) && $navigation_active === 'publication') { ?> aria-current="page"<?php } ?>>Publier une annonce</a>
            <a class="navigation-principale__lien<?php if (isset($navigation_active) && $navigation_active === 'tableau_de_bord') { ?> est-actif<?php } ?>" href="afficher_tableau_de_bord.php"<?php if (isset($navigation_active) && $navigation_active === 'tableau_de_bord') { ?> aria-current="page"<?php } ?>>Tableau de bord</a>
            <a class="navigation-principale__lien<?php if (isset($navigation_active) && $navigation_active === 'compte') { ?> est-actif<?php } ?>" href="afficher_profil.php"<?php if (isset($navigation_active) && $navigation_active === 'compte') { ?> aria-current="page"<?php } ?>>Mon compte</a>
            <span class="navigation-principale__pseudo"><?= echapper_html($pseudo_connecte) ?></span>
            <?php // La déconnexion modifie la session : elle utilise donc POST et transmet le jeton de protection. ?>
            <form class="navigation-principale__formulaire" action="enregistrer_deconnexion.php" method="post">
                <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>">
                <button class="bouton bouton--contour bouton--deconnexion" type="submit">Déconnexion</button>
            </form>
        <?php } else { ?>
            <?php // Un visiteur anonyme reçoit uniquement les accès à la connexion et à l'inscription. ?>
            <a class="bouton bouton--contour bouton--connexion" href="afficher_connexion.php">Connexion</a>
            <a class="bouton bouton--inscription" href="afficher_inscription.php">Inscription</a>
        <?php } ?>
    </nav>
</header>
