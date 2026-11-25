# Stockly

> Application web de gestion commerciale pour boutique généraliste : produits, stocks, ventes, fournisseurs, utilisateurs et tableau de bord.

Projet transversal, EPSI Bachelor 2 (Licence 2 Informatique). Équipe : un développeur (application, base de données, tests) et un référent réseau (VM Linux, HTTPS, sauvegarde, journaux).


## Objectifs

- Centraliser la gestion des produits, des stocks, des ventes et des fournisseurs.
- Éviter les ruptures de stock et les surstockages.
- Suivre les performances commerciales et les marges.
- Gérer les utilisateurs selon leur rôle.

Le MVP reste volontairement limité pour être livrable en quatre semaines. Les fonctionnalités d'intelligence artificielle (prévisions, recommandations, assistant) sont traitées en extension.

## Périmètre

- Une seule boutique. La gestion multi-boutiques est exclue.
- Produits physiques uniquement.
- Pas de retours ni de remboursements dans cette version.
- Application responsive pour ordinateur, tablette et mobile (PWA).

## Acteurs et rôles

Trois rôles hiérarchisés : Administrateur > Responsable > Vendeur. Les permissions sont contrôlées côté serveur.

| Acteur | Responsabilités | Permissions |
|---|---|---|
| Administrateur | Comptes, rôles, paramètres | Toutes, dont `users.manage` |
| Responsable | Produits, stocks, approvisionnements, statistiques | Toutes sauf `users.manage` |
| Vendeur | Enregistrer des ventes, consulter ses propres ventes | `sales.create`, `sales.view` (limité à ses ventes) |

## Fonctionnalités

Priorités : **M** obligatoire, **S** souhaitable, **C** bonus.

| ID | Fonctionnalité | Rôle | Priorité |
|---|---|---|---|
| F-01 | Se connecter et se déconnecter | Tous | M |
| F-02 | Créer, modifier, activer et désactiver un compte | Admin | M |
| F-03 | Associer un rôle et des permissions à un compte | Admin | M |
| F-04 | Gérer les catégories | Resp. | M |
| F-05 | Créer, modifier, désactiver et rechercher un produit | Resp. | M |
| F-06 | Gérer les variantes avec stock distinct | Resp. | M |
| F-07 | Saisir référence unique, prix d'achat, prix de vente, seuil minimal | Resp. | M |
| F-08 | Consulter le stock et l'historique des mouvements | Resp. | M |
| F-09 | Ajuster le stock avec un motif | Resp. | M |
| F-10 | Alertes de rupture et de stock faible | Resp. | M |
| F-11 | Créer une vente avec plusieurs lignes | Vendeur | M |
| F-12 | Valider une vente : contrôle du stock, paiement, déduction atomique | Vendeur | M |
| F-13 | Consulter et rechercher l'historique des ventes | Vendeur, Resp. | M |
| F-14 | Gérer les fournisseurs | Resp. | M |
| F-15 | Créer une commande fournisseur et suivre ses statuts | Resp. | M |
| F-16 | Enregistrer une réception partielle ou totale | Resp. | M |
| F-17 | Annuler une commande | Resp. | M |
| F-18 | Tableau de bord : chiffre d'affaires jour et mois, nombre de ventes, panier moyen | Resp. | M |
| F-19 | Produits les plus et moins vendus, ruptures, surstocks | Resp. | M |
| F-20 | Marge estimée | Resp. | S |
| F-21 | Comparer deux périodes | Resp. | C |
| F-22 | Exporter un rapport commercial simple (CSV) | Resp. | C |
| F-23 | Journal : connexions, ventes, réceptions, erreurs | Système | M |
| F-24 | Application installable sur mobile (PWA) | Tous | S |
| F-25 | Prévision des ventes, étiquetée ESTIMATION | Resp. | C |
| F-26 | Détecter les produits à faible rotation | Resp. | C |
| F-27 | Recommander une quantité de réapprovisionnement | Resp. | C |
| F-28 | Assistant conversationnel (intentions simples, requêtes prédéfinies) | Resp. | M |

## Règles métier

| ID | Règle | Fonctionnalités |
|---|---|---|
| RM-01 | La référence d'un produit est unique ; un prix ne peut pas être négatif | F-05, F-07 |
| RM-02 | Une vente et la déduction du stock forment une seule transaction : tout ou rien (`BEGIN`, verrou `FOR UPDATE`, `COMMIT` ou `ROLLBACK`). Le stock demandé est cumulé par variante | F-12 |
| RM-03 | Le stock ne peut jamais devenir négatif | F-09, F-12 |
| RM-04 | Une commande fournisseur non réceptionnée ne modifie pas le stock | F-15 |
| RM-05 | Seules les quantités reçues augmentent le stock ; la quantité reçue ne dépasse pas la quantité commandée | F-16 |
| RM-06 | Le prix de vente, le coût et le taux de taxe sont figés sur chaque ligne de vente | F-12, F-20 |
| RM-07 | Un vendeur ne voit que ses propres ventes | F-13 |
| RM-08 | Une vente applique un seul taux de taxe | F-11, F-12 |
| RM-09 | Seul l'administrateur crée les comptes ; un compte se désactive, il ne se supprime pas | F-02 |
| RM-10 | Pas de retours ni de remboursements dans cette version | F-13 |
| RM-11 | Un produit ayant un historique se désactive, il ne se supprime pas | F-05 |
| RM-12 | Les permissions sont vérifiées côté serveur à chaque opération sensible | Toutes |
| RM-13 | Une prévision est toujours étiquetée ESTIMATION | F-25 |
| RM-14 | Une recommandation ne déclenche jamais un achat ; le responsable valide | F-27 |
| RM-15 | La marge commerciale n'est pas le bénéfice net | F-20 |
| RM-16 | Connexions, ventes, réceptions et erreurs sont journalisées, sans mot de passe | F-23 |

Le paiement est enregistré dans la table `paiements(id, vente_id, mode, montant)`, dans la même transaction que la vente (RM-02).

## Architecture et technologies

Architecture en trois couches : navigateur, backend PHP, base MySQL.

| Couche | Technologies |
|---|---|
| Interface | HTML, CSS, Bootstrap, JavaScript |
| Backend | PHP (PDO, requêtes préparées, sessions) |
| Données | MySQL / MariaDB |
| Développement | XAMPP, Git |
| Hébergement | VM Linux, Apache ou Nginx, HTTPS |

Python et Flask (API d'analyse sur `127.0.0.1:5000`) sont une extension pour F-25 à F-27. Ils ne sont pas dans le MVP.

Exigences non fonctionnelles :

- **Sécurité** : mots de passe hachés, sessions `httponly` et `SameSite`, validation côté serveur.
- **Secrets** : identifiants hors Git, dans `php/config.php` (modèle fourni : `php/config.example.php`).
- **Intégrité** : transactions SQL, contraintes `CHECK` et clés étrangères, index sur `ventes(date_vente)` et `lignes_vente(variante_id)`.
- **Performance** : réponse sous 2 secondes pour les opérations courantes (à mesurer).
- **Journaux** : journal applicatif et journaux du serveur web.

## Installation

> À compléter une fois le déploiement sur la VM validé.

```bash
git clone <url-du-depot> stockly
cd stockly
cp php/config.example.php php/config.php   # puis renseigner les identifiants
```

Importer ensuite `sql/schema.sql` dans MySQL, avec un utilisateur dédié aux droits minimaux.

## Tests

Le cahier de recette compte 28 tests. Chacun est joué sur un jeu de données fictives (une dizaine de produits, trois comptes, quelques ventes et une commande).

<details>
<summary>Voir les 28 tests</summary>

| ID | Exig. | Étant donné | Quand | Alors |
|---|---|---|---|---|
| T-01 | F-01 | Un compte actif avec le bon mot de passe | Il se connecte | Il arrive sur son écran d'accueil selon son rôle |
| T-02 | F-01, F-23 | Un compte actif | Il saisit un mauvais mot de passe | Connexion refusée, message générique, échec journalisé |
| T-03 | F-02, RM-09 | Un compte désactivé | Il tente de se connecter | Connexion refusée |
| T-04 | F-02, F-03 | Un administrateur connecté | Il crée un compte Vendeur | Le vendeur n'accède qu'à la vente et à ses ventes |
| T-05 | F-05, RM-01 | Un produit REF-001 existe | On crée un autre produit REF-001 | Création refusée |
| T-06 | F-07, RM-01 | Le formulaire produit | On saisit un prix négatif | Enregistrement refusé |
| T-07 | F-05 | Un produit vient d'être créé | On le recherche par son nom | Il apparaît dans le catalogue |
| T-08 | F-06 | Un produit avec deux variantes de stock 5 et 5 | On vend 2 unités de la première | Stock 3 et 5 |
| T-09 | F-12, RM-02 | Une variante au stock de 10 | Un vendeur valide une vente de 3 | Vente et paiement créés, stock à 7, mouvement enregistré |
| T-10 | F-12, RM-03 | Une variante au stock de 2 | On valide une vente de 3 | Validation refusée, stock inchangé |
| T-11 | F-12, RM-02 | Une variante au stock de 3 | Deux lignes de 2 pour la même variante | Validation refusée (cumul 4 > 3), stock inchangé |
| T-12 | F-12, RM-02 | Une vente valide | Le paiement échoue (erreur simulée) | ROLLBACK complet, stock inchangé |
| T-13 | F-12, RM-06 | Une vente au prix de 10 € | Le prix du produit passe à 12 € | L'historique affiche toujours 10 € |
| T-14 | F-09, RM-03 | Une variante au stock de 4 | On ajuste de -6 avec un motif | Ajustement refusé |
| T-15 | F-15, RM-04 | Un stock de 10 | On crée une commande de 10 unités | Stock toujours à 10, statut Commandée |
| T-16 | F-16, RM-05 | Une commande de 10 unités | On réceptionne 4 | Stock +4, statut Partiellement reçue, reste 6 |
| T-17 | F-16, RM-05 | Une commande de 10 unités | On saisit une réception de 11 | Réception refusée |
| T-18 | F-16 | Une commande partiellement reçue (reste 6) | On réceptionne 6 | Statut Réceptionnée, stock +6 |
| T-19 | F-13, RM-07 | Deux vendeurs ont chacun des ventes | Le vendeur A ouvre l'historique | Il ne voit que ses ventes |
| T-20 | RM-12 | Un vendeur connecté | Il ouvre la gestion des produits ou des comptes | Accès refusé (403) |
| T-21 | F-18 | Un jeu de ventes connu | Le responsable ouvre le tableau de bord | Indicateurs égaux aux valeurs calculées à la main |
| T-22 | F-23, RM-16 | Une connexion, une vente et une réception | On lit le journal | Les trois événements figurent, sans mot de passe |
| T-23 | F-10 | Un stock au seuil ou en dessous | Le responsable ouvre les alertes | La variante est signalée |
| T-24 | F-24 | L'application servie en HTTPS | On l'ouvre sur un téléphone | L'installation sur l'écran d'accueil est proposée |
| T-25 | F-25, RM-13 | Un historique de ventes suffisant | On affiche une prévision | Elle porte l'étiquette ESTIMATION |
| T-26 | F-28 | L'assistant disponible | Question sans données associées | Il indique sa limite au lieu d'inventer |
| T-27 | Infra | La VM en ligne | On ouvre l'adresse en HTTP | Redirection vers HTTPS, certificat valide |
| T-28 | Infra | Une sauvegarde existe | On la restaure sur une base vide | Les données sont retrouvées à l'identique |

</details>

## Traçabilité

Chaque fonctionnalité est reliée à ses règles, ses tests, son code et son infrastructure.

| Exig. | Règles | Tests | Code | Infra |
|---|---|---|---|---|
| F-01 | RM-12, RM-16 | T-01, T-02 | `bootstrap.php` (login), page de connexion | HTTPS (T-27) |
| F-02 | RM-09, RM-12 | T-03, T-04 | Module comptes, table `utilisateurs` | Utilisateur MySQL dédié |
| F-03 | RM-12 | T-04, T-20 | `require_perm()`, tables `roles` et `permissions` | — |
| F-12 | RM-02, RM-03, RM-06, RM-08 | T-09 à T-13 | `sales_create.php`, tables `ventes`, `lignes_vente`, `paiements` | — |
| F-16 | RM-05 | T-16, T-17, T-18 | `purchase_receive.php`, table `receptions` | — |
| F-23 | RM-16 | T-02, T-22 | Fonction de journal | Journaux du serveur web |
| F-24 | — | T-24 | `manifest` et service worker | HTTPS |
| Infra | — | T-27, T-28 | — | VM, certificat, sauvegarde |

Les fonctionnalités F-04, F-14, F-17, F-19 et F-20 n'ont pas encore de test. Elles seront ajoutées au cahier de recette en S2.
