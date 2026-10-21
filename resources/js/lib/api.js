import axios from 'axios';

export const api = axios.create({
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  withCredentials: true,
  withXSRFToken: true,
});

api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      window.dispatchEvent(new CustomEvent('crm:unauthorized'));
    }
    if (error.response?.status === 419) {
      window.dispatchEvent(new CustomEvent('crm:error', { detail: 'Your security token expired. Reload the page and try again.' }));
    }
    if (error.response?.status >= 500) {
      window.dispatchEvent(new CustomEvent('crm:error', { detail: 'The server could not complete your request.' }));
    }
    return Promise.reject(error);
  },
);

export function errorText(error) {
  const errors = error.response?.data?.errors;
  if (errors) return Object.values(errors).flat().join(' ');
  return error.response?.data?.message || 'Request failed. Please try again.';
}
