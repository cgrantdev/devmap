import { SlashCommandBuilder, EmbedBuilder } from 'discord.js'
import { api } from './api.js'
import { BRAND_COLOR, money, truncate, priceLine } from './format.js'

// Command definitions — one file so registration + handling stay together.
// If this grows past ~6 commands, split into commands/<name>.js.

export const commands = [
  {
    data: new SlashCommandBuilder()
      .setName('price')
      .setDescription('Look up the current price of a peptide across vendors')
      .addStringOption(o =>
        o.setName('peptide').setDescription('e.g. BPC-157, Semaglutide').setRequired(true)),
    async execute(interaction) {
      const q = interaction.options.getString('peptide')
      await interaction.deferReply()
      const data = await api.search(q, 5)
      if (!data.results.length) {
        return interaction.editReply(`No matches for **${truncate(q, 100)}**.`)
      }
      const embed = new EmbedBuilder()
        .setColor(BRAND_COLOR)
        .setTitle(`💊  ${truncate(q, 240)} — top ${data.results.length} listings`)
        .setURL(`https://peptidemap.com/search?q=${encodeURIComponent(q)}`)
        .setFooter({ text: 'peptidemap.com — click a title for the vendor listing' })

      for (const p of data.results) {
        embed.addFields({
          name: truncate(`${p.name}${p.size_mg ? ` (${p.size_mg})` : ''} — ${p.brand_name}`, 256),
          value: truncate(`${priceLine(p)}\n[View on Peptidemap](${p.url})`, 1024),
          inline: false,
        })
      }
      return interaction.editReply({ embeds: [embed] })
    },
  },

  {
    data: new SlashCommandBuilder()
      .setName('vendors')
      .setDescription('List vendors carrying a peptide, cheapest first')
      .addStringOption(o =>
        o.setName('peptide').setDescription('e.g. Tirzepatide').setRequired(true)),
    async execute(interaction) {
      const q = interaction.options.getString('peptide')
      await interaction.deferReply()
      const data = await api.vendors(q, 10)
      if (!data.results.length) {
        return interaction.editReply(`No vendors found for **${truncate(q, 100)}**.`)
      }
      const lines = data.results.map((p, i) =>
        `\`${String(i + 1).padStart(2, ' ')}.\` **${p.brand_name}** — ${priceLine(p)}${p.size_mg ? ` · ${p.size_mg}` : ''}  [→](${p.url})`
      )
      const embed = new EmbedBuilder()
        .setColor(BRAND_COLOR)
        .setTitle(`🏷️  Vendors for ${truncate(data.matched_category || q, 240)}`)
        .setURL(data.matched_category
          ? `https://peptidemap.com/peptide/${encodeURIComponent(data.matched_category.toLowerCase().replace(/\s+/g, '-'))}`
          : `https://peptidemap.com/search?q=${encodeURIComponent(q)}`)
        .setDescription(truncate(lines.join('\n'), 4096))
        .setFooter({ text: `${data.count} vendor${data.count === 1 ? '' : 's'} · sorted by effective price` })
      return interaction.editReply({ embeds: [embed] })
    },
  },

  {
    data: new SlashCommandBuilder()
      .setName('compare')
      .setDescription('Side-by-side compare two peptides')
      .addStringOption(o => o.setName('a').setDescription('First peptide').setRequired(true))
      .addStringOption(o => o.setName('b').setDescription('Second peptide').setRequired(true)),
    async execute(interaction) {
      const a = interaction.options.getString('a')
      const b = interaction.options.getString('b')
      await interaction.deferReply()
      const data = await api.compare(a, b)
      const embed = new EmbedBuilder()
        .setColor(BRAND_COLOR)
        .setTitle(`⚖️  ${truncate(a, 100)}  vs  ${truncate(b, 100)}`)
        .setURL(`https://peptidemap.com/compare?a=${encodeURIComponent(a)}&b=${encodeURIComponent(b)}`)

      for (const side of [data.a, data.b]) {
        const val = side.found
          ? `**Vendors:** ${side.vendor_count}\n**Products:** ${side.product_count}\n**Price range:** ${money(side.min_price)} – ${money(side.max_price)}\n**Median:** ${money(side.median_price)}\n[Open on site](${side.url})`
          : `_Not in the directory yet._`
        embed.addFields({ name: truncate(side.name || side.query, 256), value: truncate(val, 1024), inline: true })
      }
      return interaction.editReply({ embeds: [embed] })
    },
  },

  {
    data: new SlashCommandBuilder()
      .setName('promo')
      .setDescription('Show active vendor discount codes (PMAP, etc.)'),
    async execute(interaction) {
      await interaction.deferReply()
      const data = await api.promoCodes(15)
      if (!data.results?.length) return interaction.editReply('No active promo codes right now.')
      const byCode = {}
      for (const r of data.results) (byCode[r.code] ??= []).push(r)
      const embed = new EmbedBuilder()
        .setColor(0xF59E0B)
        .setTitle('💸  Active vendor discount codes')
        .setURL('https://peptidemap.com/vendors')
        .setDescription('Enter at checkout on the vendor site.')
      for (const [code, brands] of Object.entries(byCode).slice(0, 10)) {
        brands.sort((a, b) => b.discount_pct - a.discount_pct)
        const lines = brands.slice(0, 12).map(b => `• [${truncate(b.brand_name, 60)}](${b.url}) — **${b.discount_pct}%**`).join('\n')
        embed.addFields({ name: `\`${code}\`  (${brands.length})`, value: truncate(lines, 1024), inline: false })
      }
      return interaction.editReply({ embeds: [embed] })
    },
  },

  {
    data: new SlashCommandBuilder()
      .setName('deals')
      .setDescription('Show the biggest live price drops right now'),
    async execute(interaction) {
      await interaction.deferReply()
      // Rolling 30-day window so we always have something to show even on
      // quiet days (the #price-drops feed uses a tighter same-day window).
      const since = new Date(Date.now() - 30 * 86400_000).toISOString()
      const data = await api.priceDrops(since, 10, 8)
      if (!data.results.length) {
        return interaction.editReply('No live price drops right now — check back later, or use `/price <peptide>` for the current best price.')
      }
      const lines = data.results.map(d =>
        `**-${d.drop_pct}%** · **${truncate(d.name, 80)}** — ${d.brand_name}\n${money(d.old_price)} → **${money(d.new_price)}**  ·  [View](${d.url})`
      )
      const embed = new EmbedBuilder()
        .setColor(0x22C55E)
        .setTitle('🔥  Live price drops')
        .setURL('https://peptidemap.com/products?sort=discount')
        .setDescription(truncate(lines.join('\n\n'), 4096))
        .setFooter({ text: `Top ${data.results.length} drops · updated in real time` })
      return interaction.editReply({ embeds: [embed] })
    },
  },
]
