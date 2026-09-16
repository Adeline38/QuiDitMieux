// Rôle : Actualiser les trois sections du tableau de bord sans recharger toute la page.
// Paramètres : Le script utilise les conteneurs et le jeton préparés par tableau_de_bord.php.
// Retour : Les cartes reflètent les données JSON reçues toutes les dix ou deux secondes.

document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.page-tableau-de-bord');
    const zoneVendeur = document.querySelector('[data-cartes-vendeur]');
    const zoneSuivies = document.querySelector('[data-cartes-suivies]');
    const zoneRemportees = document.querySelector('[data-cartes-remportees]');
    const zoneMessage = document.querySelector('[data-message-actualisation]');

    if (!page || !zoneVendeur || !zoneSuivies || !zoneRemportees || !zoneMessage) {
        return;
    }

    const jetonCsrf = page.getAttribute('data-jeton-csrf');
    let vendeurEnCours = false;
    let acheteurEnCours = false;
    let signatureVendeur = '';
    let signatureSuivies = '';
    let signatureRemportees = '';
    let intervalleVendeur = 0;
    let intervalleAcheteur = 0;

    // Cette fonction crée une étiquette dont la couleur traduit la situation de l'utilisateur.
    function creerEtiquette(carte) {
        const etiquette = document.createElement('span');

        etiquette.className = 'etiquette etiquette--neutre';

        if (carte.couleur === 'positive') {
            etiquette.className = 'etiquette etiquette--positive';
        } else if (carte.couleur === 'negative') {
            etiquette.className = 'etiquette etiquette--negative';
        }

        etiquette.textContent = carte.statut;
        return etiquette;
    }

    // Chaque carte est construite avec textContent afin qu'un titre ne puisse jamais devenir du code HTML.
    function creerCarte(carte, typeCarte) {
        const article = document.createElement('article');
        const informations = document.createElement('div');
        const titre = document.createElement('h3');
        const lienTitre = document.createElement('a');
        const prix = document.createElement('strong');
        const metadonnees = document.createElement('p');

        article.className = 'carte-tableau';
        article.setAttribute('data-annonce-id', String(carte.id));

        if (carte.couleur === 'positive') {
            article.classList.add('carte-tableau--positive');
        } else if (carte.couleur === 'negative') {
            article.classList.add('carte-tableau--negative');
        }

        if (carte.photo_url !== '') {
            const image = document.createElement('img');
            image.className = 'carte-tableau__photo';
            image.src = carte.photo_url;
            image.alt = 'Photographie de ' + carte.titre;
            article.appendChild(image);
        } else {
            const photoAbsente = document.createElement('div');
            photoAbsente.className = 'carte-tableau__photo carte-tableau__photo--absente';
            photoAbsente.textContent = 'Aucune photo';
            article.appendChild(photoAbsente);
        }

        informations.className = 'carte-tableau__informations';
        titre.className = 'carte-tableau__titre';
        lienTitre.href = 'afficher_detail_annonce.php?id=' + String(carte.id);
        lienTitre.textContent = carte.titre;
        titre.appendChild(lienTitre);
        prix.className = 'carte-tableau__prix';
        prix.textContent = carte.prix_courant + ' €';
        metadonnees.className = 'carte-tableau__metadonnees';

        if (typeCarte === 'remportee') {
            metadonnees.textContent = String(carte.nombre_encheres) + ' enchère(s) · gagnant : vous';
        } else {
            metadonnees.textContent = String(carte.nombre_encheres) + ' enchère(s) · fin ' + carte.fin_vente;
        }

        informations.append(creerEtiquette(carte), titre, prix, metadonnees);

        // Les actions vendeur restent visibles uniquement si la réponse serveur confirme encore leur autorisation.
        if (typeCarte === 'vendeur' && carte.peut_modifier === true) {
            const actions = document.createElement('div');
            const lienModification = document.createElement('a');
            const formulaireSuppression = document.createElement('form');
            const identifiantSuppression = document.createElement('input');
            const jetonSuppression = document.createElement('input');
            const boutonSuppression = document.createElement('button');

            actions.className = 'carte-tableau__actions';
            lienModification.href = 'afficher_modification_annonce.php?id=' + String(carte.id);
            lienModification.textContent = 'Modifier';
            formulaireSuppression.action = 'enregistrer_suppression_annonce.php';
            formulaireSuppression.method = 'post';
            formulaireSuppression.setAttribute('data-formulaire-suppression', '');
            identifiantSuppression.type = 'hidden';
            identifiantSuppression.name = 'annonce_id';
            identifiantSuppression.value = String(carte.id);
            jetonSuppression.type = 'hidden';
            jetonSuppression.name = 'jeton_csrf';
            jetonSuppression.value = jetonCsrf;
            boutonSuppression.type = 'submit';
            boutonSuppression.textContent = 'Supprimer';
            formulaireSuppression.append(identifiantSuppression, jetonSuppression, boutonSuppression);
            actions.append(lienModification, formulaireSuppression);
            informations.appendChild(actions);
        }

        // Retirer le suivi supprime seulement l'association volontaire, jamais les enchères déjà placées.
        if (typeCarte === 'suivie' && carte.suivi_volontaire === true) {
            const formulaireSuivi = document.createElement('form');
            const identifiantSuivi = document.createElement('input');
            const jetonSuivi = document.createElement('input');
            const boutonSuivi = document.createElement('button');

            formulaireSuivi.className = 'carte-tableau__suivi';
            formulaireSuivi.action = 'enregistrer_suppression_suivi.php';
            formulaireSuivi.method = 'post';
            identifiantSuivi.type = 'hidden';
            identifiantSuivi.name = 'annonce_id';
            identifiantSuivi.value = String(carte.id);
            jetonSuivi.type = 'hidden';
            jetonSuivi.name = 'jeton_csrf';
            jetonSuivi.value = jetonCsrf;
            boutonSuivi.type = 'submit';
            boutonSuivi.textContent = 'Ne plus suivre';
            formulaireSuivi.append(identifiantSuivi, jetonSuivi, boutonSuivi);
            informations.appendChild(formulaireSuivi);
        }

        article.appendChild(informations);
        return article;
    }

    // L'état vide conserve l'action utile prévue par la maquette pour chaque section.
    function creerEtatVide(typeCarte) {
        const conteneur = document.createElement('div');
        const texte = document.createElement('p');

        conteneur.className = 'section-tableau__etat-vide';

        if (typeCarte === 'vendeur') {
            const lienPublication = document.createElement('a');
            texte.textContent = 'Vous n’avez publié aucune annonce.';
            lienPublication.className = 'bouton';
            lienPublication.href = 'afficher_annonce.php';
            lienPublication.textContent = 'Publier une annonce';
            conteneur.append(texte, lienPublication);
        } else if (typeCarte === 'suivie') {
            const lienAccueil = document.createElement('a');
            texte.textContent = 'Vous ne suivez aucune annonce et n’avez encore placé aucune enchère.';
            lienAccueil.className = 'bouton';
            lienAccueil.href = 'afficher_accueil.php';
            lienAccueil.textContent = 'Découvrir les annonces';
            conteneur.append(texte, lienAccueil);
        } else {
            texte.textContent = 'Vous n’avez encore remporté aucune enchère.';
            conteneur.appendChild(texte);
        }

        return conteneur;
    }

    // La zone n'est reconstruite que lorsque les données changent, ce qui évite de déplacer inutilement le focus.
    function afficherCartes(zone, cartes, typeCarte) {
        const elements = [];

        if (cartes.length === 0) {
            elements.push(creerEtatVide(typeCarte));
        } else {
            cartes.forEach(function (carte) {
                elements.push(creerCarte(carte, typeCarte));
            });
        }

        zone.replaceChildren.apply(zone, elements);
    }

    function afficherErreur(erreur) {
        if (erreur === 'session_expiree') {
            zoneMessage.textContent = 'Votre session a expiré. Rechargez la page pour vous reconnecter.';
            window.clearInterval(intervalleVendeur);
            window.clearInterval(intervalleAcheteur);
        } else {
            zoneMessage.textContent = 'L’actualisation automatique est momentanément indisponible.';
        }
    }

    // Les annonces du vendeur sont interrogées indépendamment toutes les dix secondes.
    function actualiserVendeur() {
        if (vendeurEnCours === true || document.hidden === true) {
            return;
        }

        vendeurEnCours = true;

        fetch('actualiser_annonces_vendeur_10s_AJAX.php', {method: 'GET'})
            .then(function (reponse) {
                if (!reponse.ok) {
                    return null;
                }

                return reponse.json();
            })
            .then(function (donnees) {
                vendeurEnCours = false;

                if (!donnees || donnees.succes !== true || !Array.isArray(donnees.annonces)) {
                    let erreur = 'reponse_invalide';

                    if (donnees && donnees.erreur) {
                        erreur = donnees.erreur;
                    }

                    afficherErreur(erreur);
                    return;
                }

                zoneMessage.textContent = '';

                const nouvelleSignature = JSON.stringify(donnees.annonces);

                if (nouvelleSignature !== signatureVendeur) {
                    afficherCartes(zoneVendeur, donnees.annonces, 'vendeur');
                    signatureVendeur = nouvelleSignature;
                }
            })
            .catch(function () {
                vendeurEnCours = false;
                afficherErreur('reseau');
            });
    }

    // Les suivis et les gains partagent la même réponse afin qu'une victoire change de section en une seule actualisation.
    function actualiserAcheteur() {
        if (acheteurEnCours === true || document.hidden === true) {
            return;
        }

        acheteurEnCours = true;

        fetch('actualiser_annonces_suivies_2s_AJAX.php', {method: 'GET'})
            .then(function (reponse) {
                if (!reponse.ok) {
                    return null;
                }

                return reponse.json();
            })
            .then(function (donnees) {
                acheteurEnCours = false;

                if (!donnees || donnees.succes !== true || !Array.isArray(donnees.annonces_suivies) || !Array.isArray(donnees.annonces_remportees)) {
                    let erreur = 'reponse_invalide';

                    if (donnees && donnees.erreur) {
                        erreur = donnees.erreur;
                    }

                    afficherErreur(erreur);
                    return;
                }

                // Une carte remportée est considérée comme déplacée seulement si elle se trouvait encore dans la section précédente.
                const cartesDeplacees = [];
                let annonceAvecFocus = '';
                const carteAvecFocus = document.activeElement.closest('[data-annonce-id]');

                if (carteAvecFocus) {
                    annonceAvecFocus = carteAvecFocus.getAttribute('data-annonce-id');
                }

                donnees.annonces_remportees.forEach(function (carte) {
                    const carteEncoreSuivie = zoneSuivies.querySelector('[data-annonce-id="' + String(carte.id) + '"]');

                    if (carteEncoreSuivie) {
                        cartesDeplacees.push(carte);
                    }
                });

                zoneMessage.textContent = '';

                const nouvelleSignatureSuivies = JSON.stringify(donnees.annonces_suivies);
                const nouvelleSignatureRemportees = JSON.stringify(donnees.annonces_remportees);

                if (nouvelleSignatureSuivies !== signatureSuivies) {
                    afficherCartes(zoneSuivies, donnees.annonces_suivies, 'suivie');
                    signatureSuivies = nouvelleSignatureSuivies;
                }

                if (nouvelleSignatureRemportees !== signatureRemportees) {
                    afficherCartes(zoneRemportees, donnees.annonces_remportees, 'remportee');
                    signatureRemportees = nouvelleSignatureRemportees;
                }

                // Le message rend le changement de section perceptible sans obliger l'utilisateur à le deviner visuellement.
                if (cartesDeplacees.length === 1) {
                    zoneMessage.textContent = 'La vente « ' + cartesDeplacees[0].titre + ' » a été déplacée dans Enchères remportées.';
                } else if (cartesDeplacees.length > 1) {
                    zoneMessage.textContent = String(cartesDeplacees.length) + ' ventes ont été déplacées dans Enchères remportées.';
                }

                // Si le clavier se trouvait dans la carte déplacée, le focus rejoint son lien dans la nouvelle section.
                if (annonceAvecFocus !== '') {
                    const carteDeplaceeAvecFocus = zoneRemportees.querySelector('[data-annonce-id="' + annonceAvecFocus + '"]');

                    if (carteDeplaceeAvecFocus) {
                        const lienDeplace = carteDeplaceeAvecFocus.querySelector('a');

                        if (lienDeplace) {
                            lienDeplace.focus();
                        }
                    }
                }
            })
            .catch(function () {
                acheteurEnCours = false;
                afficherErreur('reseau');
            });
    }

    // Une suppression irréversible exige une confirmation même lorsque la carte vient d'être reconstruite.
    page.addEventListener('submit', function (evenement) {
        const formulaire = evenement.target;

        if (formulaire.matches('[data-formulaire-suppression]')) {
            const confirmation = window.confirm('Supprimer définitivement cette annonce ?');

            if (confirmation === false) {
                evenement.preventDefault();
            }
        }
    });

    signatureVendeur = JSON.stringify([]);
    signatureSuivies = JSON.stringify([]);
    signatureRemportees = JSON.stringify([]);
    actualiserVendeur();
    actualiserAcheteur();
    intervalleVendeur = window.setInterval(actualiserVendeur, 10000);
    intervalleAcheteur = window.setInterval(actualiserAcheteur, 2000);
});
