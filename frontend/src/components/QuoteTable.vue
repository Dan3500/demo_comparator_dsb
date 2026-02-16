<template>
  <div class="quote-table-container">
    <div class="table-header">
      <h3>Cotizaciones disponibles</h3>
      <button class="sort-btn" @click="emit('toggleSort')">
        Ordenar por precio: {{ sortOrder === 'asc' ? '↑ Menor a mayor' : '↓ Mayor a menor' }}
      </button>
    </div>

    <table class="quote-table">
      <thead>
        <tr>
          <th>Proveedor</th>
          <th>Precio (EUR)</th>
          <th v-if="campaignActive">Precio con descuento (EUR)</th>
          <th>Nota</th>
        </tr>
      </thead>
      <tbody>
        <tr 
          v-for="quote in quotes" 
          :key="quote.provider"
          :class="{ 'cheapest': quote.is_cheapest }"
        >
          <td>{{ quote.provider }}</td>
          <td :class="{ 'strikethrough': campaignActive }">
            {{ formatPrice(quote.price) }}
          </td>
          <td v-if="campaignActive" class="discounted-price">
            {{ formatPrice(quote.discounted_price) }}
          </td>
          <td>
            <span v-if="quote.is_cheapest" class="cheapest-badge">
              ⭐ Más barato
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
const props = defineProps({
  quotes: {
    type: Array,
    required: true
  },
  campaignActive: {
    type: Boolean,
    default: false
  },
  sortOrder: {
    type: String,
    default: 'asc'
  }
})

const emit = defineEmits(['toggleSort'])

function formatPrice(price) {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'EUR'
  }).format(price)
}
</script>

<style scoped>
.quote-table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
}

.table-header h3 {
  margin: 0;
}

.sort-btn {
  background: #3a82ff;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 5px;
  cursor: pointer;
  font-size: 0.9rem;
}

.sort-btn:hover {
  background: #2563d7;
}

.quote-table {
  width: 100%;
  border-collapse: collapse;
}

.quote-table th,
.quote-table td {
  padding: 1rem 1.5rem;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.quote-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #333;
}

.quote-table tbody tr:hover {
  background: #f5f5f5;
}

.quote-table tbody tr.cheapest {
  background: linear-gradient(90deg, #d4edda 0%, #f8f9fa 100%);
}

.strikethrough {
  text-decoration: line-through;
  color: #999;
}

.discounted-price {
  font-weight: bold;
  color: #28a745;
}

.cheapest-badge {
  background: #ffd700;
  color: #333;
  padding: 0.25rem 0.75rem;
  border-radius: 15px;
  font-size: 0.85rem;
  font-weight: bold;
}
</style>
