<template>
  <div class="results">
    <button class="back-btn" @click="goBack">← Volver al formulario</button>

    <div v-if="quoteStore.noOffersAvailable" class="no-offers">
      <h2>No hay ofertas disponibles</h2>
      <p>Los proveedores no han podido procesar tu solicitud.</p>
    </div>

    <template v-else-if="quoteStore.hasQuotes">
      <div v-if="quoteStore.campaignActive" class="campaign-info">
        ¡Campaña activa! CHECK24 cubre el {{ quoteStore.discountPercentage }}% del precio
      </div>

      <QuoteTable 
        :quotes="quoteStore.sortedQuotes"
        :campaign-active="quoteStore.campaignActive"
        :sort-order="quoteStore.sortOrder"
        @toggle-sort="quoteStore.toggleSortOrder"
      />

      <div v-if="quoteStore.errors.length" class="provider-errors">
        <h4>Hay algunos proveedores no respondieron:</h4>
        <ul>
          <li v-for="error in quoteStore.errors" :key="error.provider">
            {{ error.provider }}: {{ error.error }}
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useQuoteStore } from '@/stores/quoteStore'
import QuoteTable from '@/components/QuoteTable.vue'

const router = useRouter()
const quoteStore = useQuoteStore()

function goBack() {
  router.push('/')
}
</script>

<style scoped>
.back-btn {
  background: #66a4ea;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 5px;
  cursor: pointer;
  margin-bottom: 1rem;
}

.back-btn:hover {
  background: #5aa0d6;
}

.campaign-info {
  background: linear-gradient(135deg, #669fea 0%, #4b7fa2 100%);
  color: white;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  margin-bottom: 1rem;
  font-weight: bold;
}

.no-offers {
  text-align: center;
  padding: 3rem;
  background: #f5f5f5;
  border-radius: 8px;
}

.provider-errors {
  background: #fff3cd;
  border: 1px solid #ffc107;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 1rem;
}

.provider-errors h4 {
  margin: 0 0 0.5rem;
}

.provider-errors ul {
  margin: 0;
  padding-left: 1.5rem;
}
</style>
