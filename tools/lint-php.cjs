// PHP syntax check without a PHP binary. Pushes to main auto-deploy to
// production, and a parse error 500s every route in the affected file.
// Usage: npm run lint:php [-- paths...]   (defaults to all app PHP dirs)
const fs = require('fs');
const path = require('path');
const { Engine } = require('php-parser');

const engine = new Engine({
  parser: { extractDoc: false, suppressErrors: false, version: '8.3' },
  ast: { withPositions: true },
  lexer: { all_tokens: false, short_tags: false },
});

function walk(dir, out) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.name === 'vendor' || entry.name === 'node_modules' || entry.name.startsWith('.')) continue;
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(full, out);
    else if (entry.name.endsWith('.php') && !entry.name.endsWith('.blade.php')) out.push(full);
  }
  return out;
}

const args = process.argv.slice(2);
const targets = args.length ? args : ['app', 'routes', 'config', 'database', 'bootstrap'];
const files = [];
for (const t of targets) {
  if (!fs.existsSync(t)) continue;
  if (fs.statSync(t).isDirectory()) walk(t, files);
  else if (t.endsWith('.php')) files.push(t);
}

let failures = 0;
for (const file of files) {
  try {
    engine.parseCode(fs.readFileSync(file, 'utf8'), file);
  } catch (e) {
    failures++;
    console.error(`PHP syntax error in ${file}: ${e.message}`);
  }
}
console.log(`lint:php - ${files.length} files checked, ${failures} failed`);
process.exit(failures ? 1 : 0);
