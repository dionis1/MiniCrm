import axios from 'axios';

const api = axios.create({
  baseURL: '/',
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
});

export async function csrf() {
  await api.get('/sanctum/csrf-cookie');
}

export function validationMessage(error) {
  const errors = error.response?.data?.errors;
  if (errors) return Object.values(errors).flat().join(' ');
  return error.response?.data?.message || 'The request could not be completed.';
}

export default api;
