<template>
  <div id="app">
    <header class="header">
      <h1>Comparador de Seguros de coche - Daniel Soltero</h1>
      <p v-if="quoteStore.campaignActive" class="campaign-badge">
        ¡Campaña activa! 5% de descuento
      </p>
    </header>
    <main class="main">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useQuoteStore } from '@/stores/quoteStore'

const quoteStore = useQuoteStore()

onMounted(() => {
  quoteStore.restoreFromSession()
})
</script>

<style scoped>
.header {
  background: linear-gradient(135deg, #669fea 0%, #4b7fa2 100%);
  color: white;
  padding: 1.5rem;
  text-align: center;
}

.header h1 {
  margin: 0;
  font-size: 1.8rem;
}

.campaign-badge {
  background: #ffd700;
  color: #333;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  display: inline-block;
  margin-top: 0.5rem;
  font-weight: bold;
}

.main {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem;
}
</style>
