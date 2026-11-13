import { EmbedBuilder } from 'discord.js'
import { config } from './config.js'
import { BRAND_COLOR } from './format.js'

// Fires on GuildMemberAdd. Posts a short welcome to the configured welcome
// channel, tagging the new member. Skips silently if no channel is
// configured, the welcome is switched off (FEATURE_WELCOME=false), or the
// member is a bot account.
//
// Colin, Nov 2026: the server is a chatroom first, so this points people
// at the conversation and the on-demand commands, not at feed channels.

export async function handleMemberJoin(member) {
  if (member.user.bot) return
  if (!config.features.welcome) return
  const chId = config.channels.welcome
  if (!chId) return

  try {
    const ch = await member.client.channels.fetch(chId)
    const embed = new EmbedBuilder()
      .setColor(BRAND_COLOR)
      .setTitle(`👋  Welcome to Peptidemap, ${member.user.username}!`)
      .setDescription([
        `Glad you're here. Say hi, and ask anything about vendors, prices or codes.`,
        ``,
        `The bot answers when you ask, in any channel:`,
        `• \`/price BPC-157\` for current best prices`,
        `• \`/vendors Tirzepatide\` for who carries it, cheapest first`,
        `• \`/promo\` for vendor discount codes`,
        ``,
        `Full directory: https://peptidemap.com`,
      ].join('\n'))
      .setThumbnail(member.user.displayAvatarURL({ size: 128 }))
      .setFooter({ text: 'Peptidemap' })
      .setTimestamp()

    await ch.send({ content: `<@${member.id}>`, embeds: [embed] })
  } catch (e) {
    console.error('[welcome] send failed:', e.message)
  }
}
