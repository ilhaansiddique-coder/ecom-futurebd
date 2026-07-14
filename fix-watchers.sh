#!/usr/bin/env bash
# Fix "ENOSPC: System limit for number of file watchers reached".
# Raises both inotify limits (watches + instances) permanently. Run once:
#   sudo bash fix-watchers.sh
set -euo pipefail

CONF=/etc/sysctl.d/99-inotify.conf

echo "Before:"
echo "  max_user_watches  = $(cat /proc/sys/fs/inotify/max_user_watches)"
echo "  max_user_instances= $(cat /proc/sys/fs/inotify/max_user_instances)"

cat > "$CONF" <<'EOF'
# Raised for file-watching dev tools (Vite/webpack/chokidar).
fs.inotify.max_user_watches = 524288
fs.inotify.max_user_instances = 1024
EOF

# Apply immediately (no reboot needed) and persist across reboots.
sysctl -p "$CONF"

echo "After:"
echo "  max_user_watches  = $(cat /proc/sys/fs/inotify/max_user_watches)"
echo "  max_user_instances= $(cat /proc/sys/fs/inotify/max_user_instances)"
echo "Done. Wrote $CONF (persists across reboots)."
