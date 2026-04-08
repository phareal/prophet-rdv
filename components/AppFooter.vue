<script setup lang="ts">
import { MessageCircle, Phone } from 'lucide-vue-next'
import { buildWhatsAppUrl } from '@/lib/utils'

const config = useRuntimeConfig()
const phone1 = config.public.prophetPhone1 as string
const phone2 = config.public.prophetPhone2 as string

function wa(phone: string) {
  return buildWhatsAppUrl(phone, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.')
}

const links = [
  { label: 'Accueil',               href: '/' },
  { label: 'Types de consultation', href: '#rdv' },
  { label: 'Galerie',               href: '#galerie' },
  { label: 'Témoignages',           href: '#temoignages' },
]
</script>

<template>
  <footer class="footer">
    <div class="footer__inner">
      <div class="footer__cols">

        <!-- Colonne brand -->
        <div class="footer__col">
          <h3 class="footer__brand">Prophète Jeremiah Nahoum</h3>
          <p class="footer__brand-sub">"Le Conseiller des Rois"</p>
          <p class="footer__brand-desc">
            Ministère prophétique international dédié à guider les nations, les leaders
            et les familles selon la parole de Dieu.
          </p>
        </div>

        <!-- Navigation -->
        <div class="footer__col">
          <h4 class="footer__col-title">Navigation</h4>
          <nav>
            <a v-for="l in links" :key="l.label" :href="l.href" class="footer__link">
              {{ l.label }}
            </a>
          </nav>
        </div>

        <!-- Contact -->
        <div class="footer__col">
          <h4 class="footer__col-title">Contact</h4>
          <a :href="wa(phone1)" target="_blank" rel="noopener noreferrer" class="footer__contact">
            <span class="footer__contact-icon footer__contact-icon--wa">
              <MessageCircle :size="13" />
            </span>
            <span>{{ phone1 }}</span>
          </a>
          <a v-if="phone2" :href="wa(phone2)" target="_blank" rel="noopener noreferrer" class="footer__contact">
            <span class="footer__contact-icon footer__contact-icon--ph">
              <Phone :size="13" />
            </span>
            <span>{{ phone2 }}</span>
          </a>
        </div>

      </div>

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
  background: #0C1528;
  color: rgba(232,228,220,0.6);
  border-top: 1px solid rgba(255,255,255,0.05);
}
.footer__inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 3.5rem 1.75rem 2rem;
}

.footer__cols {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 3rem;
  margin-bottom: 3rem;
}
@media (max-width: 700px) {
  .footer__cols { grid-template-columns: 1fr; gap: 2rem; }
}

.footer__brand {
  font-family: var(--f-display);
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: var(--emerald);
  margin-bottom: 0.3rem;
}
.footer__brand-sub {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.85rem;
  color: rgba(200,146,28,0.5);
  margin-bottom: 0.75rem;
}
.footer__brand-desc {
  font-family: var(--f-serif);
  font-size: 0.88rem;
  line-height: 1.6;
  color: rgba(232,228,220,0.28);
}

.footer__col-title {
  font-family: var(--f-mono);
  font-size: 0.6rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  color: rgba(255,255,255,0.28);
  margin-bottom: 1rem;
}

nav { display: flex; flex-direction: column; gap: 0.55rem; }
.footer__link {
  font-family: var(--f-serif);
  font-size: 0.9rem;
  color: rgba(232,228,220,0.32);
  transition: color var(--tf);
}
.footer__link:hover { color: rgba(232,228,220,0.7); }

.footer__contact {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-family: var(--f-mono);
  font-size: 0.78rem;
  color: rgba(232,228,220,0.32);
  margin-bottom: 0.65rem;
  transition: color var(--tf);
}
.footer__contact:hover { color: #25D366; }
.footer__contact-icon {
  width: 26px; height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.footer__contact-icon--wa {
  background: rgba(37,211,102,0.1);
  color: #25D366;
}
.footer__contact-icon--ph {
  background: rgba(255,255,255,0.05);
  color: rgba(255,255,255,0.3);
}

.footer__bottom {
  border-top: 1px solid rgba(255,255,255,0.06);
  padding-top: 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  text-align: center;
}
@media (min-width: 560px) {
  .footer__bottom { flex-direction: row; justify-content: space-between; text-align: left; }
}
.footer__copy {
  font-family: var(--f-mono);
  font-size: 0.62rem;
  color: rgba(255,255,255,0.18);
}
.footer__love {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.75rem;
  color: rgba(255,255,255,0.12);
}
</style>
