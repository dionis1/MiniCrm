import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useToastStore = defineStore('toasts', () => {
  const toasts = ref([]);
  let nextId = 0;
  function push(message, type = 'success') {
    const id = ++nextId;
    toasts.value.push({ id, message, type });
    setTimeout(() => remove(id), 4500);
  }
  function remove(id) {
    toasts.value = toasts.value.filter(item => item.id !== id);
  }
  return { toasts, push, remove };
});
