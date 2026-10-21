import { nextTick, onUnmounted, watch } from 'vue';

// Trap keyboard focus inside an open dialog and return it to the opener on close.
export function useDialog(open, selector, close = () => { open.value = false; }) {
  let previous;
  let disposed = false;
  const focusable = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]';
  function keys(event) {
    const dialog = document.querySelector(selector);
    if (!dialog || !open.value) return;
    if (event.key === 'Escape') { event.preventDefault(); close(); }
    if (event.key !== 'Tab') return;
    const nodes = [...dialog.querySelectorAll(focusable)].filter(el => el.getClientRects().length);
    if (!nodes.length) { event.preventDefault(); return; }
    const first = nodes[0], last = nodes.at(-1);
    if (event.shiftKey && (document.activeElement === first || !dialog.contains(document.activeElement))) {
      event.preventDefault(); last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !dialog.contains(document.activeElement))) {
      event.preventDefault(); first.focus();
    }
  }
  watch(open, async value => {
    if (value) {
      previous = document.activeElement;
      await nextTick();
      if (disposed || !open.value) return;
      document.querySelector(selector)?.querySelector(focusable)?.focus();
      document.addEventListener('keydown', keys);
    } else {
      document.removeEventListener('keydown', keys);
      previous?.focus?.();
    }
  });
  onUnmounted(() => { disposed = true; document.removeEventListener('keydown', keys); });
}
