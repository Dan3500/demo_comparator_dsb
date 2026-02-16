<template>
  <div class="home">
    <QuoteForm @submit="handleSubmit" />
    
    <div v-if="quoteStore.errorMessage" class="error-message">
      {{ quoteStore.errorMessage }}
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useQuoteStore } from '@/stores/quoteStore'
import QuoteForm from '@/components/QuoteForm.vue'

const router = useRouter()
const quoteStore = useQuoteStore()

async function handleSubmit() {
  const success = await quoteStore.fetchQuotes()
  if (success) {
    router.push('/results')
  }
}
</script>

<style scoped>
.error-message {
  background: #fee;
  border: 1px solid #f00;
  color: #c00;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 1rem;
  text-align: center;
}
</style>
