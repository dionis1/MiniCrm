import { afterEach, describe, expect, it } from 'vitest';
import { latestRequest } from '../../resources/js/lib/latestRequest';
import { toLocalInput } from '../../resources/js/lib/dates';

describe('request ordering', () => {
  it('rejects older results and results arriving after disposal', () => {
    const requests = latestRequest();
    const older = requests.start();
    const newer = requests.start();
    expect(older()).toBe(false);
    expect(newer()).toBe(true);
    requests.invalidate();
    expect(newer()).toBe(false);
  });
});

const timezone = process.env.TZ;
afterEach(() => { if (timezone === undefined) delete process.env.TZ; else process.env.TZ = timezone; });
describe('local due-date editing', () => {
  it('keeps a UTC timestamp at the correct local wall time across a DST change', () => {
    process.env.TZ = 'America/New_York';
    expect(toLocalInput('2026-03-08T06:30:00Z')).toBe('2026-03-08T01:30');
    expect(toLocalInput('2026-03-08T07:30:00Z')).toBe('2026-03-08T03:30');
    expect(toLocalInput(null)).toBe('');
  });
});
