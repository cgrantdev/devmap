# Peptidemap Discord Bot

Node.js discord.js v14 bot. Runs on the same Forge droplet as the site,
managed by systemd. Reads/writes exclusively through `/api/bot/*` on
peptidemap.com — never touches the Laravel DB directly.

## Layout

```
src/
  index.js            entry — client, interactions, cron scheduler
  config.js           env-loaded config + feature switches
  api.js              /api/bot/* HTTP client
  state.js            JSON file for last-run/dedupe state (/var/lib/…)
  format.js           embed helpers
  commands.js         slash command definitions + handlers
  feeds.js            price-drops, new-products, reviews, evergreen posts
  deploy-commands.js  one-off — registers commands to the configured guild
```

## Systemd

`/etc/systemd/system/peptidemap-bot.service` — see `SYSTEMD.md` in this dir.

```bash
sudo systemctl restart peptidemap-bot
sudo journalctl -u peptidemap-bot -f
```

## Slash commands

Guild-scoped. To (re)register after editing `commands.js`:

```bash
cd /home/forge/peptidemap-bot && npm run deploy-commands
```

## Env

Bot reads a symlink at `.env` that points to the site's `/current/.env`
so secrets rotate in one place. Bot-only keys:

- `BOT_API_TOKEN` — bearer for `/api/bot/*` (generated at install)
- `DISCORD_BOT_TOKEN`, `DISCORD_APPLICATION_ID`, `DISCORD_GUILD_ID`
- `DISCORD_CHANNEL_*` — one per feed target
- `FEATURE_*=false` — silence any feed without a redeploy
- `PRICE_DROP_MIN_PCT` (default 10), `PRICE_DROP_TOP_N` (5),
  `PRICE_DROP_DEDUPE_DAYS` (7), `NEW_PRODUCTS_MIN_COUNT` (3),
  `REVIEWS_RATE_LIMIT_PER_HOUR` (3)
