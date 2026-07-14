import { useSyncExternalStore } from "react";

export const THEME_KEY = "dashboard-theme";

export type ThemeMode = "light" | "dark";

export function applyTheme(theme: ThemeMode) {
  document.documentElement.classList.toggle("dark", theme === "dark");
  document.documentElement.dataset.theme = theme;
  window.localStorage.setItem(THEME_KEY, theme);
}

function subscribe(onChange: () => void) {
  const observer = new MutationObserver(onChange);
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["class", "data-theme"],
  });

  return () => observer.disconnect();
}

// The <html> class is the single source of truth: it is set before first paint by
// the inline script in app.blade.php, so every consumer stays in sync with it.
export function useTheme(): ThemeMode {
  return useSyncExternalStore(
    subscribe,
    () => (document.documentElement.classList.contains("dark") ? "dark" : "light"),
    () => "light" as ThemeMode,
  );
}
