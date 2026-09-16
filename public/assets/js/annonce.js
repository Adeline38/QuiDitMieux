// Rôle : Prévisualiser, choisir et retirer les photographies puis demander confirmation avant une suppression d'annonce.
// Paramètres : Le script utilise uniquement les éléments préparés par le template annonce.php.
// Retour : L'interface et les champs du formulaire reflètent les choix locaux avant leur envoi au serveur.

document.addEventListener('DOMContentLoaded', function () {
    const champFichiers = document.querySelector('[data-photo-input]');
    const zonePhotos = document.querySelector('[data-photo-zone]');
    const zoneErreur = document.querySelector('[data-photo-erreur]');
    const champPhotoPrincipale = document.querySelector('[data-photo-principale]');

    if (champFichiers && zonePhotos && zoneErreur && champPhotoPrincipale) {
        const nombreMaximum = 3;
        const tailleMaximum = 5 * 1024 * 1024;
        const typesAcceptes = ['image/jpeg', 'image/png', 'image/webp'];
        const cartesExistantes = Array.from(zonePhotos.querySelectorAll('[data-photo-existante]'));
        const suppressionsExistantes = Array.from(document.querySelectorAll('[data-photo-suppression]'));
        let fichiersSelectionnes = [];
        let urlsTemporaires = [];
        let identifiantPrincipalExistant = '';
        let fichierPrincipal = null;

        // Le choix préparé par le serveur est repris uniquement lorsqu'il désigne une photographie existante.
        if (champPhotoPrincipale.value.indexOf('existante:') === 0) {
            identifiantPrincipalExistant = champPhotoPrincipale.value.substring('existante:'.length);
        }

        // Les adresses créées par le navigateur sont libérées avant chaque nouvelle prévisualisation.
        function libererUrlsTemporaires() {
            urlsTemporaires.forEach(function (url) {
                URL.revokeObjectURL(url);
            });

            urlsTemporaires = [];
        }

        // Le champ de fichiers est reconstruit pour transmettre les nouvelles photos encore visibles dans leur ordre.
        function synchroniserChampFichiers() {
            const transfert = new DataTransfer();

            fichiersSelectionnes.forEach(function (fichier) {
                transfert.items.add(fichier);
            });

            champFichiers.files = transfert.files;
        }

        function afficherErreur(message) {
            zoneErreur.textContent = message;
            zoneErreur.hidden = false;
        }

        function effacerErreur() {
            zoneErreur.textContent = '';
            zoneErreur.hidden = true;
        }

        function compterPhotosExistantesConservees() {
            let nombre = 0;

            suppressionsExistantes.forEach(function (caseSuppression) {
                if (!caseSuppression.checked) {
                    nombre += 1;
                }
            });

            return nombre;
        }

        function photoExistanteEstConservee(identifiant) {
            const caseSuppression = document.querySelector('[data-photo-suppression="' + identifiant + '"]');

            return caseSuppression && caseSuppression.checked === false;
        }

        // Si le choix courant disparaît, la première photographie restante devient automatiquement principale.
        function choisirPrincipaleParDefaut() {
            if (identifiantPrincipalExistant !== '' && photoExistanteEstConservee(identifiantPrincipalExistant)) {
                return;
            }

            if (fichierPrincipal && fichiersSelectionnes.includes(fichierPrincipal)) {
                return;
            }

            identifiantPrincipalExistant = '';
            fichierPrincipal = null;

            for (const carte of cartesExistantes) {
                const identifiant = carte.getAttribute('data-photo-existante');

                if (photoExistanteEstConservee(identifiant)) {
                    identifiantPrincipalExistant = identifiant;
                    return;
                }
            }

            if (fichiersSelectionnes.length > 0) {
                fichierPrincipal = fichiersSelectionnes[0];
            }
        }

        // Le champ caché transmet un identifiant existant ou l'index du nouveau fichier dans le prochain POST.
        function synchroniserPhotoPrincipale() {
            choisirPrincipaleParDefaut();
            champPhotoPrincipale.value = '';

            if (identifiantPrincipalExistant !== '') {
                champPhotoPrincipale.value = 'existante:' + identifiantPrincipalExistant;
                return;
            }

            if (fichierPrincipal) {
                const indexPrincipal = fichiersSelectionnes.indexOf(fichierPrincipal);

                if (indexPrincipal >= 0) {
                    champPhotoPrincipale.value = 'nouvelle:' + String(indexPrincipal);
                }
            }
        }

        function creerEmplacementVide() {
            const etiquette = document.createElement('label');
            const symbole = document.createElement('span');
            const texte = document.createElement('span');

            etiquette.className = 'annonce__ajout-photo';
            etiquette.setAttribute('for', 'photographies');
            symbole.className = 'annonce__ajout-photo-symbole';
            symbole.setAttribute('aria-hidden', 'true');
            symbole.textContent = '＋';
            texte.textContent = 'Ajouter une photo';
            etiquette.append(symbole, texte);

            return etiquette;
        }

        // Une nouvelle photographie possède une image locale, un choix principal et une commande de retrait.
        function creerPrevisualisation(fichier, positionVisible, indexFichier) {
            const conteneur = document.createElement('div');
            const zoneImage = document.createElement('button');
            const image = document.createElement('img');
            const statut = document.createElement('span');
            const boutonSuppression = document.createElement('button');
            const url = URL.createObjectURL(fichier);

            urlsTemporaires.push(url);
            conteneur.className = 'annonce__photo-selectionnee';
            zoneImage.className = 'annonce__choix-photo';
            zoneImage.type = 'button';
            zoneImage.setAttribute('data-photo-choix', '');
            zoneImage.setAttribute('data-photo-nouvelle', String(indexFichier));
            zoneImage.setAttribute('aria-label', 'Choisir ' + fichier.name + ' comme photographie principale');
            image.src = url;
            image.alt = 'Prévisualisation de ' + fichier.name;
            statut.className = 'annonce__statut-photo';

            if (fichier === fichierPrincipal) {
                conteneur.classList.add('est-principale');
                statut.textContent = 'Principale';
                zoneImage.setAttribute('aria-pressed', 'true');
            } else {
                statut.textContent = 'Photo ' + String(positionVisible);
                zoneImage.setAttribute('aria-pressed', 'false');
            }

            zoneImage.addEventListener('click', function () {
                identifiantPrincipalExistant = '';
                fichierPrincipal = fichier;
                afficherPhotographies();

                const indexChoisi = fichiersSelectionnes.indexOf(fichier);
                const commandeChoisie = zonePhotos.querySelector('[data-photo-nouvelle="' + String(indexChoisi) + '"]');

                if (commandeChoisie) {
                    commandeChoisie.focus();
                }
            });

            boutonSuppression.className = 'annonce__supprimer-photo';
            boutonSuppression.type = 'button';
            boutonSuppression.textContent = 'Supprimer';
            boutonSuppression.setAttribute('aria-label', 'Supprimer la photographie ' + fichier.name);

            boutonSuppression.addEventListener('click', function () {
                if (fichier === fichierPrincipal) {
                    fichierPrincipal = null;
                }

                fichiersSelectionnes.splice(indexFichier, 1);
                synchroniserChampFichiers();
                afficherPhotographies();
                champFichiers.focus();
            });

            zoneImage.append(image, statut);
            conteneur.append(zoneImage, boutonSuppression);

            return conteneur;
        }

        // L'affichage réunit les photos conservées et les nouveaux fichiers dans les trois emplacements de la maquette.
        function afficherPhotographies() {
            libererUrlsTemporaires();
            zonePhotos.replaceChildren();
            synchroniserPhotoPrincipale();
            let positionVisible = 1;

            cartesExistantes.forEach(function (carte) {
                const identifiant = carte.getAttribute('data-photo-existante');
                const caseSuppression = document.querySelector('[data-photo-suppression="' + identifiant + '"]');
                const statut = carte.querySelector('[data-photo-statut]');
                const commande = carte.querySelector('[data-photo-commande]');
                const commandeChoix = carte.querySelector('[data-photo-choix]');

                carte.classList.remove('est-principale', 'est-supprimee');

                if (caseSuppression && caseSuppression.checked) {
                    carte.classList.add('est-supprimee');
                    statut.textContent = 'Sera supprimée';
                    commande.textContent = 'Annuler';
                    commandeChoix.disabled = true;
                    commandeChoix.setAttribute('aria-pressed', 'false');
                } else {
                    commandeChoix.disabled = false;

                    if (identifiant === identifiantPrincipalExistant) {
                        carte.classList.add('est-principale');
                        statut.textContent = 'Principale';
                        commandeChoix.setAttribute('aria-pressed', 'true');
                    } else {
                        statut.textContent = 'Photo ' + String(positionVisible);
                        commandeChoix.setAttribute('aria-pressed', 'false');
                    }

                    commande.textContent = 'Supprimer';
                    positionVisible += 1;
                }

                zonePhotos.appendChild(carte);
            });

            fichiersSelectionnes.forEach(function (fichier, indexFichier) {
                zonePhotos.appendChild(creerPrevisualisation(fichier, positionVisible, indexFichier));
                positionVisible += 1;
            });

            const nombreFinal = compterPhotosExistantesConservees() + fichiersSelectionnes.length;

            for (let position = nombreFinal; position < nombreMaximum; position += 1) {
                zonePhotos.appendChild(creerEmplacementVide());
            }
        }

        // Les commandes existantes choisissent la principale ou préparent une suppression pour le prochain POST.
        cartesExistantes.forEach(function (carte) {
            const identifiant = carte.getAttribute('data-photo-existante');
            const caseSuppression = document.querySelector('[data-photo-suppression="' + identifiant + '"]');
            const commandeChoix = carte.querySelector('[data-photo-choix]');
            const commandeSuppression = carte.querySelector('[data-photo-commande]');

            commandeChoix.addEventListener('click', function () {
                if (caseSuppression.checked) {
                    return;
                }

                identifiantPrincipalExistant = identifiant;
                fichierPrincipal = null;
                afficherPhotographies();
                commandeChoix.focus();
            });

            commandeSuppression.addEventListener('click', function () {
                caseSuppression.checked = !caseSuppression.checked;

                if (caseSuppression.checked && identifiantPrincipalExistant === identifiant) {
                    identifiantPrincipalExistant = '';
                }

                const nombreFinal = compterPhotosExistantesConservees() + fichiersSelectionnes.length;

                if (nombreFinal > nombreMaximum) {
                    caseSuppression.checked = true;
                    afficherErreur('Le nombre total de photographies ne peut pas dépasser trois.');
                } else {
                    effacerErreur();
                }

                afficherPhotographies();
                commandeSuppression.focus();
            });
        });

        // Le navigateur réalise un premier contrôle ; le serveur vérifiera ensuite le type MIME réel.
        champFichiers.addEventListener('change', function () {
            const nouveauxFichiers = Array.from(champFichiers.files);
            const placesDisponibles = nombreMaximum - compterPhotosExistantesConservees() - fichiersSelectionnes.length;

            effacerErreur();

            if (nouveauxFichiers.length > placesDisponibles) {
                afficherErreur('Le nombre total de photographies conservées et ajoutées ne peut pas dépasser trois.');
                synchroniserChampFichiers();
                return;
            }

            for (const fichier of nouveauxFichiers) {
                if (!typesAcceptes.includes(fichier.type)) {
                    afficherErreur('Seuls les fichiers JPEG, PNG et WebP sont acceptés.');
                    synchroniserChampFichiers();
                    return;
                }

                if (fichier.size > tailleMaximum) {
                    afficherErreur('Chaque photographie doit peser au maximum 5 Mo.');
                    synchroniserChampFichiers();
                    return;
                }
            }

            fichiersSelectionnes = fichiersSelectionnes.concat(nouveauxFichiers);
            synchroniserChampFichiers();
            afficherPhotographies();
        });

        afficherPhotographies();

        window.addEventListener('beforeunload', function () {
            libererUrlsTemporaires();
        });
    }

    const dialogueSuppression = document.querySelector('[data-confirmation-suppression]');
    const boutonOuverture = document.querySelector('[data-ouvrir-suppression]');
    const boutonFermeture = document.querySelector('[data-fermer-suppression]');

    if (dialogueSuppression && boutonOuverture && boutonFermeture) {
        // La fenêtre modale s'ouvre sans envoyer le formulaire de suppression.
        boutonOuverture.addEventListener('click', function () {
            dialogueSuppression.showModal();
            boutonFermeture.focus();
        });

        function fermerConfirmation() {
            dialogueSuppression.close();
            boutonOuverture.focus();
        }

        boutonFermeture.addEventListener('click', fermerConfirmation);

        dialogueSuppression.addEventListener('cancel', function (evenement) {
            evenement.preventDefault();
            fermerConfirmation();
        });

        // La touche Tab reste entre les commandes de la confirmation tant qu'elle est ouverte.
        dialogueSuppression.addEventListener('keydown', function (evenement) {
            if (evenement.key !== 'Tab') {
                return;
            }

            const commandes = dialogueSuppression.querySelectorAll('button');
            const premiereCommande = commandes[0];
            const derniereCommande = commandes[commandes.length - 1];

            if (evenement.shiftKey && document.activeElement === premiereCommande) {
                evenement.preventDefault();
                derniereCommande.focus();
            } else if (!evenement.shiftKey && document.activeElement === derniereCommande) {
                evenement.preventDefault();
                premiereCommande.focus();
            }
        });
    }
});
