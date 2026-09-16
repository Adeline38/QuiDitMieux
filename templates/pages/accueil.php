<?php // TEMPLATE : accueil.php

// Rôle : Afficher l'accueil, les filtres et les annonces préparées.
// Paramètres :
/**
        * @var mixed $mode_recherche : Type de recherche actif
        * @var mixed $recherche_valide : Validation des filtres
        * @var mixed $nombre_resultats : Total d'annonces trouvées
        * @var mixed $erreur_accueil : Message d'erreur de la page
        * @var mixed $annonces_accueil : Liste des annonces à afficher

        * @var array $valeurs_recherche : Critères de recherche saisis
        * @var array $categories_recherche : Liste des catégories du site

        * @var string $message_accueil : boîte qui contient un texte vert de victoire si une annonce vient d'être supprimée avec succès

        * @var array $erreurs_recherche : retient les erreurs de saisie pour chaque filtre de recherche
        * @var string $erreur_categories : Alerte si bug des catégories
*/
// Retour : Une page HTML complète destinée au navigateur


// --- ÉTAPE 1 : LE TITRE DE LA PAGE ---
// Le titre apparaît dans l'onglet du navigateur
// Si un interrupteur caché appelé "$mode_recherche" est activé (VRAI), alors on efface le mot "Accueil" pour écrire à la place le mot "Recherche"
$titre_page = 'Accueil — QuiDitMieux';
if ($mode_recherche === true) {
    $titre_page = 'Recherche — QuiDitMieux';
}

// On crée des listes bien ordonnées (des tableaux). 
// --- ÉTAPE 2 : ÉTATS ET STATUTS ---
// La première liste répertorie toutes les états possibles de l'objet (neuf, bon état...)
// La deuxième liste permet de trier si la vente est encore active ou déjà finie
// Ces listes permettent d'afficher les choix sans répéter le même code HTML
$etats = [
    '' => 'Tous les états', 'neuf' => 'Neuf', 'tres_bon_etat' => 'Très bon état',
    'bon_etat' => 'Bon état', 'etat_correct' => 'État correct'
];
$statuts = ['toutes' => 'Toutes', 'en_cours' => 'En cours', 'terminees' => 'Terminées'];

// --- ÉTAPE 3 : BOUCLE DES ALERTES CHAMPS ---
// On prépare des casiers vides. 
// On fait le tour de toutes les cases du formulaire grâce à la boucle "foreach". 
// Si une case contient une erreur, on lui accroche une étiquette de style "est-invalide" et active un signal pour prévenir les outils de lecture d'écran qu'il y a un problème ici
$classes_champs = [];
$attributs_invalides = [];
foreach ($erreurs_recherche as $nom => $erreur) {
    $classes_champs[$nom] = 'champ';
    $attributs_invalides[$nom] = '';
    if ($erreur !== '') {
        $classes_champs[$nom] .= ' est-invalide';
        $attributs_invalides[$nom] = ' aria-invalid="true"';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php // --- ÉTAPE 4 : LES STYLES ---
    // Le fragment contient les réglages communs et la feuille de styles ?>
    <?php require_once 'templates/fragments/head.php'; ?>
</head>
<body>
    <div class="cadre-application">
        <?php // --- ÉTAPE 5 : BANDEAU DE NAVIGATION EN HAUT --- 
        // Le menu change selon l'état de connexion préparé par le contrôleur ?>
        <?php require_once 'templates/fragments/header.php'; ?>
        <main id="contenu-principal" class="page-accueil">

            <?php // --- ÉTAPE 6 : LE MESSAGE DE SUCCÈS --- 
            // Si le tiroir "$message_accueil" n'est pas vide (par exemple si on vient de supprimer un objet), on fait surgir une boîte verte de félicitations pour confirmer que tout a bien fonctionné
            // Ce message confirme une suppression effectuée avant le retour à l'accueil ?>
            <?php if ($message_accueil !== '') { ?>
                <div class="message message--succes page-accueil__message" role="status">
                    <p class="message__titre">Suppression réussie</p>
                    <p><?= echapper_html($message_accueil) ?></p>
                </div>
            <?php } ?>
            <div class="grille-recherche">

                <?php 
                // --- ÉTAPE 7 : LE FORMULAIRE DE FILTRES --- 
                // Le formulaire utilise la méthode "get" pour conserver les filtres dans l'adresse de la page ce qui veut dire que chaque choix fait par l'utilisateur sera écrit directement dans la barre d'adresse du navigateur -- ?>
                <aside class="filtres-recherche" aria-labelledby="titre-filtres">
                    <form class="formulaire" action="lancer_recherche.php" method="get">
                        <div class="filtres-recherche__entete">
                            <h1 id="titre-filtres" class="filtres-recherche__titre">Filtres</h1>
                            <a class="filtres-recherche__reinitialisation" href="afficher_accueil.php">Réinitialiser</a>
                        </div>

                        <?php // --- ÉTAPE 8 : LA RECHERCHE PAR MOTS-CLÉS --- 
                        // Les mots-clés recherchent dans le titre ou la description
                        // On applique la classe de l'étape 3 pour l'allumer en rouge s'il y a une erreur, et prépare le texte de sécurité avec sa fonction de nettoyage ?>
                        <div class="<?= $classes_champs['mots_cles'] ?>">
                            <label class="champ__libelle" for="mots-cles">Mots-clés</label>
                            <input class="champ__saisie" id="mots-cles" name="mots_cles" type="search" maxlength="255" value="<?= echapper_html($valeurs_recherche['mots_cles']) ?>" placeholder="Titre ou description" aria-describedby="erreur-mots-cles"<?= $attributs_invalides['mots_cles'] ?>>
                            <p class="champ__erreur" id="erreur-mots-cles"><?= echapper_html($erreurs_recherche['mots_cles']) ?></p>
                        </div>

                        <?php // --- ÉTAPE 9 : LES CATÉGORIES --- 
                        // Les catégories viennent de l'API et conservent le choix reçu
                        // La boucle "foreach" passe sur toutes les catégories reçues du serveur et écrit l'option à l'écran. Si l'une d'elles correspond au choix précédent, elle reste sélectionnée ?>
                        <div class="<?= $classes_champs['categorie_id'] ?>">
                            <label class="champ__libelle" for="categorie">Catégorie</label>
                            <select class="champ__saisie" id="categorie" name="categorie_id" aria-describedby="aide-categorie erreur-categorie"<?= $attributs_invalides['categorie_id'] ?>>
                                <option value="">Toutes les catégories</option>
                                <?php 
                                // La boucle affiche les quatre états autorisés par le projet
                                foreach ($categories_recherche as $categorie) { ?>
                                    <option value="<?= (int) $categorie['id'] ?>"<?php if ($valeurs_recherche['categorie_id'] !== '' && (int) $valeurs_recherche['categorie_id'] === (int) $categorie['id']) { echo ' selected'; } ?>><?= echapper_html($categorie['libelle']) ?></option>
                                <?php } ?>
                            </select>
                            <p class="champ__aide" id="aide-categorie"><?= echapper_html($erreur_categories) ?></p>
                            <p class="champ__erreur" id="erreur-categorie"><?= echapper_html($erreurs_recherche['categorie_id']) ?></p>
                        </div>

                        <?php // --- ÉTAPE 10 : LA SÉLECTION ÉTAT --- 
                        // La boucle affiche les quatre états autorisés par le projet
                        // On parcourt le dictionnaire créé à l'étape 2. Il fabrique les lignes du menu pour trier les objets du site selon qu'ils sont totalement neufs ou simplement corrects ?>
                        <div class="<?= $classes_champs['etat'] ?>">
                            <label class="champ__libelle" for="etat-objet">État de l'objet</label>
                            <select class="champ__saisie" id="etat-objet" name="etat" aria-describedby="erreur-etat"<?= $attributs_invalides['etat'] ?>>
                                <?php foreach ($etats as $valeur_etat => $libelle_etat) { ?>
                                    <option value="<?= echapper_html($valeur_etat) ?>"<?php if ($valeurs_recherche['etat'] === $valeur_etat) { echo ' selected'; } ?>><?= echapper_html($libelle_etat) ?></option>
                                <?php } ?>
                            </select>
                            <p class="champ__erreur" id="erreur-etat"><?= echapper_html($erreurs_recherche['etat']) ?></p>
                        </div>

                        <?php // --- ÉTAPE 11 : LE PRIX MINIMUM ET MAXIMUM --- 
                        // On crée deux cases côte à côte. Le type "number" et le "step" forcent on à n'accepter que de l'argent réel (des nombres avec des centimes) pour créer une tranche de prix de recherche ?>
                        <div class="groupe-champs">
                            <div class="<?= $classes_champs['prix_minimum'] ?>">
                                <label class="champ__libelle" for="prix-minimum">Prix minimum</label>
                                <input class="champ__saisie" id="prix-minimum" name="prix_minimum" type="number" min="0" max="9999.99" step="0.01" value="<?= echapper_html($valeurs_recherche['prix_minimum']) ?>" placeholder="Min. €" aria-describedby="erreur-prix-minimum"<?= $attributs_invalides['prix_minimum'] ?>>
                                <p class="champ__erreur" id="erreur-prix-minimum"><?= echapper_html($erreurs_recherche['prix_minimum']) ?></p>
                            </div>
                            <div class="<?= $classes_champs['prix_maximum'] ?>">
                                <label class="champ__libelle" for="prix-maximum">Prix maximum</label>
                                <input class="champ__saisie" id="prix-maximum" name="prix_maximum" type="number" min="0" max="9999.99" step="0.01" value="<?= echapper_html($valeurs_recherche['prix_maximum']) ?>" placeholder="Max. €" aria-describedby="erreur-prix-maximum"<?= $attributs_invalides['prix_maximum'] ?>>
                                <p class="champ__erreur" id="erreur-prix-maximum"><?= echapper_html($erreurs_recherche['prix_maximum']) ?></p>
                            </div>
                        </div>

                                                <?php 
                        // --- ÉTAPE 12 : LE BOUTON RADIO À CHOIX UNIQUE (LE STATUT DE VENTE) ---
                        // Un bouton radio choisit les ventes à inclure
                        // Le "fieldset" rassemble plusieurs ronds à cocher. Grâce à la boucle "foreach", on fait le tour de la liste "$statuts" (Toutes, En cours, Terminées)
                        // Si une option correspond au choix du visiteur, on lui ajoute la couleur "est-active" et on coche le rond
                        ?>
                        <fieldset class="<?= $classes_champs['statut'] ?> groupe-statut" aria-describedby="erreur-statut">
                            <legend class="champ__libelle">Statut de la vente</legend>
                            <div class="choix-segmente">
                                <?php foreach ($statuts as $valeur_statut => $libelle_statut) { ?>
                                    <label class="choix-segmente__option<?php
                                        if ($valeurs_recherche['statut'] === $valeur_statut) {
                                            echo ' est-active';
                                        }
                                    ?>">
                                        <input class="texte-visuellement-cache" type="radio" name="statut" value="<?= echapper_html($valeur_statut) ?>"<?php if ($valeurs_recherche['statut'] === $valeur_statut) { echo ' checked'; } ?>> <?= echapper_html($libelle_statut) ?> </label>
                                <?php } ?>
                            </div>
                            <p class="champ__erreur" id="erreur-statut"><?= echapper_html($erreurs_recherche['statut']) ?></p>
                        </fieldset>
                        <button class="bouton bouton--large" type="submit"><?php if ($mode_recherche === true) { ?>Appliquer les filtres<?php } else { ?>Lancer la recherche<?php } ?></button>
                    </form>
                </aside>

                <?php 
                // --- ÉTAPE 13 : L'ÉTIQUETTE DE PRÉSENTATION DES RÉSULTATS ---
                // Cette section affiche la sélection initiale ou les résultats d'une recherche
                // Si l'interrupteur "$mode_recherche" est activé, on compte les annonces trouvées
                // S'il n'y a qu'une seule annonce, il écrit "annonce correspond" au singulier, sinon il met un "s" au pluriel.
                // Si le visiteur vient juste d'arriver sur le site (sans chercher), on affiche simplement le titre standard "Enchères en cours"
                ?>
                <section class="resultats-recherche" aria-labelledby="titre-selection">
                    <header class="resultats-recherche__entete">
                        <?php if ($mode_recherche === true) { ?>
                            <h2 id="titre-selection">
                                <?= (int) $nombre_resultats ?>
                                <?php if ((int) $nombre_resultats === 1) { ?>annonce correspond<?php } else { ?>annonces correspondent<?php } ?>
                                à votre recherche
                            </h2>
                            <p class="resultats-recherche__description">Tri automatique selon l'échéance des ventes</p>
                        <?php } else { ?>
                            <h2 id="titre-selection">Enchères en cours</h2>
                            <p class="resultats-recherche__description">Découvrez les ventes ouvertes</p>
                        <?php } ?>
                    </header>

                    <?php 
                    // --- ÉTAPE 14 : LES PANNEAUX D'AFFICHAGE UNIQUES ---
                    // C'est un jeu de conditions :
                    // 1. Si la boîte "$erreur_accueil" contient du texte, on allume le panneau rouge de panne d'affichage
                    // 2. Sinon, si le visiteur a fait une recherche invalide, on lui demande de corriger ses filtres
                    // 3. Sinon, si la liste des annonces est complètement vide, on montre un panneau de recherche infructueuse
                    // 4. Enfin, si tout va bien et qu'on a des annonces, on ouvre les portes de la grande grille
                    ?>
                    <?php if ($erreur_accueil !== '') { ?>
                        <div class="message message--erreur" role="alert">
                            <p class="message__titre">Affichage indisponible</p>
                            <p><?= echapper_html($erreur_accueil) ?></p>
                        </div>
                    <?php } elseif ($mode_recherche === true && $recherche_valide === false) { ?>
                        <div class="message message--erreur" role="alert">
                            <p class="message__titre">Certains filtres sont invalides</p>
                            <p>Corrigez les champs signalés puis relancez la recherche.</p>
                        </div>
                    <?php } elseif (empty($annonces_accueil)) { ?>
                        <div class="message resultats-recherche__etat" role="status">
                            <span class="resultats-recherche__icone-vide" aria-hidden="true">⌕</span>
                            <?php if ($mode_recherche === true) { ?>
                                <p class="message__titre">Aucune annonce ne correspond à vos filtres</p>
                                <p>Retirez un critère ou élargissez votre fourchette de prix.</p>
                                <a class="bouton bouton--contour" href="afficher_accueil.php">Réinitialiser les filtres</a>
                            <?php } else { ?>
                                <p class="message__titre">Aucune enchère en cours</p>
                                <p>Aucune annonce ayant reçu une enchère n'est disponible pour le moment.</p>
                            <?php } ?>
                        </div>
                    <?php } else { ?>

                        <?php 
                        // --- ÉTAPE 15 : LA FABRICATION DES ANNONCES ---
                        // La boucle "foreach" inspecte notre tiroir à infos  "$annonces_accueil"
                        // Pour chaque annonce trouvée, elle fabrique une carte cliquable (qui conduit à son détail) avec sa photo principale, son étiquette de statut (En cours ou Terminée), son titre, son prix actuel et la date limite
                        ?>
                        <div class="grille-annonces">
                            <?php foreach ($annonces_accueil as $annonce) { ?>
                                <a class="carte-annonce" href="afficher_detail_annonce.php?id=<?= (int) $annonce['id'] ?>">
                                    <?php if ($annonce['url_photo'] !== '') { ?>
                                        <img class="carte-annonce__photo" src="<?= echapper_html($annonce['url_photo']) ?>" alt="Photographie principale de <?= echapper_html($annonce['titre']) ?>">
                                    <?php } else { ?>
                                        <div class="carte-annonce__photo-absente"><span>Aucune photographie</span></div>
                                    <?php } ?>
                                    <div class="carte-annonce__contenu">
                                        <span class="etiquette<?php if ($annonce['statut'] === 'Terminée') { ?> etiquette--neutre<?php } ?>"><?= echapper_html($annonce['statut']) ?></span>
                                        <h3 class="carte-annonce__titre"><?= echapper_html($annonce['titre']) ?></h3>
                                        <p class="carte-annonce__categorie"><?= echapper_html($annonce['categorie']) ?></p>
                                        <div class="carte-annonce__pied">
                                            <strong class="carte-annonce__prix"><?= echapper_html($annonce['prix_courant']) ?> €</strong>
                                            <span class="carte-annonce__metadonnees">
                                                <?= (int) $annonce['nombre_encheres'] ?>
                                                <?php if ((int) $annonce['nombre_encheres'] === 1) { ?>enchère<?php } else { ?>enchères<?php } ?>
                                                · fin <?= echapper_html($annonce['fin_vente']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?php
                    // --- ÉTAPE 16 : LA PAGINATION ---
                    // On charge un fragment de code externe pour afficher les boutons "Page suivante" et "Page précédente" permettant de feuilleter les annonces six par six
                    require_once 'templates/fragments/pagination_accueil.php';
                    ?>
                </section>
            </div>
        </main>

        <?php 
        // --- LE FOOTER --- 
        // Le pied de page commun termine la page.
        require_once 'templates/fragments/footer.php'; 
        ?>
    </div>
</body>
</html>

