<?php // TEMPLATE : connexion.php

// Rôle : Afficher le formulaire public de connexion.
// Paramètres : 
//      Le contrôleur fournit l'identifiant, les messages, les erreurs et le jeton CSRF
/**
        * @var string $identifiant : Identifiant retenu en mémoire
        * @var bool $formulaire_disponible : Droit d'afficher les champs
        * 
        * @var string $message_information : Note d'information ou rappel
        * 
        * @var array $erreurs : Erreurs de saisie par champ
        * @var string $erreur_generale : Message d'échec de connexion
        * @var string $jeton_csrf : Jeton de sécurité anti-fraude
*/
// Retour : Une page HTML complète destinée au navigateur


// --- ÉTAPE 1 : LE TITRE DE LA PAGE ---
// Le titre apparaît dans l'onglet du navigateur
$titre_page = 'Connexion — QuiDitMieux';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php // --- ÉTAPE 2 : LES STYLES ---
    // Le fragment contient les réglages communs et la feuille de styles ?>
    <?php require_once 'templates/fragments/head.php'; ?>
</head>
<body>
    <main id="contenu-principal" class="page-authentification">
        
        <?php //--- ÉTAPE 3 : BIENVENUE (PANNEAU GAUCHE) ---
        // Cette partie présente les avantages disponibles après la connexion
         ?>
        <section class="presentation-authentification" aria-labelledby="titre-presentation">
            <a class="presentation-authentification__monogramme" href="afficher_accueil.php"
                aria-label="QuiDitMieux, revenir à l'accueil">Q</a>
            <h1 id="titre-presentation" class="presentation-authentification__titre">Content de vous revoir !</h1>
            <p>Retrouvez vos annonces, vos suivis et votre position dans chaque vente.</p>
        </section>
        
        <?php // Les messages restent généraux pour ne pas révéler si un compte existe ?>
        <section class="zone-authentification" aria-labelledby="titre-formulaire">
            <div class="formulaire-authentification formulaire-authentification--connexion">
                <h2 id="titre-formulaire">Se connecter</h2>
                <p class="formulaire-authentification__introduction">Utilisez votre pseudo ou votre adresse de courriel.</p>
                
                <?php // --- ÉTAPE 4 : LE MESSAGE D'INFORMATION ---
                // Si la boîte "$message_information" n'est pas vide (par exemple pour dire "Vous devez vous connecter"),
                // On fait apparaître un message à l'écran pour guider le visiteur ?>
                <?php if ($message_information !== '') { ?>
                    <div class="message formulaire-authentification__message" role="status">
                        <p><?= echapper_html($message_information) ?></p>
                    </div>
                <?php } ?>
                
                <?php // --- ÉTAPE 5 : L'ERREUR GÉNÉRALE ---
                // Si On s'aperçoit que la connexion a échoué (par exemple à cause d'un mot de passe erroné),
                // la boîte "$erreur_generale" se remplit et on signale le refus ?>
                <?php if ($erreur_generale !== '') { ?>
                    <div class="message message--erreur formulaire-authentification__message" role="alert">
                        <p class="message__titre">Connexion impossible</p>
                        <p><?= echapper_html($erreur_generale) ?></p>
                    </div>
                <?php } ?>
                
                <?php // --- ÉTAPE 6 : L'AUTORISATION D'OUVRIR LE FORMULAIRE ---
                // Le formulaire est affiché seulement lorsque le contrôleur l'autorise
                // C'est un test avec un interrupteur VRAI ou FAUX. Si On dit "true" (VRAI), alors il a le droit de fabriquer et d'afficher le formulaire de connexion ?>
                <?php if ($formulaire_disponible === true) { ?>
                    <form class="formulaire" action="enregistrer_connexion.php" method="post">
                        
                        <?php // --- ÉTAPE 7 : LE CODE DE SÉCURITÉ CACHÉ ---
                        // Une case totalement invisible pour les utilisateurs, mais essentielle pour l'application.
                        // Elle contient un jeton secret pour prouver que personne n'essaie de tricher depuis un autre site. ?>
                        <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>">
                        
                        <?php // --- ÉTAPE 8 : LE PSEUDO OU EMAIL ---
                        // L'identifiant accepte le pseudo ou le courriel conservé après une erreur.
                        // Si On trouve une erreur dans "$erreurs['identifiant']", le champ est signalé en rouge grâce à la classe "est-invalide". ?>
                        <div class="champ<?php if ($erreurs['identifiant'] !== '') { ?> est-invalide<?php } ?>">
                            <label class="champ__libelle" for="identifiant">Pseudo ou adresse de courriel</label>
                            <input class="champ__saisie" id="identifiant" name="identifiant" type="text" maxlength="255" autocomplete="username" value="<?= echapper_html($identifiant) ?>" placeholder="Saisir pseudo ou adresse de courriel" aria-describedby="erreur-identifiant" <?php if ($erreurs['identifiant'] !== '') { ?>aria-invalid="true" <?php } ?>required>
                            <p class="champ__erreur" id="erreur-identifiant"><?= echapper_html($erreurs['identifiant']) ?></p>
                        </div>
                        
                        <?php // --- ÉTAPE 9 : LE MOT DE PASSE SECRET ---
                        // Le type "password" transforme tout ce qu'on écrit en petits ronds
                        // Le mot de passe reste masqué et n'est jamais remis après une erreur ?>
                        <div class="champ<?php if ($erreurs['mot_de_passe'] !== '') { ?> est-invalide<?php } ?>">
                            <label class="champ__libelle" for="mot-de-passe">Mot de passe</label>
                            <input class="champ__saisie" id="mot-de-passe" name="mot_de_passe" type="password" autocomplete="current-password" placeholder="Saisir mot de passe" aria-describedby="erreur-mot-de-passe" <?php if ($erreurs['mot_de_passe'] !== '') { ?>aria-invalid="true" <?php } ?>required>
                            <p class="champ__erreur" id="erreur-mot-de-passe"><?= echapper_html($erreurs['mot_de_passe']) ?></p>
                        </div>
                        
                        <?php //--- ÉTAPE 10 : LE BOUTON D'ENVOI ---
                        // Le bouton envoie les deux champs au contrôleur de connexion
                        // Le type "submit" transforme ce bouton en interrupteur final. Quand on clique dessus, on rassemble les deux cases et envoie le tout au contrôleur de connexion "enregistrer_connexion.php". ?>
                        <button class="bouton formulaire-authentification__bouton" type="submit">Se connecter</button>
                    </form>
                <?php } ?>
                
                <!-- --- ÉTAPE 11 ---
                // Si le visiteur n'a pas encore de compte, on lui propose d'aller vers la page d'inscription -->
                <p class="formulaire-authentification__navigation">Pas encore de compte ? <a href="afficher_inscription.php">S’inscrire</a></p>
            </div>
        </section>
    </main>
</body>
</html>
