import { EmbedBuilder } from 'discord.js'
import { config } from './config.js'
import { BRAND_COLOR } from './format.js'

// Fires on GuildMemberAdd. Posts a friendly welcome to the configured
// welcome channel, tagging the new member. Skips silently if no channel
// is configured or if the join event is a bot account.

export async function handleMemberJoin(member) {
  if (member.user.bot) return
  const chId = config.channels.welcome
  if (!chId) return

  try {
    const ch = await member.client.channels.fetch(chId)
    const embed = new EmbedBuilder()
      .setColor(BRAND_COLOR)
      .setTitle(`👋  Welcome to Peptidemap, ${member.user.username}!`)
      .setDescription([
        `Glad to have you here. A few quick pointers:`,
        ``,
        `• Try \`/price <peptide>\` in any channel — e.g. \`/price BPC-157\``,
        `• \`/vendors <peptide>\` shows the cheapest vendors carrying it`,
        `• \`/deals\` lists the biggest live price drops`,
        `• Watch <#${config.channels.priceDrops || 'price-drops'}> for auto-posted drops (≥10% only, no spam)`,
        `• Get a peptide summary daily in <#${config.channels.peptideOfDay || 'peptide-of-the-day'}>`,
        ``,
        `Full directory: https://peptidemap.com`,
      ].join('\n'))
      .setThumbnail(member.user.displayAvatarURL({ size: 128 }))
      .setFooter({ text: 'Peptidemap · say hi in general 👋' })
      .setTimestamp()

    await ch.send({ content: `<@${member.id}>`, embeds: [embed] })
  } catch (e) {
    console.error('[welcome] send failed:', e.message)
  }
}
