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

