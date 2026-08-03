import { Client, GatewayIntentBits, Events, MessageFlags } from 'discord.js'
import cron from 'node-cron'
import { config } from './config.js'
import { commands } from './commands.js'
import { api } from './api.js'
import {
  tickPriceDrops, tickNewProducts, tickReviews,
  tickPeptideOfDay, tickDealOfDay, tickWeeklyRecap,
} from './feeds.js'
import { handleMemberJoin } from './welcome.js'

const client = new Client({
  intents: [
    GatewayIntentBits.Guilds,
    GatewayIntentBits.GuildMessages,
    GatewayIntentBits.MessageContent,
    GatewayIntentBits.GuildMembers,
  ],
})

const handlers = new Map(commands.map(c => [c.data.name, c]))

client.once(Events.ClientReady, async c => {
  console.log(`Logged in as ${c.user.tag}`)
  try {
    const h = await api.health()
    console.log(`API reachable: ${JSON.stringify(h)}`)
  } catch (e) {
    console.error('API unreachable — commands will fail until fixed:', e.message)
  }
})

client.on(Events.GuildMemberAdd, member => handleMemberJoin(member))

client.on(Events.InteractionCreate, async interaction => {
  if (!interaction.isChatInputCommand()) return
  const h = handlers.get(interaction.commandName)
  if (!h) return
  try {
    await h.execute(interaction)
  } catch (e) {
    console.error(`[/${interaction.commandName}] failed:`, e)
    const msg = 'Sorry — that command hit an error. The team has been notified.'
    if (interaction.deferred || interaction.replied) {
      await interaction.editReply(msg).catch(() => {})
    } else {
      await interaction.reply({ content: msg, flags: MessageFlags.Ephemeral }).catch(() => {})
    }
  }
})

// Scheduled feeds — cron in UTC.
//   every 15 min    price drops (batched top-N + deduped)
//   12:00 daily     new products digest
//   12:00 daily     deal of the day (same channel as manual deals)
//   15:00 daily     peptide of the day
//   Sun 16:00       weekly recap
//   every 10 min    reviews mirror (rate-capped)

function schedule() {
  cron.schedule('*/15 * * * *', () => tickPriceDrops(client), { timezone: 'UTC' })
  cron.schedule('0 12 * * *',   () => tickNewProducts(client), { timezone: 'UTC' })
  cron.schedule('0 12 * * *',   () => tickDealOfDay(client),   { timezone: 'UTC' })
  cron.schedule('0 15 * * *',   () => tickPeptideOfDay(client),{ timezone: 'UTC' })
  cron.schedule('0 16 * * 0',   () => tickWeeklyRecap(client), { timezone: 'UTC' })
  cron.schedule('*/10 * * * *', () => tickReviews(client),     { timezone: 'UTC' })
  console.log('Cron jobs scheduled')
}

process.on('unhandledRejection', e => console.error('unhandledRejection:', e))
process.on('uncaughtException',  e => console.error('uncaughtException:', e))

await client.login(config.botToken)
schedule()
