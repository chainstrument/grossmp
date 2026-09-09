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

