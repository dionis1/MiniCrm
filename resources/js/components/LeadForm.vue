<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({ lead: { type: Object, default: null }, saving: Boolean });
const emit = defineEmits(['save', 'cancel']);
const form = reactive({ name: '', email: '', phone: '', company_name: '', stage: 'new', deal_value: 0, probability: 10 });
watch(() => props.lead, (value) => {
  Object.assign(form, {
    name: value?.name || '', email: value?.email || '', phone: value?.phone || '',
    company_name: value?.company_name || '', stage: value?.stage || 'new',
    deal_value: Number(value?.deal_value || 0), probability: Number(value?.probability ?? 10),
  });
}, { immediate: true });
function submit() {
  emit('save', { ...form, version: props.lead?.version });
}
</script>

<template>
  <form @submit.prevent="submit" class="grid gap-4 sm:grid-cols-2">
    <label class="text-sm font-medium">Name * <input v-model.trim="form.name" required maxlength="160" class="mt-1 w-full rounded-lg border p-2"></label>
    <label class="text-sm font-medium">Company <input v-model.trim="form.company_name" maxlength="160" class="mt-1 w-full rounded-lg border p-2"></label>
    <label class="text-sm font-medium">Email <input v-model.trim="form.email" type="email" class="mt-1 w-full rounded-lg border p-2"></label>
    <label class="text-sm font-medium">Phone <input v-model.trim="form.phone" maxlength="40" class="mt-1 w-full rounded-lg border p-2"></label>
    <label class="text-sm font-medium">Stage
      <select v-model="form.stage" class="mt-1 w-full rounded-lg border p-2">
        <option value="new">New lead</option><option value="contacted">Contacted</option><option value="proposal">Proposal</option>
        <option value="closed_won">Closed won</option><option value="closed_lost">Closed lost</option>
      </select>
    </label>
    <label class="text-sm font-medium">Deal value (€)
      <input v-model.number="form.deal_value" type="number" min="0" max="9999999999" step="0.01" required class="mt-1 w-full rounded-lg border p-2">
    </label>
    <label class="text-sm font-medium">Probability (%)
      <input v-model.number="form.probability" type="number" min="0" max="100" required class="mt-1 w-full rounded-lg border p-2">
    </label>
    <div class="flex items-end gap-2 sm:col-span-2">
      <button type="submit" :disabled="saving" class="rounded-lg bg-indigo-700 px-4 py-2 font-semibold text-white disabled:opacity-50">{{ saving ? 'Saving…' : lead ? 'Save changes' : 'Create lead' }}</button>
      <button type="button" @click="emit('cancel')" class="rounded-lg border px-4 py-2">Cancel</button>
    </div>
  </form>
</template>
