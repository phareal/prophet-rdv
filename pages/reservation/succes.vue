<template>
  <div class="flex flex-col items-center justify-center min-h-screen gap-6 p-8">
    <div class="text-6xl">✅</div>
    <h1 class="text-2xl font-bold text-center">
      Réservation confirmée !
    </h1>
    <p class="text-muted-foreground text-center max-w-md">
      Votre confirmation a été envoyée sur WhatsApp.
      Vérifiez vos messages.
    </p>
    <div class="bg-muted rounded-lg p-4 text-sm">
      Référence : <strong>{{ refId }}</strong>
    </div>
    <Button @click="navigateTo('/')">Retour à l'accueil</Button>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const refId = route.query.ref
</script>
