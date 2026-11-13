import 'dotenv/config'

// Bot reads its config from Forge .env (same file the Laravel app uses)
// via a symlink: /home/forge/peptidemap-bot/.env -> the Laravel .env.
// That way secrets rotate in one place.

function req(key) {
  const v = process.env[key]
  if (!v) throw new Error(`Missing required env: ${key}`)
  return v
}

export const config = {
  botToken: req('DISCORD_BOT_TOKEN'),
  appId: req('DISCORD_APPLICATION_ID'),
  guildId: req('DISCORD_GUILD_ID'),
  apiBase: process.env.BOT_API_BASE || 'https://peptidemap.com',
  apiToken: req('BOT_API_TOKEN'),
  channels: {
    priceDrops: process.env.DISCORD_CHANNEL_PRICE_DROPS || null,
    newProducts: process.env.DISCORD_CHANNEL_NEW_PRODUCTS || null,
    vendorReviews: process.env.DISCORD_CHANNEL_VENDOR_REVIEWS || null,
    deals: process.env.DISCORD_CHANNEL_DEALS || null,
    news: process.env.DISCORD_CHANNEL_NEWS || null,
    blog: process.env.DISCORD_CHANNEL_BLOG || null,
    peptideOfDay: process.env.DISCORD_CHANNEL_PEPTIDE_OF_DAY || process.env.DISCORD_CHANNEL_NEWS || null,
    welcome: process.env.DISCORD_CHANNEL_WELCOME || null,
  },
  // Feature switches. Colin, Nov 2026: the server is a chatroom first, not
  // a notice board. Scheduled feeds are off unless FEATURE_X=true is set
  // in the .env; the Friday codes post and the welcome stay on unless
  // FEATURE_X=false. Restart the systemd unit after editing the .env.
  features: {
    priceDrops: process.env.FEATURE_PRICE_DROPS === 'true',
    newProducts: process.env.FEATURE_NEW_PRODUCTS === 'true',
    reviews: process.env.FEATURE_REVIEWS === 'true',
    peptideOfDay: process.env.FEATURE_PEPTIDE_OF_DAY === 'true',
    dealOfDay: process.env.FEATURE_DEAL_OF_DAY === 'true',
    weeklyRecap: process.env.FEATURE_WEEKLY_RECAP === 'true',
    blogOfDay: process.env.FEATURE_BLOG_OF_DAY === 'true',
    promoSpotlight: process.env.FEATURE_PROMO_SPOTLIGHT !== 'false',
    welcome: process.env.FEATURE_WELCOME !== 'false',
  },
  // Anti-spam thresholds
  limits: {
    priceDropMinPct: Number(process.env.PRICE_DROP_MIN_PCT || 10),
    priceDropTopN: Number(process.env.PRICE_DROP_TOP_N || 5),
    priceDropDedupeDays: Number(process.env.PRICE_DROP_DEDUPE_DAYS || 7),
    newProductsMinCount: Number(process.env.NEW_PRODUCTS_MIN_COUNT || 3),
    reviewsRateLimitPerHour: Number(process.env.REVIEWS_RATE_LIMIT_PER_HOUR || 3),
  },
}
