<?php // FRAGMENT : formulaire_annonce.php

// Rôle : Afficher les champs du formulaire de création ou de modification d'une annonce.
// Paramètres : 
/**
        * @var int $annonce_id : Identifiant unique de l'annonce

        * @var bool $publication_disponible : Autorisation de publication
        * @var bool $mode_modification : Mode édition actif ou non

        * @var string $titre_formulaire : Grand titre du formulaire
        * @var array $valeurs_annonce : Données saisies de l'annonce
        * @var array $categories : Liste des catégories d'achat
        * @var array $etats_annonce : Liste des états de l'objet
        * @var string $date_minimale : Date limite dans le passé
        
        * @var string $photo_principale_choisie : Nom de la photo principale
        * @var array $photographies_existantes : Photos déjà enregistrées
        * @var array $identifiants_photos_supprimees : ID des photos à supprimer
        
        * @var string $libelle_action : Texte du bouton de validation (ex: "Créer" ou "Modifier")
        * @var string $action_formulaire : URL d'envoi du formulaire

        * @var string $titre_erreur : Message d'erreur ciblé
        * @var array $erreurs_annonce : Erreurs de validation par champ
        * @var string $erreur_generale : Message de panne générale

        * @var string $jeton_csrf : Code secret de sécurité caché qui prouve que le formulaire vient bien de notre propre site web
 */
// Retour : Le formulaire HTML complet prêt à être envoyé au contrôleur

?>
<form class="page-annonce" action="<?= echapper_html($action_formulaire) ?>" method="post" enctype="multipart/form-data" >

    <?php 
    // --- ÉTAPE 1 : LES CASES CACHÉES DE SÉCURITÉ ---
    // Ici, on cache des informations secrètes pour que le site s'en rappelle tout seul
    // Ces champs cachés transmettent des informations sans les faire saisir à l'utilisateur
    // Le jeton protège le formulaire, l'identifiant désigne l'annonce et le dernier champ mémorise la photo principale
    ?>
    <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>" >
    <?php if ($mode_modification === true) { ?>
        <input type="hidden" name="annonce_id" value="<?= (int) $annonce_id ?>" >
    <?php } ?>
    <input type="hidden" name="photo_principale" value="<?= echapper_html($photo_principale_choisie) ?>" data-photo-principale >

    <?php 
    // --- ÉTAPE 2 : LES INFORMATIONS SUR L'OBJET
    // Le premier panneau regroupe les informations qui décrivent l'objet mis en vente ?>
    <section class="annonce__informations" aria-labelledby="titre-formulaire-annonce">
        <h1 id="titre-formulaire-annonce" class="annonce__titre"><?= echapper_html($titre_formulaire) ?></h1>
        <p class="annonce__introduction">Tous les champs sont obligatoires, sauf les photographies.</p>

        <?php 
        // --- ÉTAPE 3 : LA GRANDE PANNE (L'ERREUR GÉNÉRALE) ---
        // Si la boîte "$erreur_generale" n'est pas vide, on ouvre une alerte pour dire que toute la page a un problème technique
        ?>
        <?php if ($erreur_generale !== '') { ?>
            <div class="message message--erreur" role="alert">
                <p class="message__titre"><?= echapper_html($titre_erreur) ?></p>
                <p><?= echapper_html($erreur_generale) ?></p>
            </div>
        <?php } ?>

        <?php 
        // --- ÉTAPE 4 : ÉCRIRE LE TITRE DE L'ANNONCE ---
        // Ce champ contient le nom court qui permet de reconnaître l'annonce
        // Si le visiteur fait une erreur, la boîte prend la couleur "est-invalide".
        ?>
        <div class="champ<?php if ($erreurs_annonce['titre'] !== '') { ?> est-invalide<?php } ?>">
            <label class="champ__libelle" for="titre">Titre</label>
            <input class="champ__saisie" id="titre" name="titre" type="text" maxlength="255" value="<?= echapper_html($valeurs_annonce['titre']) ?>" placeholder="Saisir le titre" aria-describedby="erreur-titre" <?php if ($erreurs_annonce['titre'] !== '') { ?>aria-invalid="true"<?php } ?> required >
            <p class="champ__erreur" id="erreur-titre"><?= echapper_html($erreurs_annonce['titre']) ?></p>
        </div>

        <?php 
        // --- ÉTAPE 5 : LA CATÉGORIE ---
        // Cette liste permet de choisir une seule catégorie parmi celles reçues de l'API
        // La balise "select" fabrique un menu déroulant
        // Grâce à la boucle "foreach", on écrit automatiquement chaque choix de catégorie l'un après l'autre
        ?>
        <div class="champ<?php if ($erreurs_annonce['categorie_id'] !== '') { ?> est-invalide<?php } ?>">
            <label class="champ__libelle" for="categorie-id">Catégorie</label>
            <select class="champ__saisie" id="categorie-id" name="categorie_id" aria-describedby="erreur-categorie" <?php if ($erreurs_annonce['categorie_id'] !== '') { ?>aria-invalid="true"<?php } ?> required <?php if ($publication_disponible === false) { ?>disabled<?php } ?> >
                <option value="">Choisir une catégorie</option>
                <?php 
                // Seules les catégories validées par le modèle externe sont proposées
                foreach ($categories as $categorie) { ?>
                    <option value="<?= (int) $categorie['id'] ?>" <?php if ($valeurs_annonce['categorie_id'] !== '' && (int) $valeurs_annonce['categorie_id'] === (int) $categorie['id']) { ?>selected<?php } ?> ><?= echapper_html($categorie['libelle']) ?></option>
                <?php } ?>
            </select>
            <p class="champ__erreur" id="erreur-categorie"><?= echapper_html($erreurs_annonce['categorie_id']) ?></p>
        </div>

        <?php 
        // --- ÉTAPE 6 : LA DESCRIPTION DÉTAILLÉE ---
        // Cette zone permet de présenter précisément l'objet mis en vente sur plusieurs lignes
        ?>
        <div class="champ<?php if ($erreurs_annonce['description'] !== '') { ?> est-invalide<?php } ?>">
            <label class="champ__libelle" for="description">Description détaillée</label>
            <textarea class="champ__saisie" id="description" name="description" placeholder="Saisir la description détaillée" aria-describedby="erreur-description" <?php if ($erreurs_annonce['description'] !== '') { ?>aria-invalid="true"<?php } ?> required >
                <?= echapper_html($valeurs_annonce['description']) ?>
            </textarea>
            <p class="champ__erreur" id="erreur-description"><?= echapper_html($erreurs_annonce['description']) ?></p>
        </div>

        <div class="groupe-champs annonce__etat-et-prix">
            <?php 
            // --- ÉTAPE 7 : L'ÉTAT DE L'OBJET (NEUF OU ABÎMÉ) ---
            // on regarde les options autorisées dans sa mémoire et recrée la liste proprement pour éviter que l'utilisateur n'écrive n'importe quoi
            ?>
            <div class="champ<?php if ($erreurs_annonce['etat'] !== '') { ?> est-invalide<?php } ?>">
                <label class="champ__libelle" for="etat">État de l'objet</label>
                <select class="champ__saisie" id="etat" name="etat" aria-describedby="erreur-etat" <?php if ($erreurs_annonce['etat'] !== '') { ?>aria-invalid="true"<?php } ?> required >
                    <option value="">Choisir un état</option>
                    <?php 
                    // Les quatre états viennent du cahier des charges et non d'une saisie libre
                    foreach ($etats_annonce as $etat_annonce) { ?>
                        <option value="<?= echapper_html($etat_annonce['valeur']) ?>" <?php if ($valeurs_annonce['etat'] === $etat_annonce['valeur']) { ?>selected<?php } ?> ><?= echapper_html($etat_annonce['libelle']) ?></option>
                    <?php } ?>
                </select>
                <p class="champ__erreur" id="erreur-etat"><?= echapper_html($erreurs_annonce['etat']) ?></p>
            </div>

            <?php 
            // --- ÉTAPE 8 : L'ARGENT (LE PRIX DE DÉPART) ---
            // Le type "number" transforme la case pour qu'elle n'accepte que des chiffres
            // Le paramètre "step=0.01" est une règle mathématique pour autoriser les centimes après la virgule
            ?>
            <div class="champ<?php if ($erreurs_annonce['prix_depart'] !== '') { ?> est-invalide<?php } ?>">
                <label class="champ__libelle" for="prix-depart">Prix de départ</label>
                <input class="champ__saisie" id="prix-depart" name="prix_depart" type="number" min="0.01" step="0.01" inputmode="decimal" value="<?= echapper_html($valeurs_annonce['prix_depart']) ?>" placeholder="Saisir le prix de départ" aria-describedby="erreur-prix-depart" <?php if ($erreurs_annonce['prix_depart'] !== '') { ?>aria-invalid="true"<?php } ?> required >
                <p class="champ__erreur" id="erreur-prix-depart"><?= echapper_html($erreurs_annonce['prix_depart']) ?></p>
            </div>
        </div>
    </section>

    <?php 
    // --- ÉTAPE 9 : LA FIN DE LA VENTE ET LES PHOTOGRAPHIES ---
    // Le second panneau rassemble la fin de la vente, les photographies et les boutons principaux ?>
    <section class="annonce__date-et-photos" aria-labelledby="titre-date-et-photos">
        <h2 id="titre-date-et-photos" class="texte-visuellement-cache">Échéance et photographies</h2>
        
        <div class="groupe-champs annonce__date-et-heure">
            <?php 
            // --- ÉTAPE 10 : LE CALENDRIER ET LA MONTRE (DATE ET HEURE DE FIN) ---
            // La date et l'heure sont séparées dans le formulaire (on sépare le temps en deux cases), puis réunies par le contrôleur
            // Le type "date" fait apparaître un mini calendrier interactif où on clique sur le jour choisi, et le type "time" affiche une horloge pour régler les minutes
            ?>
            <div class="champ<?php if ($erreurs_annonce['date_fin'] !== '') { ?> est-invalide<?php } ?>">
                <label class="champ__libelle" for="date-fin">Date de fin</label>
                <input class="champ__saisie" id="date-fin" name="date_fin" type="date" min="<?= echapper_html($date_minimale) ?>" value="<?= echapper_html($valeurs_annonce['date_fin']) ?>" aria-describedby="erreur-date-fin" <?php if ($erreurs_annonce['date_fin'] !== '') { ?>aria-invalid="true"<?php } ?> required >
                <p class="champ__erreur" id="erreur-date-fin"><?= echapper_html($erreurs_annonce['date_fin']) ?></p>
            </div>
            <div class="champ<?php if ($erreurs_annonce['heure_fin'] !== '') { ?> est-invalide<?php } ?>">
                <label class="champ__libelle" for="heure-fin">Heure de fin</label>
                <input class="champ__saisie" id="heure-fin" name="heure_fin" type="time" value="<?= echapper_html($valeurs_annonce['heure_fin']) ?>" aria-describedby="erreur-heure-fin" <?php if ($erreurs_annonce['heure_fin'] !== '') { ?>aria-invalid="true"<?php } ?> required >
                <p class="champ__erreur" id="erreur-heure-fin"><?= echapper_html($erreurs_annonce['heure_fin']) ?></p>
            </div>
        </div>

        <?php 
        // --- ÉTAPE 11 : PHOTOGRAPHIES DE L'ANNONCE ---
        // L'utilisateur peut publier une annonce sans photo ou choisir au maximum trois photographies ?>
        <div class="annonce__photographies">
            <label class="champ__libelle" for="photographies">Photographies (0 à 3)</label>
            <p class="champ__aide">Activez une photographie pour la choisir comme principale.</p>
            <input class="texte-visuellement-cache" id="photographies" name="photographies[]" type="file" accept="image/jpeg,image/png,image/webp" multiple data-photo-input >
            
            <?php 
            // --- ÉTAPE 12 : LE REPROCHE-PHOTO (LES CASES À COCHER MASQUÉES) ---
            // En modification, chaque case cachée correspond à une photographie déjà enregistrée
            // JavaScript coche la bonne case lorsque l'utilisateur demande de supprimer cette photographie
            // Si le créateur modifie son annonce et veut jeter une vieille photo, on utilise des cases invisibles "checkbox" qui se cochent en cachette pour lister les images à détruire
            ?>
            <div class="texte-visuellement-cache" data-photo-suppressions>
                <?php foreach ($photographies_existantes as $photographie_existante) { ?>
                    <input id="supprimer-photo-<?= (int) $photographie_existante['id'] ?>" name="supprimer_photos[]" type="checkbox" value="<?= (int) $photographie_existante['id'] ?>" data-photo-suppression="<?= (int) $photographie_existante['id'] ?>" <?php if (in_array((int) $photographie_existante['id'], $identifiants_photos_supprimees, true)) { ?>checked<?php } ?> >
                <?php } ?>
            </div>
            
            <?php 
            // --- ÉTAPE 13 : L'ALBUM SOUVENIR (AFFICHER LES IMAGES EXISTANTES) ---
            // La boucle répète une carte pour chaque photographie déjà enregistrée
            // Chaque carte permet de choisir la photo principale ou de demander sa suppression
            // La boucle lit la collection de photos de l'objet. 
            // Si le fichier est trouvé sur le disque dur, la balise "img" l'affiche à l'écran. Sinon, un texte de secours prévient du problème
            ?>
            <div class="annonce__emplacements-photos" data-photo-zone>
                <?php foreach ($photographies_existantes as $photographie_existante) { ?>
                    <div class="annonce__photo-selectionnee<?php if ($photo_principale_choisie === 'existante:' . (int) $photographie_existante['id']) { ?> est-principale<?php } ?>" data-photo-existante="<?= (int) $photographie_existante['id'] ?>">
                        <button class="annonce__choix-photo" type="button" data-photo-choix aria-label="Choisir cette photographie comme photographie principale" aria-pressed="<?php if ($photo_principale_choisie === 'existante:' . (int) $photographie_existante['id']) { ?>true<?php } else { ?>false<?php } ?>" >
                            <?php // On affiche la photographie si son fichier existe, sinon on montre un message simple ?>
                            <?php if ($photographie_existante['url'] !== '') { ?>
                                <img src="<?= echapper_html($photographie_existante['url']) ?>" alt="<?= echapper_html($photographie_existante['alt']) ?>">
                            <?php } else { ?>
                                <span class="annonce__photo-indisponible">Fichier indisponible</span>
                            <?php } ?>
                            <span class="annonce__statut-photo" data-photo-statut></span>
                        </button>
                        <button class="annonce__supprimer-photo" type="button" data-photo-commande>Supprimer</button>
                    </div>
                <?php } ?>
                
                <?php 
                // --- ÉTAPE 14 : COMPTER LES PLACES VIDES (MAXIMUM 3 PHOTOS) ---
                // On calcule le nombre de places disponibles pour ne jamais dépasser trois photographies
                // On fait un petit calcul de soustraction : 3 places au total MOINS les photos gardées
                // La boucle "for" tourne autant de fois qu'il reste de places vides pour dessiner des boutons "＋ Ajouter"
                ?>
                <?php $nombre_emplacements_vides = 3 - count($photographies_existantes) + count($identifiants_photos_supprimees); ?>
                <?php // La boucle ajoute une case vide pour chaque photographie que l'utilisateur peut encore choisir ?>
                <?php for ($position = 1; $position <= $nombre_emplacements_vides; $position++) { ?>
                    <label class="annonce__ajout-photo" for="photographies">
                        <span class="annonce__ajout-photo-symbole" aria-hidden="true">＋</span>
                        <span>Ajouter une photo</span>
                    </label>
                <?php } ?>
            </div>
            
            <?php 
            // --- ÉTAPE 15 : SÉCURITÉ SUR LA TAILLE DES IMAGES ---
            // Cette zone affiche une erreur si une photographie est trop lourde, invalide ou si le nombre maximal est dépassé
            // L'attribut "hidden" agit comme un interrupteur de lumière. Si la boîte d'erreurs est vide, le message se cache tout seul à l'écran pour ne pas embêter le visiteur
            ?>
            <p class="champ__erreur" role="alert" <?php if ($erreurs_annonce['photographies'] === '') { ?>hidden<?php } ?> data-photo-erreur><?= echapper_html($erreurs_annonce['photographies']) ?></p>
        </div>
        <?php // Ce rappel présente les formats et la taille acceptés avant l'envoi du formulaire ?>
        <aside class="message annonce__rappel" aria-labelledby="titre-rappel-photos">
            <p id="titre-rappel-photos" class="message__titre">Rappel</p>
            <p>Formats JPEG, PNG et WebP · 5 Mo maximum par photo.</p>
        </aside>
        
        <?php 
        // --- ÉTAPE 16 : LES BOUTONS D'ACTION FINALE ---
        // Le premier bouton enregistre le formulaire
        // En modification, un second bouton permet aussi d'ouvrir la confirmation de suppression
        // Le bouton de soumission "submit" rassemble tout le colis d'informations et l'envoie au serveur
        // Si l'annonce est en mode modification, un second bouton rouge apparaît en plus pour détruire l'annonce
        ?>
        <div class="annonce__actions">
            <button class="bouton annonce__bouton-publication" type="submit" <?php if ($publication_disponible === false) { ?>disabled<?php } ?> ><?= echapper_html($libelle_action) ?></button>
            <?php if ($mode_modification === true) { ?>
                <button class="bouton bouton--danger" type="button" data-ouvrir-suppression>Supprimer l’annonce</button>
            <?php } ?>
        </div>
    </section>
</form>

