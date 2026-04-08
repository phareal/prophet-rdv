<script setup lang="ts">
import { MessageCircle, Phone } from 'lucide-vue-next'
import { buildWhatsAppUrl } from '@/lib/utils'

const config = useRuntimeConfig()
const phone1 = config.public.prophetPhone1 as string
const phone2 = config.public.prophetPhone2 as string

function wa(phone: string) {
  return buildWhatsAppUrl(phone, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.')
}

const navLinks = [
  { label: 'Accueil', href: '/' },
  { label: 'À propos', href: '/#apropos' },
  { label: 'Galerie', href: '/#galerie' },
  { label: 'Témoignages', href: '/#temoignages' },
]

const serviceLinks = [
  'Mariage', 'Affaires', 'Carrière politique', 'Santé',
  'Voyage', 'Appel prophétique',
]
</script>

<template>
  <footer class="footer">
    <div class="footer__inner">
      <div class="footer__cols">

        <!-- Brand -->
        <div class="footer__col footer__col--brand">
          <div class="footer__logo">
            <span class="footer__logo-cross">✦</span>
            <div>
              <p class="footer__brand-name">Prophète Jeremiah Nahoum</p>
              <p class="footer__brand-sub">"Le Conseiller des Rois"</p>
            </div>
          </div>
          <p class="footer__brand-desc">
            Ministère prophétique international dédié à guider les nations, les dirigeants
            et les familles selon la volonté de Dieu depuis plus de 15 ans.
          </p>
          <div class="footer__contacts">
            <a :href="wa(phone1)" target="_blank" rel="noopener noreferrer" class="footer__contact">
              <span class="footer__ci footer__ci--wa"><MessageCircle :size="13" /></span>
              <span>{{ phone1 }}</span>
            </a>
            <a v-if="phone2" :href="wa(phone2)" target="_blank" rel="noopener noreferrer" class="footer__contact">
              <span class="footer__ci footer__ci--ph"><Phone :size="13" /></span>
              <span>{{ phone2 }}</span>
            </a>
          </div>
        </div>

        <!-- Navigation -->
        <div class="footer__col">
          <h4 class="footer__col-title">Navigation</h4>
          <nav class="footer__nav">
            <a v-for="l in navLinks" :key="l.label" :href="l.href" class="footer__link">{{ l.label }}</a>
            <a href="/rdv" class="footer__link">Prendre RDV</a>
          </nav>
        </div>

        <!-- Services -->
        <div class="footer__col">
          <h4 class="footer__col-title">Services</h4>
          <nav class="footer__nav">
            <a v-for="s in serviceLinks" :key="s" href="/rdv" class="footer__link">{{ s }}</a>
          </nav>
        </div>

        <!-- RDV -->
        <div class="footer__col">
          <h4 class="footer__col-title">Rendez-vous</h4>
          <p class="footer__rdv-desc">
            Consultations disponibles 7j/7 pour toutes les nations.
          </p>
          <a href="/rdv" class="footer__rdv-btn">Réserver maintenant →</a>
          <blockquote class="footer__verse">
            <p>"Appelez-moi et je vous répondrai."</p>
            <cite>— Jérémie 33:3</cite>
          </blockquote>
        </div>

      </div>

      <!-- Bottom bar -->
      <div class="footer__bottom">
        <p class="footer__copy">
          © {{ new Date().getFullYear() }} Prophète Jeremiah Nahoum · Tous droits réservés
        </p>
        <p class="footer__love">Conçu pour le Royaume de Dieu</p>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  background: #070C1A;
  color: rgba(232, 228, 220, 0.5);
  border-top: 1px solid rgba(200, 146, 28, 0.1);
}

.footer__inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 4.5rem 2rem 2rem;
}

.footer__cols {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.5fr;
  gap: 3rem;
  margin-bottom: 3.5rem;
}

/* Brand */
.footer__logo {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  margin-bottom: 1rem;
}

.footer__logo-cross {
  font-size: 1rem;
  color: rgba(200, 146, 28, 0.6);
  line-height: 1.4;
}

.footer__brand-name {
  font-family: var(--f-display);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: rgba(244, 240, 235, 0.85);
  line-height: 1.2;
}

.footer__brand-sub {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.72rem;
  color: rgba(200, 146, 28, 0.5);
  margin-top: 0.1rem;
}

.footer__brand-desc {
  font-family: var(--f-serif);
  font-size: 0.85rem;
  line-height: 1.65;
  color: rgba(232, 228, 220, 0.28);
  margin-bottom: 1.25rem;
}

.footer__contacts {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.footer__contact {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  font-family: var(--f-mono);
  font-size: 0.72rem;
  color: rgba(232, 228, 220, 0.32);
  text-decoration: none;
  transition: color var(--t);
}

.footer__contact:hover { color: #25D366; }

.footer__ci {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.footer__ci--wa { background: rgba(37, 211, 102, 0.1); color: #25D366; }
.footer__ci--ph { background: rgba(255, 255, 255, 0.05); color: rgba(255, 255, 255, 0.3); }

/* Col title */
.footer__col-title {
  font-family: var(--f-mono);
  font-size: 0.58rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  color: rgba(255, 255, 255, 0.25);
  margin-bottom: 1.1rem;
}

/* Nav */
.footer__nav {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.footer__link {
  font-family: var(--f-serif);
  font-size: 0.88rem;
  color: rgba(232, 228, 220, 0.3);
  text-decoration: none;
  transition: color var(--t);
}

.footer__link:hover { color: rgba(232, 228, 220, 0.7); }

/* RDV col */
.footer__rdv-desc {
  font-family: var(--f-serif);
  font-size: 0.85rem;
  line-height: 1.6;
  color: rgba(232, 228, 220, 0.3);
  margin-bottom: 1rem;
}

.footer__rdv-btn {
  display: inline-block;
  font-family: var(--f-display);
  font-size: 0.65rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--gold);
  text-decoration: none;
  padding-bottom: 0.25rem;
  border-bottom: 1px solid rgba(200, 146, 28, 0.25);
  transition: color var(--t), border-color var(--t);
  margin-bottom: 1.5rem;
}

.footer__rdv-btn:hover {
  color: #F0C040;
  border-color: rgba(240, 192, 64, 0.5);
}

.footer__verse {
  padding-left: 0.75rem;
  border-left: 1px solid rgba(200, 146, 28, 0.18);
}

.footer__verse p {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.78rem;
  line-height: 1.5;
  color: rgba(232, 228, 220, 0.22);
  margin-bottom: 0.3rem;
}

.footer__verse cite {
  font-family: var(--f-mono);
  font-size: 0.55rem;
  color: rgba(200, 146, 28, 0.3);
  font-style: normal;
}

/* Bottom */
.footer__bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding-top: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.footer__copy {
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.06em;
  color: rgba(255, 255, 255, 0.16);
}

.footer__love {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.1);
}

/* Responsive */
@media (max-width: 1024px) {
  .footer__cols { grid-template-columns: 1fr 1fr; }
  .footer__col--brand { grid-column: span 2; }
}

@media (max-width: 640px) {
  .footer__cols { grid-template-columns: 1fr; }
  .footer__col--brand { grid-column: span 1; }
  .footer__bottom { flex-direction: column; text-align: center; }
  .footer__inner { padding: 3.5rem 1.5rem 2rem; }
}
</style>
