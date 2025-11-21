import axios from 'axios'
import { useAuth } from '../stores/auth'

const api = axios.create({
  // vepsat vlastni api url ktery vypisuje vas BE
  //baseURL: 'https://www.stud.fit.vutbr.cz/~xkaval05/laravel/api',
  baseURL: 'http://127.0.0.1:8000/api',
  withCredentials: false,
  headers: { Accept: 'application/json' }
})

api.interceptors.request.use(config => {
  const auth = useAuth()
  if (auth?.token) config.headers.Authorization = `Bearer ${auth.token}`
  return config
})
api.interceptors.response.use(
  r => r,
  err => {
    if (err?.response?.status === 401) {
      // тронем глобальное событие — чтобы открыть модал логина
      window.dispatchEvent(new CustomEvent('need-login'))
    }
    throw err
  }
)
export default api
