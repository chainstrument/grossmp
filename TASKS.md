# Tâches et backlog (EPICs)

## EPIC 1 — Socle technique du projet

Mettre en place l'environnement de dev avant toute feature métier.

- [x] #1 Initialiser le projet Symfony (skeleton, composer.json, structure `src/`)
- [x] #2 Écrire le `docker-compose.yml` (services : php, nginx/caddy, postgres, mailhog) — via `compose.yaml`/`compose.override.yaml` (convention Symfony Flex), service mail = Mailpit
- [x] #3 Configurer la connexion Doctrine vers PostgreSQL (`.env`, `doctrine.yaml`)
- [x] #4 Mettre en place les migrations Doctrine (première migration vide, vérifier le flux)
- [x] #5 Configurer PHPUnit + première suite de tests (smoke test)
- [x] #6 Mettre en place un linter/formatter (PHP-CS-Fixer ou PHPStan)
- [x] #7 Écrire le README d'installation (setup Docker, premières commandes)

---

## EPIC 2 — Comptes clients et authentification

Gérer les sociétés clientes, leurs utilisateurs, et les rôles.

- [ ] #8 Entity `Company` (société cliente) : nom, SIRET, adresse de facturation/livraison
- [ ] #9 Entity `User` avec relation vers `Company`, rôles (`ROLE_BUYER`, `ROLE_VALIDATOR`, `ROLE_COMPANY_ADMIN`)
- [ ] #10 Configurer `security.yaml` (firewall, provider, hashing des mots de passe)
- [ ] #11 Formulaire + page de connexion (login classique)
- [ ] #12 Commande CLI pour créer un premier utilisateur admin (`app:user:create`)
- [ ] #13 Page "mon compte" (édition des infos utilisateur)

---

## EPIC 3 — Catalogue produits et tarification

Gérer les produits et les grilles tarifaires par client.

- [ ] #14 Entity `Product` (référence, désignation, prix de base, stock)
- [ ] #15 Entity `PriceTier` (tarif dégressif par quantité et/ou par `Company`)
- [ ] #16 Repository custom : méthode pour calculer le prix effectif d'un produit pour un client donné
- [ ] #17 Page catalogue (liste produits, recherche, filtre par catégorie)
- [ ] #18 Fixtures de test (produits, tarifs) via `DataFixtures`

---

## EPIC 4 — Panier et création de commande

Le cœur du parcours acheteur.

- [ ] #19 Entity `Order` + `OrderLine` (statut initial : `draft`)
- [ ] #20 Service `CartManager` : ajout/suppression de lignes, recalcul du total
- [ ] #21 `Form/Type` pour la validation de commande (quantités mini, contrôle de stock)
- [ ] #22 `Validator` custom : contrainte "stock suffisant" sur une `OrderLine`
- [ ] #23 Page panier + page récapitulatif avant validation

---

## EPIC 5 — Workflow de commande (statuts)

Gérer le cycle de vie d'une commande avec le composant `Workflow`.

- [ ] #24 Définir la state machine dans `config/packages/workflow.yaml` (draft → submitted → validated → preparing → shipped → invoiced), + statut `cancelled`
- [ ] #25 Implémenter les *guards* de transition (ex : impossible de passer à `preparing` si stock insuffisant)
- [ ] #26 Contrôleur/actions pour déclencher les transitions (boutons "Valider", "Expédier"...)
- [ ] #27 `EventSubscriber` sur les événements du Workflow (`workflow.order.transition.shipped` etc.)
- [ ] #28 Page de suivi de commande affichant l'historique des transitions

---

## EPIC 6 — Autorisations fines (Voters)

S'assurer qu'un utilisateur n'accède qu'aux données de sa société.

- [ ] #29 `Voter` sur `Order` : un `ROLE_BUYER` ne voit que les commandes de sa `Company`
- [ ] #30 `Voter` : seul un `ROLE_VALIDATOR` peut faire passer une commande de `draft` à `submitted` → `validated`
- [ ] #31 Tests unitaires sur les Voters (cas autorisé / refusé)

---

## EPIC 7 — Notifications par email

- [ ] #32 Configurer Symfony Mailer (SMTP via Mailhog en dev)
- [ ] #33 Template d'email de confirmation de commande
- [ ] #34 `EventSubscriber` : envoi automatique d'email à chaque changement de statut de commande
- [ ] #35 Tester l'envoi en dev via Mailhog (vérifier réception dans l'UI Mailhog)

---

## EPIC 8 — Traitement asynchrone (Messenger)

Génération de facture PDF sans bloquer la requête HTTP.

- [ ] #36 Configurer Symfony Messenger (transport Doctrine pour commencer, migration Redis possible ensuite)
- [ ] #37 `Message` `GenerateInvoiceMessage` + `MessageHandler` associé
- [ ] #38 Génération du PDF de facture (lib type `dompdf` ou `mpdf`)
- [ ] #39 Déclenchement du message à la transition `invoiced` du Workflow
- [ ] #40 Commande CLI pour consommer la queue (`messenger:consume`) + doc sur le lancement en dev

---

## EPIC 9 — API REST

Exposer les données pour un futur client externe (mobile/SPA), sans exposer les Entities brutes.

- [ ] #41 `DTO` `OrderDto`, `ProductDto` (séparation stricte Entity / donnée exposée)
- [ ] #42 `Normalizer` custom pour transformer Entity → DTO
- [ ] #43 `Controller/Api` : endpoints GET liste produits, GET détail commande
- [ ] #44 `Controller/Api` : endpoint POST création de commande (validation via DTO)
- [ ] #45 Authentification API JWT (LexikJWTAuthenticationBundle) : login émettant un token, guard sur les routes API
- [ ] #45b Gestion du refresh token (ou expiration courte + re-login côté front)
- [ ] #46 Documentation API (NelmioApiDocBundle ou fichier OpenAPI manuel)

---

## EPIC 10 — Import de stock (CLI/Cron)

- [ ] #47 `Command` `app:stock:import` : lecture d'un CSV, mise à jour des stocks produits
- [ ] #48 Gestion des erreurs d'import (lignes invalides, log dans `var/log`)
- [ ] #49 Documentation de la commande + exemple de CSV de test

---

## EPIC 11 — Reporting

- [ ] #50 Requête DQL/QueryBuilder : chiffre d'affaires par client sur une période
- [ ] #51 Requête DQL/QueryBuilder : top produits vendus
- [ ] #52 Page reporting simple (tableau, éventuellement un export CSV)

---

## EPIC 12 — Tests et qualité

- [ ] #53 Tests fonctionnels sur le parcours complet (création commande → workflow → facturation)
- [ ] #54 Tests unitaires sur `CartManager` et le calcul de prix dégressif
- [ ] #55 Mettre en place une CI (GitHub Actions) : lint + tests à chaque push

---

## EPIC 13 — Front React (espace client)

À démarrer une fois l'API (EPIC 9) fonctionnelle. Objectif : un espace self-service pour les clients, entièrement découplé du back Twig.

- [ ] #56 Initialiser le projet React dans `front/` (Vite, structure de dossiers, config ESLint/Prettier)
- [ ] #57 Ajouter `front/` au `docker-compose.yml` (service Node dédié, port distinct du back Symfony)
- [ ] #58 Client HTTP (fetch/axios) centralisé + gestion du token JWT (stockage, injection dans les headers)
- [ ] #59 Page de login React (appel à l'endpoint JWT, redirection si déjà connecté)
- [ ] #60 Gestion de l'expiration du token (redirection login si 401, ou refresh automatique selon #45b)
- [ ] #61 Page catalogue produits (consomme `GET /api/products`, filtre/recherche côté front)
- [ ] #62 Panier côté React (état local, ajout/suppression de lignes, calcul du total en s'appuyant sur les prix renvoyés par l'API)
- [ ] #63 Page de validation de commande (appel `POST /api/orders`, gestion des erreurs de validation renvoyées par l'API)
- [ ] #64 Page "mes commandes" + suivi de statut (consomme `GET /api/orders`, affiche le statut du Workflow)
- [ ] #65 Gestion des rôles côté front (un `ROLE_VALIDATOR` voit un bouton "Valider" que le `ROLE_BUYER` ne voit pas)
- [ ] #66 Build de prod du front (script npm) + intégration dans le pipeline CI

---

## EPIC 14 — Déploiement

Deux approches possibles, non exclusives (voir recommandation ci-dessous).

### Option A — PaaS simple (Scalingo / Railway / Render)

Recommandé pour rester concentré sur Symfony/React plutôt que sur l'infra.

- [ ] #67 Créer l'app sur le PaaS choisi + provisionner l'addon PostgreSQL
- [ ] #68 Configurer les variables d'environnement de prod (`.env` prod, secrets)
- [ ] #69 Adapter le déploiement pour lancer les migrations Doctrine automatiquement au déploiement
- [ ] #70 Configurer le worker Messenger en process séparé (consommation de la queue en continu)
- [ ] #71 Build + déploiement du front React (build statique servi séparément ou via le même PaaS)
- [ ] #72 Configurer un nom de domaine + HTTPS (généralement automatique sur ces PaaS)

### Option B — AWS (à envisager après l'option A, ou pour une brique isolée)

- [ ] #73 RDS PostgreSQL (instance managée, à la place de la BDD Docker locale)
- [ ] #74 Déploiement du back Symfony sur Elastic Beanstalk (plus simple) ou ECS/Fargate (plus proche d'une vraie prod)
- [ ] #75 S3 pour le stockage des factures PDF générées (remplace le stockage local `var/`)
- [ ] #76 SQS comme transport Messenger (remplace le transport Doctrine)
- [ ] #77 CloudFront + S3 pour héberger le build statique du front React
- [ ] #78 IAM : créer un utilisateur dédié avec permissions minimales (pas la racine du compte)
- [ ] #79 Mettre en place des alertes de facturation (budget AWS) pour éviter les mauvaises surprises

**Recommandation** : démarre avec l'option A pour livrer un projet fonctionnel sans te disperser sur l'infra. Si tu veux valoriser AWS sur ton CV, migre ensuite une seule brique isolée (typiquement #75, S3 pour les factures) plutôt que tout l'archi d'un coup.

---

## Ordre de développement suggéré

1. EPIC 1 (socle)
2. EPIC 2 (comptes)
3. EPIC 3 (catalogue)
4. EPIC 4 (panier/commande)
5. EPIC 5 (workflow) — cœur du projet
6. EPIC 6 (Voters) — en parallèle ou juste après EPIC 5
7. EPIC 7 (emails)
8. EPIC 8 (Messenger/PDF)
9. EPIC 9 (API) — prérequis obligatoire avant l'EPIC 13
10. EPIC 10 (import CLI)
11. EPIC 11 (reporting)
12. EPIC 12 (tests/CI) — en continu, pas seulement à la fin
13. EPIC 13 (front React) — une fois l'API stable
14. EPIC 14 (déploiement) — option A (PaaS) en priorité, option B (AWS) en bonus si tu veux le valoriser sur ton CV
