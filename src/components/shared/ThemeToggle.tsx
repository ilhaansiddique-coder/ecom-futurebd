import { MoonStar, SunMedium } from "lucide-react";

import { Button } from "@/components/ui/button";
import { applyTheme, useTheme, type ThemeMode } from "@/lib/theme";

export function ThemeToggle() {
  const theme = useTheme();

  const toggleTheme = () => {
    const nextTheme: ThemeMode = theme === "light" ? "dark" : "light";
    applyTheme(nextTheme);
  };

  return (
    <Button
      type="button"
      variant="outline"
      size="icon"
      className="rounded-full border-border/80 bg-background/80"
      aria-label="Toggle color theme"
      onClick={toggleTheme}
    >
      {theme === "light" ? <MoonStar className="h-4 w-4" /> : <SunMedium className="h-4 w-4" />}
    </Button>
  );
}
