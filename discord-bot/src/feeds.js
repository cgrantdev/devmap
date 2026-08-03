import { EmbedBuilder } from 'discord.js'
import { api } from './api.js'
import { config } from './config.js'
import * as state from './state.js'
import { BRAND_COLOR, money, priceLine, truncate } from './format.js'

// Every feed:
//   - reads its last-run timestamp from state.json (default: 24h ago)
//   - fetches from /api/bot/*
//   - posts to its configured channel if the result passes the anti-spam gate
//   - records the new timestamp so we don't re-post
//
// Errors get logged and swallowed — a broken feed must not crash the bot.

function isoAgo(hours) {
  return new Date(Date.now() - hours * 3600_000).toISOString()
}

async function safeSend(client, channelId, payload, label) {
  if (!channelId) return console.log(`[${label}] no channel configured, skipping`)
  try {
    const ch = await client.channels.fetch(channelId)
    await ch.send(payload)
  } catch (e) {
    console.error(`[${label}] send failed:`, e.message)
  }
}

/* ─── Price drops feed (hourly, batched, deduped) ─── */

export async function tickPriceDrops(client) {
  if (!config.features.priceDrops) return
  const last = await state.get('priceDrops.lastRun', isoAgo(1))
  const seen = (await state.get('priceDrops.seen', {})) || {}
  const dedupeMs = config.limits.priceDropDedupeDays * 86400_000
  const now = Date.now()

  // Prune the seen-map so it doesn't grow forever
  for (const [pid, ts] of Object.entries(seen)) {
    if (now - new Date(ts).getTime() > dedupeMs) delete seen[pid]
  }

  let data
  try {
    data = await api.priceDrops(last, config.limits.priceDropMinPct, 50)
  } catch (e) {
    return console.error('[priceDrops] fetch failed:', e.message)
  }

  const fresh = (data.results || []).filter(d => !seen[d.product_id])
  if (!fresh.length) {
    await state.set('priceDrops.lastRun', new Date().toISOString())
    return
  }

  const top = fresh
    .sort((a, b) => b.drop_pct - a.drop_pct)
    .slice(0, config.limits.priceDropTopN)

  const lines = top.map(d =>
    `**-${d.drop_pct}%** · **${truncate(d.name, 80)}** — ${d.brand_name}\n${money(d.old_price)} → **${money(d.new_price)}**  ·  [View](${d.url})`
  )
  const embed = new EmbedBuilder()
    .setColor(0x22C55E)
    .setTitle(`🔥  ${top.length === 1 ? 'New price drop' : `${top.length} new price drops`}`)
    .setDescription(truncate(lines.join('\n\n'), 4096))
    .setFooter({ text: `Peptidemap · min drop ${config.limits.priceDropMinPct}% · deduped ${config.limits.priceDropDedupeDays}d` })
    .setTimestamp()

  await safeSend(client, config.channels.priceDrops, { embeds: [embed] }, 'priceDrops')

  for (const d of top) seen[d.product_id] = new Date().toISOString()
  await state.set('priceDrops.seen', seen)
  await state.set('priceDrops.lastRun', new Date().toISOString())
}

/* ─── New products digest (daily 12:00 UTC) ─── */

export async function tickNewProducts(client) {
  if (!config.features.newProducts) return
  const last = await state.get('newProducts.lastRun', isoAgo(24))
  let data
  try {
    data = await api.newProducts(last, 30)
  } catch (e) {
    return console.error('[newProducts] fetch failed:', e.message)
  }
  if ((data.count || 0) < config.limits.newProductsMinCount) {
    await state.set('newProducts.lastRun', new Date().toISOString())
    return
  }
  const byBrand = {}
  for (const p of data.results) {
    byBrand[p.brand_name] ??= []
    byBrand[p.brand_name].push(p)
  }
  const embed = new EmbedBuilder()
    .setColor(BRAND_COLOR)
    .setTitle(`🆕  ${data.count} new product${data.count === 1 ? '' : 's'} added`)
    .setFooter({ text: 'Peptidemap · daily digest' })
    .setTimestamp()

  for (const [brand, items] of Object.entries(byBrand).slice(0, 20)) {
    const value = items.slice(0, 5).map(p => `• [${truncate(p.name, 100)}](${p.url}) — ${money(p.effective_price)}`).join('\n')
    embed.addFields({ name: truncate(brand, 256), value: truncate(value, 1024), inline: false })
  }
  await safeSend(client, config.channels.newProducts, { embeds: [embed] }, 'newProducts')
  await state.set('newProducts.lastRun', new Date().toISOString())
}

/* ─── Vendor reviews mirror (real-time, curated) ─── */

export async function tickReviews(client) {
  if (!config.features.reviews) return
  const last = await state.get('reviews.lastRun', isoAgo(1))
  let data
  try {
    data = await api.reviews(last, 25)
  } catch (e) {
    return console.error('[reviews] fetch failed:', e.message)
  }
  // Curate: only extreme reviews (1-2★ or 4-5★). Middling 3★ noise-out.
  const curated = (data.results || []).filter(r => r.rating <= 2 || r.rating >= 4)
  // Rate cap
  const window = await state.get('reviews.recentTimestamps', [])
  const oneHourAgo = Date.now() - 3600_000
  const recent = window.filter(t => t > oneHourAgo)
  const remaining = Math.max(0, config.limits.reviewsRateLimitPerHour - recent.length)
  const toPost = curated.slice(0, remaining)

  for (const r of toPost) {
    const stars = '⭐'.repeat(r.rating) + '☆'.repeat(5 - r.rating)
    const embed = new EmbedBuilder()
      .setColor(r.rating >= 4 ? 0x22C55E : 0xEF4444)
      .setTitle(truncate(r.title || `${stars}  Review of ${r.brand_name}`, 256))
      .setURL(r.brand_url)
      .setDescription(truncate(`${stars}\n\n${r.body || ''}`, 2000))
      .setAuthor({ name: r.author || 'Anonymous' })
      .setFooter({ text: `Peptidemap · ${r.brand_name}` })
      .setTimestamp(new Date(r.created_at))
    await safeSend(client, config.channels.vendorReviews, { embeds: [embed] }, 'reviews')
    recent.push(Date.now())
  }
  await state.set('reviews.recentTimestamps', recent.filter(t => t > oneHourAgo))
  await state.set('reviews.lastRun', new Date().toISOString())
}

/* ─── Peptide of the Day (evergreen, no external freshness needed) ─── */

export async function tickPeptideOfDay(client) {
  if (!config.features.peptideOfDay) return
  // Rotate through the 40-ish categories by day-of-year so we don't repeat
  // for weeks. We fetch a whole vendors listing for a compound and pick its
  // best-price row for the tile. If we later add /api/bot/random-compound
  // this becomes 3 lines shorter.
  const catalog = ['BPC-157', 'TB-500', 'Semaglutide', 'Tirzepatide', 'DSIP', 'Selank', 'Semax',
    'Ipamorelin', 'CJC-1295', 'PT-141', 'MOTS-c', 'Epithalon', 'GHK-Cu', 'Melanotan II',
    'Retatrutide', 'Cagrilintide', 'HGH Fragment 176-191', 'Thymalin', 'Thymosin Alpha-1',
    'AOD-9604', 'HCG', 'LL-37', 'Kisspeptin-10', 'Adipotide', 'FOXO4-DRI']
  const dayIdx = Math.floor(Date.now() / 86400_000) % catalog.length
  const q = catalog[dayIdx]

  let data
  try {
    data = await api.vendors(q, 3)
  } catch (e) {
    return console.error('[peptideOfDay] fetch failed:', e.message)
  }
  if (!data.results?.length) return console.log(`[peptideOfDay] no data for ${q}, skipping`)

  const cheapest = data.results[0]
  const cheapestLine = data.results.map(p =>
    `• **${p.brand_name}** — ${priceLine(p)}  [→](${p.url})`
  ).join('\n')

  const embed = new EmbedBuilder()
    .setColor(BRAND_COLOR)
    .setTitle(`🧬  Peptide of the Day: ${data.matched_category || q}`)
    .setURL(`https://peptidemap.com/peptide/${encodeURIComponent((data.matched_category || q).toLowerCase().replace(/\s+/g, '-'))}`)
    .setDescription(`Best available today:\n\n${cheapestLine}\n\nUse \`/vendors ${q}\` for the full list, or \`/price ${q}\` to search by size.`)
    .setThumbnail(cheapest?.image_url || null)
    .setFooter({ text: 'Peptidemap · daily' })
    .setTimestamp()

  await safeSend(client, config.channels.peptideOfDay, { embeds: [embed] }, 'peptideOfDay')
}

/* ─── Deal of the Day (single top drop, posted to #deals) ─── */

export async function tickDealOfDay(client) {
  if (!config.features.dealOfDay) return
  const since = isoAgo(24 * 30) // any drop in the last 30d is fair game
  let data
  try {
    data = await api.priceDrops(since, 15, 5)
  } catch (e) {
    return console.error('[dealOfDay] fetch failed:', e.message)
  }
  const top = data.results?.[0]
  if (!top) return console.log('[dealOfDay] no deals qualify today')

  const embed = new EmbedBuilder()
    .setColor(0xF59E0B)
    .setTitle(`💥  Deal of the Day: -${top.drop_pct}%`)
    .setURL(top.url)
    .setDescription(`**${truncate(top.name, 200)}** — ${top.brand_name}\n\n~~${money(top.old_price)}~~ → **${money(top.new_price)}**`)
    .setThumbnail(top.image_url || null)
    .setFooter({ text: 'Peptidemap · daily' })
    .setTimestamp()
  await safeSend(client, config.channels.deals, { embeds: [embed] }, 'dealOfDay')
}

/* ─── Promo code spotlight (Tue + Fri, posted to #deals) ─── */

export async function tickPromoSpotlight(client) {
  if (!config.features.promoSpotlight) return
  let data
  try {
    data = await api.promoCodes(15)
  } catch (e) {
    return console.error('[promoSpotlight] fetch failed:', e.message)
  }
  if (!data.results?.length) return console.log('[promoSpotlight] no active codes')

  // Group by code (most vendors use PMAP) so the message reads as
  // "here's who accepts each code" rather than a jumble.
  const byCode = {}
  for (const r of data.results) (byCode[r.code] ??= []).push(r)

  const embed = new EmbedBuilder()
    .setColor(0xF59E0B)
    .setTitle('💸  Active vendor discount codes')
    .setURL('https://peptidemap.com/vendors')
    .setDescription('Use these at checkout on the vendor\'s own site. Discount is applied by the vendor, not by Peptidemap.')

  for (const [code, brands] of Object.entries(byCode).slice(0, 10)) {
    brands.sort((a, b) => b.discount_pct - a.discount_pct)
    const lines = brands.slice(0, 12).map(b => `• [${truncate(b.brand_name, 60)}](${b.url}) — **${b.discount_pct}%**`).join('\n')
    embed.addFields({
      name: `\`${code}\`  (${brands.length} vendor${brands.length === 1 ? '' : 's'})`,
      value: truncate(lines, 1024),
      inline: false,
    })
  }
  embed.setFooter({ text: 'Peptidemap · codes refresh as vendors update them' }).setTimestamp()

  await safeSend(client, config.channels.deals, { embeds: [embed] }, 'promoSpotlight')
}

/* ─── Blog of the Day (rotates through existing posts) ─── */

export async function tickBlogOfDay(client) {
  if (!config.features.blogOfDay) return
  let data
  try {
    data = await api.blogOfDay()
  } catch (e) {
    return console.error('[blogOfDay] fetch failed:', e.message)
  }
  if (!data.found) return console.log('[blogOfDay] no blogs available, skipping')

  const embed = new EmbedBuilder()
    .setColor(BRAND_COLOR)
    .setTitle(`📰  ${truncate(data.title, 240)}`)
    .setURL(data.url)
    .setDescription(truncate((data.description || 'Read the full article on peptidemap.com.') + `\n\n[Read on Peptidemap →](${data.url})`, 4096))
    .setImage(data.image_url || null)
    .setFooter({ text: `Peptidemap${data.read_time ? ' · ' + data.read_time : ''}` })
    .setTimestamp()
  await safeSend(client, config.channels.news, { embeds: [embed] }, 'blogOfDay')
}

/* ─── Weekly Recap (Sunday) ─── */

export async function tickWeeklyRecap(client) {
  if (!config.features.weeklyRecap) return
  const since = isoAgo(7 * 24)
  const [drops, newp, revs] = await Promise.all([
    api.priceDrops(since, 5, 100).catch(() => ({ results: [] })),
    api.newProducts(since, 100).catch(() => ({ results: [] })),
    api.reviews(since, 100).catch(() => ({ results: [] })),
  ])
  const topDrop = drops.results?.[0]
  const embed = new EmbedBuilder()
    .setColor(BRAND_COLOR)
    .setTitle('📊  This week on Peptidemap')
    .setDescription([
      `**${drops.results?.length || 0}** price drops`,
      `**${newp.results?.length || 0}** new products listed`,
      `**${revs.results?.length || 0}** new customer reviews`,
      topDrop ? `\n🔥 Biggest drop: **-${topDrop.drop_pct}%** on [${truncate(topDrop.name, 100)}](${topDrop.url}) at ${topDrop.brand_name}` : '',
    ].filter(Boolean).join('\n'))
    .setFooter({ text: 'Peptidemap · weekly recap' })
    .setTimestamp()
  await safeSend(client, config.channels.news, { embeds: [embed] }, 'weeklyRecap')
}
