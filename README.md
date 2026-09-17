# QuiDitMieux

Prototype d'application Web d'enchères entre particuliers réalisé dans le cadre de la formation
**Développeur Web Full Stack — niveau 5 (Bac+2)**.

QuiDitMieux est une application Web d'enchères entre particuliers développée en PHP 8.3 natif et MySQL, sans framework, Composer ni autoload. Un visiteur peut consulter et rechercher les annonces. Un membre peut publier, suivre et enchérir selon son rôle dans chaque vente.

> État documenté le 16 septembre 2026.

## Fonctionnalités

Fonctionnalités présentes dans le code actuel :

- accueil public avec une sélection de six annonces ayant reçu une enchère ;
- recherche publique multicritère, tri imposé et pagination de six résultats ;
- inscription et connexion par pseudo ou courriel ;
- déconnexion et modification du compte ;
- création d'une annonce avec zéro à trois photographies ;
- récupération des catégories par l'API externe QuiDitMieux avec cURL ;
- détail public d'une annonce et galerie de photographies ;
- enchère strictement supérieure au prix courant ;
- protection contre l'enchère sur sa propre annonce ou après la fin ;
- historique réservé au vendeur et aux participants ;
- suivi et arrêt du suivi d'une annonce ;
- modification et suppression conditionnelles d'une annonce ;
- tableau de bord vendeur, suivi/participation et ventes remportées ;
- actualisation AJAX à 10 secondes pour le vendeur et 2 secondes pour l'acheteur.

Sont volontairement hors périmètre : paiement, livraison, messagerie, avis, notifications et récupération d'un mot de passe oublié.

## Architecture du projet

```text
qdm-adeline/
├── *.php                    Contrôleurs accessibles par URL
├── core/                    Initialisation, session et modèle parent
├── library/                 Sécurité, validations et préparations communes
├── model/                   Modèles utilisateur, annonce, photo, enchère, catégorie
├── templates/
│   ├── pages/               Pages HTML
│   └── fragments/           Éléments HTML partagés
├── public/assets/           CSS, JavaScript et photographies
├── ressources/scss/         Sources SCSS organisées par responsabilité
└── documents/               Cahier des charges et conception
```

Le point d'entrée `index.php` transmet l'accueil à `afficher_accueil.php`. Les autres contrôleurs sont appelés directement par les liens, formulaires ou requêtes AJAX.

## Schémas ergonomiques

Le [schéma ergonomique](<documents/conceptualisation/Schéma Ergonomique/Schéma ergonomique.png>) présente les écrans et leurs enchaînements.

Les sept écrans de référence sont conservés dans le dossier [Maquette complète](<documents/conceptualisation/Schéma Ergonomique/Maquette Figma/Maquette complete>) :

1. accueil et recherche ;
2. détail d'une annonce ;
3. inscription ;
4. connexion ;
5. gestion du compte ;
6. création et modification d'une annonce ;
7. tableau de bord.

Les maquettes visent un affichage sur ordinateur, conformément au cahier des charges.

## Tableau de spécifications

Le fichier [Specifications.xlsx](<documents/conceptualisation/Spécifications/Specifications.xlsx>) relie les quatre familles techniques :

- 22 contrôleurs, avec leur rôle, leurs entrées et leur sortie ;
- 12 templates ou fragments, avec les variables nécessaires ;
- 5 modèles, avec leurs responsabilités et données ;
- 3 fichiers JavaScript, avec leurs événements et effets sur l'interface.

## Modèles conceptuel et physique de données

Le [MCD](<documents/conceptualisation/Modèles de données/MCD.png>) présente les entités et associations métier. 
Le [MPD](<documents/conceptualisation/Modèles de données/MPD.png>) traduit cette conception en tables MySQL.

Tables principales :

| Table | Rôle | Données principales |
|---|---|---|
| `utilisateur` | Comptes | pseudo, email, mot de passe haché |
| `annonce` | Objets proposés | titre, description, état, prix, fin, catégorie, vendeur |
| `photo` | Photographies ordonnées | nom, position, annonce |
| `enchere` | Propositions des participants | montant, date, annonce, utilisateur |
| `asso_utilisateur_annonce` | Suivis volontaires | annonce, utilisateur |

La catégorie n'est pas stockée dans une table locale : `annonce.categorie_id` conserve l'identifiant fourni par l'API externe.

## Création de la base de données

Prérequis : PHP 8.3 avec PDO MySQL, cURL et Fileinfo, ainsi qu'un serveur MySQL.

Création de la base sur le serveur d'hébergement :

```sql
CREATE DATABASE qdm-adeline
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

La collation `utf8mb4_unicode_ci` participe à la recherche sans distinction de casse et d'accent. Les cinq tables doivent ensuite être créées conformément au MPD, avec leurs clés étrangères et contraintes.

Configuration d'exemple dans `config.example.php` :

```powershell
Copy-Item config.example.php config.php
```
Compléter ensuite dans `config.php` :

```php
$GLOBALS['bdd_host'] = '';
$GLOBALS['bdd_base'] = '';
$GLOBALS['bdd_user'] = '';
$GLOBALS['bdd_pwd'] = '';
```

IMPORTANT : `config.php` est ignoré par Git afin de ne pas publier les identifiants.

Visualiser le site avec : `https://qdm-adeline.play.mywebecom.ovh`.

## Données de démonstration

Le dossier `public/assets/images/photo-objet/` contient des photographies de démonstration.

Pour une démonstration complète, il a fallu préparer au minimum :

- un compte vendeur ;
- deux comptes acheteurs ;
- une annonce en cours sans enchère, modifiable et supprimable ;
- une annonce en cours avec plusieurs enchères ;
- une vente terminée remportée ;
- une vente terminée sans enchère ;
- un suivi simple sans participation.

Ce jeu permet de démontrer tous les rôles contextuels sans utiliser de données personnelles réelles.

## Organisation MVC et répartition des responsabilités

```text
Navigateur
→ contrôleur : reçoit, contrôle et organise
→ modèle : consulte ou modifie les données
→ contrôleur : prépare des valeurs simples
→ template : échappe et affiche le HTML
```

- `core/init.php` démarre la session et ouvre PDO ;
- `core/_model.php` fournit les opérations communes aux modèles MySQL ;
- `library/` contient les outils transversaux, sans HTML ni SQL métier ;
- `model/` contient le SQL et les règles liées aux données ;
- les contrôleurs racine lisent GET, POST, FILES et la session ;
- `templates/` affiche uniquement les variables préparées ;
- le JavaScript améliore l'interface mais ne remplace jamais les contrôles PHP.

## Un contrôleur = une action

Chaque URL PHP possède un objectif précis :

| Préfixe | Responsabilité | Exemple |
|---|---|---|
| `afficher_` | Préparer une page | `afficher_detail_annonce.php` |
| `enregistrer_` | Traiter une écriture POST | `enregistrer_enchere.php` |
| `lancer_` | Traiter une consultation élaborée | `lancer_recherche.php` |
| `actualiser_` | Produire une réponse JSON | `actualiser_annonces_suivies_2s_AJAX.php` |

Ce choix évite un contrôleur central très long et rend les autorisations propres à chaque action visibles.

## Sécurité et confidentialité

- validation de toutes les entrées côté serveur ;
- paramètres préparés PDO pour toutes les valeurs SQL ;
- listes blanches pour les éléments SQL non paramétrables ;
- `password_hash()` et `password_verify()` pour les mots de passe ;
- renouvellement de l'identifiant de session après connexion ;
- contrôle de connexion et d'autorisation sur chaque ressource ;
- jeton CSRF pour les formulaires qui modifient des données ;
- échappement avec `echapper_html()` dans les templates ;
- `textContent` pour les textes construits en JavaScript ;
- contrôle MIME, taille maximale de 5 Mo et nom généré pour les photographies ;
- transaction et `FOR UPDATE` pour les écritures concurrentes sensibles ;
- courriel et mot de passe jamais affichés publiquement ;
- historique et gagnant visibles uniquement par les rôles autorisés.

## RGPD

Le projet applique la minimisation : seuls le pseudo, l'email et le mot de passe haché sont demandés. Le pseudo est public ; l'email et le hachage restent privés. Les mots de passe en clair ne sont ni stockés, ni journalisés, ni réaffichés.

L'utilisateur peut consulter et modifier ses informations dans son profil. La suppression du compte n'est pas prévue par le cahier des charges actuel et n'est pas implémentée. Avant de l'ajouter, il faut décider si le compte est supprimé ou anonymisé et définir le devenir des annonces et enchères afin de préserver l'intégrité de l'historique.

Le prototype traite des données personnelles fictives.

Principes à appliquer :

- **finalité** : expliquer que les données servent spécifiquement à l'application Web d'enchères entre particuliers QuiDitMieux ;
- **minimisation** : ne demander que les données utiles au service ;
- **transparence** : fournir une notice claire au moment de l'inscription ;
- **sécurité** : hachage, droits d'accès, validation et secrets hors de Git ;
- **durée de conservation** : définir une durée et une règle de purge ;
- **droits** : prévoir accès, rectification, export et effacement ;
- **images** : rappeler à l'utilisateur de publier des photos qu'il peut utiliser ;
- **données de test** : employer des identités fictives et aucun email réel.

Le projet ne possède pas encore de notice de confidentialité, de consentement explicite ni de parcours d'export/suppression. Ces points doivent être présentés comme des limites du prototype, et non comme des fonctions déjà réalisées.

Les données de démonstration sont fictives. Les durées de conservation et la procédure d'exercice des droits devront être précisées avant une mise en production réelle. Un lien clairement identifiable en bas de page sera mis à la disposition de l'utilisateur pour lui présenter des informations de transparence exigées par le Règlement Général sur la Protection des Données (RGPD). Ces informations devront être rédigées par un juriste spécialisé en RGPD.

## Auteur

**Adeline Sivaz** — projet de certification consacré au développement back-end d'une application Web en PHP natif.
Projet pédagogique réalisé pour une soutenance de niveau 5 (Bac+2).
