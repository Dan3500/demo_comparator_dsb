import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { quoteService } from '@/services/quoteService'

export const useQuoteStore = defineStore('quote', () => {
    
  const form = ref({
    driverBirthday: '',
    carType: '',
    carUse: ''
  })

  
  const quotes = ref([])
  const errors = ref([])
  const campaignActive = ref(false)
  const discountPercentage = ref(0)

  const loading = ref(false)
  const errorMessage = ref('')
  const sortOrder = ref('asc')

  
  const sortedQuotes = computed(() => {
    const sorted = [...quotes.value]
    sorted.sort((a, b) => {
      const priceA = a.discounted_price ?? a.price
      const priceB = b.discounted_price ?? b.price
      return sortOrder.value === 'asc' ? priceA - priceB : priceB - priceA
    })
    return sorted
  })

  const hasQuotes = computed(() => quotes.value.length > 0)
  const noOffersAvailable = computed(() => quotes.value.length === 0 && errors.value.length > 0)

  
  async function fetchQuotes() {
    loading.value = true
    errorMessage.value = ''

    try {
      const response = await quoteService.calculate({
        driver_birthday: form.value.driverBirthday,
        car_type: form.value.carType,
        car_use: form.value.carUse
      })

      quotes.value = response.quotes
      errors.value = response.errors
      campaignActive.value = response.campaign_active
      discountPercentage.value = response.discount_percentage

      saveToSession()

      return true
    } catch (error) {
      errorMessage.value = error.response?.data?.message || 'Error al obtener cotizaciones'
      return false
    } finally {
      loading.value = false
    }
  }

  function toggleSortOrder() {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  }

  function saveToSession() {
    sessionStorage.setItem('quoteForm', JSON.stringify(form.value))
    sessionStorage.setItem('quoteResults', JSON.stringify({
      quotes: quotes.value,
      errors: errors.value,
      campaignActive: campaignActive.value,
      discountPercentage: discountPercentage.value
    }))
  }

  function restoreFromSession() {
    const savedForm = sessionStorage.getItem('quoteForm')
    const savedResults = sessionStorage.getItem('quoteResults')

    if (savedForm) {
      form.value = JSON.parse(savedForm)
    }

    if (savedResults) {
      const results = JSON.parse(savedResults)
      quotes.value = results.quotes
      errors.value = results.errors
      campaignActive.value = results.campaignActive
      discountPercentage.value = results.discountPercentage
    }
  }

  function clearResults() {
    quotes.value = []
    errors.value = []
    errorMessage.value = ''
  }

  return {
    // State
    form,
    quotes,
    errors,
    campaignActive,
    discountPercentage,
    loading,
    errorMessage,
    sortOrder,
    // Computed
    sortedQuotes,
    hasQuotes,
    noOffersAvailable,
    // Actions
    fetchQuotes,
    toggleSortOrder,
    saveToSession,
    restoreFromSession,
    clearResults
  }
})
