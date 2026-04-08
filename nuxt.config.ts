// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-04-03',
  devtools: { enabled: true },

  modules: ['@nuxtjs/tailwindcss'],

  typescript: {
    strict: true,
  },

  css: ['~/assets/css/main.css'],

  runtimeConfig: {
    // Serveur uniquement
    mongodbUri: process.env.MONGODB_URI || 'mongodb://localhost:27017/prophetrdv',
    smtpHost: process.env.SMTP_HOST || 'smtp.gmail.com',
    smtpPort: parseInt(process.env.SMTP_PORT || '587'),
    smtpSecure: process.env.SMTP_SECURE === 'true',
    smtpUser: process.env.SMTP_USER || '',
    smtpPass: process.env.SMTP_PASS || '',
    prophetEmail: process.env.PROPHET_EMAIL || '',
    prophetPhone1: process.env.PROPHET_PHONE_1 || '+22897169090',
    prophetPhone2: process.env.PROPHET_PHONE_2 || '+2348119265483',
    // Public (exposé côté client)
    public: {
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000',
      prophetPhone1: process.env.PROPHET_PHONE_1 || '+22897169090',
      prophetPhone2: process.env.PROPHET_PHONE_2 || '+2348119265483',
    },
  },

  nitro: {
    experimental: {
      wasm: true,
    },
  },

  vite: {
    server: {
      watch: {
        usePolling: true,
        interval: 300,
      },
    },
  },

  app: {
    head: {
      title: 'Prophète Jeremiah Nahoum — Le Conseiller des Rois',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        {
          name: 'description',
          content:
            'Prenez rendez-vous avec le Prophète Jeremiah Nahoum, Le Conseiller des Rois. Consultation spirituelle, guidée et prophétique.',
        },
        { property: 'og:title', content: 'Prophète Jeremiah Nahoum — Le Conseiller des Rois' },
        {
          property: 'og:description',
          content: 'Prenez rendez-vous avec le Prophète Jeremiah Nahoum.',
        },
      ],
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;900&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&family=JetBrains+Mono:wght@400;500&display=swap',
        },
      ],
    },
  },
})
