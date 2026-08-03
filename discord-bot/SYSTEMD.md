# Systemd unit for peptidemap-bot

Written once at install time; no changes needed unless the bot path moves.

```ini
# /etc/systemd/system/peptidemap-bot.service
[Unit]
Description=Peptidemap Discord bot
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=forge
Group=forge
WorkingDirectory=/home/forge/peptidemap-bot
ExecStart=/usr/bin/node src/index.js
Restart=on-failure
RestartSec=5
StandardOutput=journal
StandardError=journal
SyslogIdentifier=peptidemap-bot
Environment=NODE_ENV=production
# Bot writes dedupe state here; must be writable by the forge user.
ReadWritePaths=/var/lib/peptidemap-bot

[Install]
WantedBy=multi-user.target
```

Install:

```bash
sudo mkdir -p /var/lib/peptidemap-bot
sudo chown forge:forge /var/lib/peptidemap-bot
sudo systemctl daemon-reload
sudo systemctl enable --now peptidemap-bot
sudo systemctl status peptidemap-bot
```

Deploy loop: `git pull` inside `/home/forge/peptidemap-bot` (which is a
worktree of the site repo's `/discord-bot` subdir — see install script)
then `sudo systemctl restart peptidemap-bot`.
