import { REST, Routes } from 'discord.js'
import { config } from './config.js'
import { commands } from './commands.js'

// One-off script to register slash commands against the configured guild.
// Guild-scoped commands appear instantly (no ~1hr propagation lag).
// Run: `npm run deploy-commands` after editing commands.js.

const rest = new REST({ version: '10' }).setToken(config.botToken)

try {
  console.log(`Registering ${commands.length} commands to guild ${config.guildId}…`)
  const data = await rest.put(
    Routes.applicationGuildCommands(config.appId, config.guildId),
    { body: commands.map(c => c.data.toJSON()) },
  )
  console.log(`✓ Registered ${data.length} commands: ${data.map(c => '/' + c.name).join(', ')}`)
} catch (e) {
  console.error('Deploy failed:', e)
  process.exit(1)
}
