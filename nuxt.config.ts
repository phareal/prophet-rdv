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
    apiBridgeUrl: process.env.API_BRIDGE_URL || '',
    youtubeApiKey: process.env.YOUTUBE_API_KEY || '',
    youtubeChannelId: process.env.YOUTUBE_CHANNEL_ID || '',
    googleApiKey: process.env.GOOGLE_API_KEY || '',
    googleCalendarId: process.env.GOOGLE_CALENDAR_ID || '',
    mongodbUri: process.env.MONGODB_URI || 'mongodb://localhost:27017/prophetrdv',
    smtpHost: process.env.SMTP_HOST || 'smtp.gmail.com',
    smtpPort: parseInt(process.env.SMTP_PORT || '587'),
    smtpSecure: process.env.SMTP_SECURE === 'true',
    smtpUser: process.env.SMTP_USER || '',
    smtpPass: process.env.SMTP_PASS || '',
    prophetEmail: process.env.PROPHET_EMAIL || '',
    prophetPhone1: process.env.PROPHET_PHONE_1 || '+22897169090',
    prophetPhone2: process.env.PROPHET_PHONE_2 || '+2348119265483',
    twilioAccountSid: process.env.TWILIO_ACCOUNT_SID || '',
    twilioAuthToken: process.env.TWILIO_AUTH_TOKEN || '',
    twilioWhatsappNumber: process.env.TWILIO_WHATSAPP_NUMBER || '',
    adminWhatsappNumber: process.env.ADMIN_WHATSAPP_NUMBER || '',
    blobReadWriteToken: process.env.BLOB_READ_WRITE_TOKEN || '',
    // Public (exposé côté client)
    public: {
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000',
      prophetPhone1: process.env.PROPHET_PHONE_1 || '+22897169090',
      prophetPhone2: process.env.PROPHET_PHONE_2 || '+2348119265483',
    },
  },

  nitro: {
    compressPublicAssets: true,
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
        // Chargement non-bloquant des fonts (évite le render-blocking)
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;900&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600&family=JetBrains+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap',
          media: 'print',
          onload: "this.media='all'",
        },
      ],
      noscript: [
        {
          innerHTML: '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;900&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600&family=JetBrains+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap">',
        },
      ],
    },
  },
})
