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
