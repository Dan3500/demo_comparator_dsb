<template>
  <form class="quote-form" @submit.prevent="handleSubmit">
    <div class="form-group">
      <label for="driverBirthday">Fecha de nacimiento del conductor *</label>
      <input
        id="driverBirthday"
        v-model="quoteStore.form.driverBirthday"
        type="date"
        required
        :max="maxDate"
        :min="minDate"
      />
      <span v-if="errors.driverBirthday" class="field-error">{{ errors.driverBirthday }}</span>
    </div>

    <div class="form-group">
      <label for="carType">Tipo de vehículo *</label>
      <select id="carType" v-model="quoteStore.form.carType" required>
        <option value="">Selecciona...</option>
        <option value="turismo">Turismo</option>
        <option value="suv">SUV</option>
        <option value="compacto">Compacto</option>
      </select>
      <span v-if="errors.carType" class="field-error">{{ errors.carType }}</span>
    </div>

    <div class="form-group">
      <label>Uso del vehículo *</label>
      <div class="radio-group">
        <label class="radio-label">
          <input
            type="radio"
            v-model="quoteStore.form.carUse"
            value="privado"
            required
          />
          Privado
        </label>
        <label class="radio-label">
          <input
            type="radio"
            v-model="quoteStore.form.carUse"
            value="comercial"
          />
          Comercial
        </label>
      </div>
      <span v-if="errors.carUse" class="field-error">{{ errors.carUse }}</span>
    </div>

    <button 
      type="submit" 
      class="submit-btn"
      :disabled="quoteStore.loading || !isFormValid"
    >
      <span v-if="quoteStore.loading">Calculando...</span>
      <span v-else>Calcular cotizaciones</span>
    </button>
  </form>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useQuoteStore } from '@/stores/quoteStore'

const emit = defineEmits(['submit'])
const quoteStore = useQuoteStore()

const errors = ref({
  driverBirthday: '',
  carType: '',
  carUse: ''
})

// Date limits
const today = new Date()
const maxDate = computed(() => {
  const date = new Date()
  date.setFullYear(date.getFullYear() - 18) // Min 18 years old
  return date.toISOString().split('T')[0]
})

const minDate = computed(() => {
  const date = new Date()
  date.setFullYear(date.getFullYear() - 100) // Max 100 years old
  return date.toISOString().split('T')[0]
})

const isFormValid = computed(() => {
  return quoteStore.form.driverBirthday && 
         quoteStore.form.carType && 
         quoteStore.form.carUse
})

function validate() {
  errors.value = { driverBirthday: '', carType: '', carUse: '' }
  let valid = true

  if (!quoteStore.form.driverBirthday) {
    errors.value.driverBirthday = 'La fecha de nacimiento es obligatoria'
    valid = false
  } else {
    const birthDate = new Date(quoteStore.form.driverBirthday)
    const age = Math.floor((today - birthDate) / (365.25 * 24 * 60 * 60 * 1000))
    
    if (birthDate > today) {
      errors.value.driverBirthday = 'La fecha no puede ser futura'
      valid = false
    } else if (age < 18) {
      errors.value.driverBirthday = 'El conductor debe tener al menos 18 años'
      valid = false
    }
  }

  if (!quoteStore.form.carType) {
    errors.value.carType = 'Selecciona un tipo de vehículo'
    valid = false
  }

  if (!quoteStore.form.carUse) {
    errors.value.carUse = 'Selecciona el uso del vehículo'
    valid = false
  }

  return valid
}

function handleSubmit() {
  if (validate()) {
    emit('submit')
  }
}
</script>

<style scoped>
.quote-form {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
}

.form-group input[type="date"],
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input[type="date"]:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
}

.radio-group {
  display: flex;
  gap: 2rem;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-weight: normal;
}

.radio-label input[type="radio"] {
  width: 18px;
  height: 18px;
}

.field-error {
  color: #dc3545;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

.submit-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #669fea 0%, #2b59ae 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
  transition: opacity 0.2s;
}

.submit-btn:hover:not(:disabled) {
  opacity: 0.9;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
