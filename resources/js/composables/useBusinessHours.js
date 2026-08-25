// Business-hours helpers — turn the vendor_settings.business_hours_json
// blob into a human string ("Mon–Fri 9am–5pm ET · Weekends closed") and
// a live "open now / closed" pill.
//
// The JSON shape stored per vendor:
// {
//   timezone: 'America/New_York',
//   mon: { open: '09:00', close: '17:00' },  // 24h "HH:MM"
//   tue: {...},
//   ...
//   sat: null,   // null = closed that day
//   sun: null,
// }

const DAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
const DAY_LABELS = { mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu', fri: 'Fri', sat: 'Sat', sun: 'Sun' }

// TZ → user-facing abbreviation (best-effort, US-heavy).
const TZ_ABBR = {
  'America/New_York': 'ET',
  'America/Chicago': 'CT',
  'America/Denver': 'MT',
  'America/Los_Angeles': 'PT',
  'Europe/London': 'GMT',
  'Europe/Berlin': 'CET',
  'UTC': 'UTC',
}

function fmt12(hhmm) {
  if (!hhmm) return ''
  const [h, m] = hhmm.split(':').map(Number)
  const ampm = h < 12 ? 'am' : 'pm'
  const h12 = ((h + 11) % 12) + 1
  return m === 0 ? `${h12}${ampm}` : `${h12}:${String(m).padStart(2, '0')}${ampm}`
}

/**
 * "Mon–Fri 9am–5pm ET · Weekends closed" style summary. Groups adjacent
 * days that share the same open/close so the output stays terse.
 */
export function humanize(hours) {
  if (!hours || typeof hours !== 'object') return null
  const tz = TZ_ABBR[hours.timezone] || hours.timezone || ''

  // Walk 7 days building [{days: ['mon','tue'], open, close}, ...]
  const groups = []
  let current = null
  for (const day of DAY_KEYS) {
    const h = hours[day]
    const sig = h ? `${h.open}-${h.close}` : 'CLOSED'
    if (current && current.sig === sig) {
      current.days.push(day)
    } else {
      current = { sig, days: [day], hours: h }
      groups.push(current)
    }
  }

  const parts = groups.map(g => {
    const dayLabel = g.days.length === 1
      ? DAY_LABELS[g.days[0]]
      : `${DAY_LABELS[g.days[0]]}–${DAY_LABELS[g.days.at(-1)]}`
    if (!g.hours) return `${dayLabel} closed`
    return `${dayLabel} ${fmt12(g.hours.open)}–${fmt12(g.hours.close)}`
  })

  return parts.join(' · ') + (tz ? ` ${tz}` : '')
}

/**
 * Per-day list — one row per day of the week, in Mon-Sun order.
 * Returns [{day: 'Mon', label: '9am–5pm'}, {day: 'Tue', label: 'Closed'}, ...].
 * Used when the caller wants a stacked line-by-line display instead of
 * the compressed `humanize()` output.
 */
export function daysList(hours) {
  if (!hours || typeof hours !== 'object') return []
  const tz = TZ_ABBR[hours.timezone] || hours.timezone || ''
  return DAY_KEYS.map(k => {
    const h = hours[k]
    const label = (h && h.open && h.close)
      ? `${fmt12(h.open)}–${fmt12(h.close)}`
      : 'Closed'
    return { day: DAY_LABELS[k], label, tz }
  })
}

/**
 * Returns { open: bool, label: 'Open now' | 'Closed' | 'Opens Mon 9am' }.
 * Uses the vendor's declared timezone so the answer is honest regardless
 * of where the viewer is.
 */
export function openStatus(hours) {
  if (!hours || typeof hours !== 'object') return null
  const tz = hours.timezone || 'UTC'
  const now = new Date()

  // Get local hour/minute/day at the vendor's tz.
  let localParts
  try {
    localParts = new Intl.DateTimeFormat('en-US', {
      timeZone: tz,
      weekday: 'short',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).formatToParts(now)
  } catch { return null }

  const partsMap = Object.fromEntries(localParts.map(p => [p.type, p.value]))
  const weekday = partsMap.weekday?.toLowerCase()?.slice(0, 3) // 'mon'
  const hour = Number(partsMap.hour)
  const minute = Number(partsMap.minute)
  const nowMinutes = hour * 60 + minute

  const today = hours[weekday]
  if (today && today.open && today.close) {
    const [oH, oM] = today.open.split(':').map(Number)
    const [cH, cM] = today.close.split(':').map(Number)
    const openMin = oH * 60 + oM
    const closeMin = cH * 60 + cM
    if (nowMinutes >= openMin && nowMinutes < closeMin) {
      return { open: true, label: 'Open now', closesAt: fmt12(today.close) }
    }
  }
  return { open: false, label: 'Closed' }
}
