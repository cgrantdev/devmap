// Country → region map. Colin PMAP Sep 22 — vendors were scrolling
// through 100+ countries in a flat list; grouping by region + a
// search box makes it navigable. Each region only lists countries
// that meaningfully ship peptides; niche ones (Vatican, San Marino)
// still show under "Other" if they exist in the DB.
//
// Names match the display names in the locations table. When
// backfilling new regions, add the country to the appropriate array
// or push it to "Other" — the fallback catches every DB row not
// explicitly grouped so nothing disappears.

export const REGION_ORDER = [
  'North America',
  'Europe',
  'United Kingdom',
  'Central America & Caribbean',
  'South America',
  'GCC / Gulf States',
  'South Asia',
  'Southeast Asia',
  'East Asia',
  'Middle East',
  'Africa',
  'Oceania',
  'Other',
]

export const REGION_MAP = {
  'North America': ['United States', 'Canada', 'Mexico'],
  'United Kingdom': ['United Kingdom', 'Ireland'],
  'Europe': [
    'Albania', 'Andorra', 'Austria', 'Belarus', 'Belgium', 'Bosnia and Herzegovina',
    'Bulgaria', 'Croatia', 'Cyprus', 'Czech Republic', 'Denmark', 'Estonia', 'Finland',
    'France', 'Germany', 'Greece', 'Hungary', 'Iceland', 'Italy', 'Latvia', 'Liechtenstein',
    'Lithuania', 'Luxembourg', 'Malta', 'Moldova', 'Monaco', 'Montenegro', 'Netherlands',
    'North Macedonia', 'Norway', 'Poland', 'Portugal', 'Romania', 'Russia', 'San Marino',
    'Serbia', 'Slovakia', 'Slovenia', 'Spain', 'Sweden', 'Switzerland', 'Ukraine',
  ],
  'Central America & Caribbean': [
    'Belize', 'Costa Rica', 'El Salvador', 'Guatemala', 'Honduras', 'Nicaragua', 'Panama',
    'Antigua and Barbuda', 'Bahamas', 'Barbados', 'Cuba', 'Dominica', 'Dominican Republic',
    'Grenada', 'Haiti', 'Jamaica', 'Saint Kitts and Nevis', 'Saint Lucia',
    'Saint Vincent and the Grenadines', 'Trinidad and Tobago', 'Puerto Rico',
  ],
  'South America': [
    'Argentina', 'Bolivia', 'Brazil', 'Chile', 'Colombia', 'Ecuador', 'Guyana',
    'Paraguay', 'Peru', 'Suriname', 'Uruguay', 'Venezuela',
  ],
  'GCC / Gulf States': [
    'Bahrain', 'Kuwait', 'Oman', 'Qatar', 'Saudi Arabia', 'United Arab Emirates',
  ],
  'South Asia': [
    'Afghanistan', 'Bangladesh', 'Bhutan', 'India', 'Maldives', 'Nepal', 'Pakistan', 'Sri Lanka',
  ],
  'Southeast Asia': [
    'Brunei', 'Cambodia', 'Indonesia', 'Laos', 'Malaysia', 'Myanmar', 'Philippines',
    'Singapore', 'Thailand', 'Timor-Leste', 'Vietnam',
  ],
  'East Asia': ['China', 'Hong Kong', 'Japan', 'Macau', 'Mongolia', 'South Korea', 'Taiwan'],
  'Middle East': ['Iran', 'Iraq', 'Israel', 'Jordan', 'Lebanon', 'Palestine', 'Syria', 'Turkey', 'Yemen'],
  'Africa': [
    'Algeria', 'Angola', 'Botswana', 'Burkina Faso', 'Burundi', 'Cameroon', 'Cape Verde',
    'Central African Republic', 'Chad', 'Comoros', 'Democratic Republic of the Congo',
    'Djibouti', 'Egypt', 'Equatorial Guinea', 'Eritrea', 'Ethiopia', 'Gabon', 'Gambia',
    'Ghana', 'Guinea', 'Guinea-Bissau', 'Ivory Coast', 'Kenya', 'Lesotho', 'Liberia', 'Libya',
    'Madagascar', 'Malawi', 'Mali', 'Mauritania', 'Mauritius', 'Morocco', 'Mozambique',
    'Namibia', 'Niger', 'Nigeria', 'Republic of the Congo', 'Rwanda', 'São Tomé and Príncipe',
    'Senegal', 'Seychelles', 'Sierra Leone', 'Somalia', 'South Africa', 'South Sudan',
    'Sudan', 'Tanzania', 'Togo', 'Tunisia', 'Uganda', 'Zambia', 'Zimbabwe', 'Eswatini',
  ],
  'Oceania': [
    'Australia', 'Fiji', 'Kiribati', 'Marshall Islands', 'Micronesia', 'Nauru',
    'New Zealand', 'Palau', 'Papua New Guinea', 'Samoa', 'Solomon Islands', 'Tonga',
    'Tuvalu', 'Vanuatu',
  ],
}

/**
 * Group a list of {id, name} country rows into region buckets in
 * REGION_ORDER order. Countries not found in any region fall into
 * "Other" so no row silently drops. Optional search filter narrows
 * to countries whose name contains the query (case-insensitive).
 */
export function groupLocationsByRegion(locations = [], search = '') {
  const nameToRegion = {}
  for (const [region, list] of Object.entries(REGION_MAP)) {
    for (const c of list) nameToRegion[c.toLowerCase()] = region
  }

  const q = (search || '').trim().toLowerCase()
  const buckets = {}
  for (const loc of locations) {
    const name = String(loc?.name || '').trim()
    if (!name) continue
    if (q && !name.toLowerCase().includes(q)) continue
    const region = nameToRegion[name.toLowerCase()] || 'Other'
    if (!buckets[region]) buckets[region] = []
    buckets[region].push(loc)
  }

  return REGION_ORDER
    .filter((r) => buckets[r] && buckets[r].length > 0)
    .map((r) => ({ region: r, countries: buckets[r].slice().sort((a, b) => a.name.localeCompare(b.name)) }))
}
