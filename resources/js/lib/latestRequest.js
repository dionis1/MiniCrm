// Ignore results that finish after a newer request (or component disposal).
export function latestRequest() {
  let generation = 0;
  return {
    start() {
      const current = ++generation;
      return () => current === generation;
    },
    invalidate() { generation++; },
  };
}
