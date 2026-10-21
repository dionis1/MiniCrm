import { describe, expect, it } from 'vitest';
import { expectedRevenue, filterLeads, STAGES, totalDealValue } from '../../resources/js/lib/pipeline';

describe('CRM pipeline domain helpers', () => {
  const leads = [
    { name: 'Ada', company_name: 'Acme', email: 'ada@example.test', deal_value: '1200.50', probability: 50 },
    { name: 'Grace', company_name: 'Gamma', email: 'grace@example.test', deal_value: '799.50', probability: 80 },
  ];

  it('matches names, companies and email addresses regardless of case', () => {
    expect(filterLeads(leads, ' ACME ')).toEqual([leads[0]]);
    expect(filterLeads(leads, 'GRACE@')).toEqual([leads[1]]);
    expect(filterLeads(leads, '')).toEqual(leads);
  });

  it('keeps the five canonical lead stages in order', () => {
    expect(STAGES).toEqual(['new', 'contacted', 'proposal', 'closed_won', 'closed_lost']);
  });

  it('computes money totals and probability-weighted revenue', () => {
    expect(totalDealValue(leads)).toBe(2000);
    expect(expectedRevenue(leads[0])).toBe(600.25);
    expect(expectedRevenue({ deal_value: 100, probability: 250 })).toBe(100);
  });
});
