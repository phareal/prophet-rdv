# Audit des collisions CSS — `<style scoped>` des composants Vue

Commande exécutée depuis la racine du dépôt (Nuxt) :

```bash
node scripts/audit-css-classes.mjs | tee /tmp/audit-css.txt
```

## Sortie brute du script

```
48 composants analysés, 407 classes distinctes.
13 classes utilisées par plusieurs composants :

.hero (2)
    components/HeroLeft.vue
    components/HeroSection.vue
.hero__badge (2)
    components/HeroLeft.vue
    components/HeroSection.vue
.hero__title (2)
    components/HeroLeft.vue
    components/HeroSection.vue
.hero__title-name (2)
    components/HeroLeft.vue
    components/HeroSection.vue
.hero__subtitle (2)
    components/HeroLeft.vue
    components/HeroSection.vue
.hero__portrait (2)
    components/HeroLeft.vue
    components/HeroSection.vue
.hero__verse (2)
    components/HeroLeft.vue
    components/HeroSection.vue
.tcard (2)
    components/TestimonialCard.vue
    components/TestimonialsSection.vue
.tcard__text (2)
    components/TestimonialCard.vue
    components/TestimonialsSection.vue
.tcard__footer (2)
    components/TestimonialCard.vue
    components/TestimonialsSection.vue
.tcard__avatar (2)
    components/TestimonialCard.vue
    components/TestimonialsSection.vue
.tcard__meta (2)
    components/TestimonialCard.vue
    components/TestimonialsSection.vue
.tcard__name (2)
    components/TestimonialCard.vue
    components/TestimonialsSection.vue
```

13 classes en collision sur 407 classes distinctes relevées dans 48 composants — deux paires de composants concernées.

## Analyse composant par composant

### Paire 1 — `HeroLeft.vue` / `HeroSection.vue` (7 classes : `.hero`, `.hero__badge`, `.hero__title`, `.hero__title-name`, `.hero__subtitle`, `.hero__portrait`, `.hero__verse`)

- `HeroSection.vue` est monté uniquement dans `pages/index.vue` (`<HeroSection />`, ligne 18).
- `HeroLeft.vue` est monté uniquement dans `pages/rdv.vue` (`import HeroLeft from '@/components/HeroLeft.vue'`, `<HeroLeft />`, ligne 23).
- Vérifié par recherche : aucun des deux fichiers ne référence l'autre (ni `<HeroLeft` dans `HeroSection.vue`, ni `<HeroSection` dans `HeroLeft.vue`).
- Ce sont deux variantes de héros totalement indépendantes, rendues sur deux pages différentes, qui partagent par coïncidence la même convention de nommage BEM (`.hero__*`). Chacune définit sa propre balise et son propre style pour ces classes — aucune ne stylise un élément rendu par l'autre.
- **Verdict : collision ordinaire, sans danger.**

### Paire 2 — `TestimonialCard.vue` / `TestimonialsSection.vue` (6 classes : `.tcard`, `.tcard__text`, `.tcard__footer`, `.tcard__avatar`, `.tcard__meta`, `.tcard__name`)

- `TestimonialsSection.vue` ne rend **pas** le composant `<TestimonialCard>` : elle définit son propre balisage `<article class="tcard">` inline (lignes 35-53) et son propre bloc `<style scoped>` pour `.tcard*` (lignes 129-205).
- `TestimonialCard.vue` n'est importé ni rendu nulle part dans le dépôt (recherche `grep -rn "TestimonialCard"` sur `*.vue/*.ts/*.js` : aucune occurrence hors de son propre fichier, ni en tag `<TestimonialCard`). C'est un composant orphelin, mort, jamais utilisé par l'application Nuxt.
- Comme `TestimonialCard.vue` n'est jamais monté, il ne peut pas y avoir de conflit d'application de règles au runtime ; la collision de noms de classes n'a aucun effet observable aujourd'hui. À noter pour les tâches 14-20 : ce fichier n'a probablement pas besoin d'être porté puisqu'il n'est utilisé par aucune page.
- **Verdict : collision ordinaire (composant mort), sans danger.**

## Conclusion

**Aucune collision dangereuse trouvée.** Le cas redouté — une règle d'un composant stylant un élément rendu par un *autre* composant, qui ne pourrait pas survivre à une copie dans un fichier `.sec-<nom>` scopé — n'apparaît dans aucune des deux paires. Les 13 classes en collision relèvent toutes de la coïncidence de nommage entre composants indépendants (jamais imbriqués), dont un est même du code mort.

**La copie simple des styles scoped vers les fichiers `.sec-<nom>` des tâches 14 à 20 est sûre.** Le scoping par section neutralise ces collisions ordinaires exactement comme prévu ; aucun traitement spécial n'est requis.
