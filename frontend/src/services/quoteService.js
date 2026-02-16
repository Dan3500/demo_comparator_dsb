import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json'
  }
})

export const quoteService = {
  async calculate(data) {
    const response = await api.post('/calculate', data)
    return response.data
  }
}
