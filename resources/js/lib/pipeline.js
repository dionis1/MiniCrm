// Pure pipeline helpers shared by the Vue UI and its unit tests.
export const STAGES = ['new', 'contacted', 'proposal', 'closed_won', 'closed_lost'];

export function filterLeads(leads, query = '') {
  const needle = query.trim().toLocaleLowerCase();
  if (!needle) return leads;
  return leads.filter((lead) =>
    [lead.name, lead.company_name, lead.email]
      .some((text) => String(text || '').toLocaleLowerCase().includes(needle))
  );
}

export function totalDealValue(leads) {
  return Math.round(leads.reduce((total, lead) => total + Number(lead.deal_value || 0), 0) * 100) / 100;
}

export function expectedRevenue(lead) {
  const value = Number(lead.deal_value || 0);
  const probability = Math.max(0, Math.min(100, Number(lead.probability || 0)));
  return Math.round(value * probability) / 100;
}
