# Vendor Certification Verification Workflow

Vendor-submitted claims for **cGMP-compliant manufacturing** and **7+
compound independent testing** — reviewed by an admin, approved claims
light up a **Peptidemap-verified badge** on the vendor's storefront and
product cards, and post to Discord.

The point: we can't let vendors self-declare "cGMP Facility" the way
they might self-declare "Ships Internationally." IDUN was flagged in
Aug 2026 for claiming credentials it didn't hold — verification exists
to prevent that on the badge tier of trust signals.

---

## Lifecycle

```
Vendor uploads doc  →  Pending review  →  Admin approves/rejects
     (PDF/img)                                     │
                                                   ├─→ Approved: badge live + Discord post
                                                   └─→ Rejected: vendor sees note, can resubmit
```

**States** (`vendor_certification_claims.status`):
- `pending` — vendor submitted, awaiting review
- `approved` — badge is live on the storefront
- `rejected` — vendor needs to resubmit; admin note explains why

**One row per (brand, type)** — a resubmission overwrites the doc, wipes
the previous verdict + admin notes, and returns the row to `pending`.

---

## Types

Managed as a whitelist in `VendorCertificationClaim::TYPE_LABELS`:

| Key | Badge label | Typical proof |
|---|---|---|
| `cgmp` | cGMP Compliant Manufacturing | Facility audit / cGMP cert |
| `testing_7x` | 7+ Compound Independent Testing | Batch COAs from an independent lab |

**Adding a new type:**
1. Add the constant + label to `VendorCertificationClaim.php`
2. It automatically appears in the vendor submit form
3. Add a matching case anywhere frontend needs custom rendering (icon,
   copy) — the default `label` fallback works otherwise

---

## Files

**Backend:**
- `database/migrations/2026_08_28_140000_create_vendor_certification_claims_table.php`
- `app/Models/VendorCertificationClaim.php`
- `app/Http/Controllers/Vendor/CertificationsController.php` — vendor submit
- `app/Http/Controllers/Admin/CertificationsController.php` — admin queue + approve/reject + Discord post + private doc download

**Frontend:**
- `resources/js/Pages/Vendor/Certifications.vue` — vendor submits claims per type
- `resources/js/Pages/Admin/Certifications/Index.vue` — admin review queue
- `resources/js/Pages/Frontend/BrandProducts.vue` — renders the verified-badges row under the brand name

**Routes:**
- `GET  /vendor/certifications` — vendor form
- `POST /vendor/certifications` — vendor submit
- `GET  /admin/certifications` — admin queue (query `?status=pending|approved|rejected`)
- `GET  /admin/certifications/{id}/document` — private doc download (admin only)
- `POST /admin/certifications/{id}/approve` — admin approve
- `POST /admin/certifications/{id}/reject` — admin reject (note required)

---

## Storage

**Documents live under `storage/app/certifications/{claim_id}/{filename}`
on the `local` (private) disk.** They are never public. Admins fetch via
the signed session-auth download route above. Replacing a doc on a
resubmit deletes the old file so nothing leaks.

Accepted formats: PDF, PNG, JPG, WebP. Max size: 15 MB.

---

## Frontend badge display

On `/brand/{slug}` the badges appear as a small emerald pill row under
the brand name, next to the star cluster:

```
✓ cGMP Compliant Manufacturing   ✓ 7+ Compound Independent Testing
```

Data source: `brand.verified_badges` — an array of `{type, label}`
computed server-side, only populated with `status = 'approved'` rows.
Vendors with no approved claims render nothing (the block is `v-if`'d
on the array length).

**To add badges to other views** (vendor cards on `/brands`, product-card
brand badges), read the same `verified_badges` array off the brand
payload and render the same pill component. Prefer showing just an
icon-only pill in the smaller surfaces to avoid crowding.

---

## Discord announcement

When a claim is approved, a message is posted to the growth channel
(`services.discord.growth_channel_id`, currently `1541364154093404171`)
in the format:

> 🏅 **Amino Club** verified: **cGMP Compliant Manufacturing** — https://peptidemap.com/brand/amino-club

The post is a fire-and-forget best-effort — if Discord is down the
approval still commits. Failures are logged at WARNING level.

Rejections do NOT post. The vendor gets the rejection message in the
UI and (soon) via email; broadcasting a rejection to Discord would
create noise + a bad signal.

---

## Admin workflow

Julia's day-to-day:

1. **Open `/admin/certifications`** — defaults to the `pending` filter
2. **Review each row:**
   - Click the paperclip link to download the doc
   - Read the vendor's notes (rendered above the doc link)
3. **Approve or reject:**
   - Approve → optional note, badge goes live, Discord posts
   - Reject → **note is required** (explains what's wrong so the vendor
     can fix + resubmit)

The queue is sorted `pending` first, then by `created_at` desc.

---

## Vendor workflow

The vendor's day-to-day (they'll see it in their dashboard nav):

1. **Visit `/vendor/certifications`**
2. For each certification type, upload a supporting doc + optional notes
3. Submitting sets status to `pending` — they see a chip + "under review"
4. Approvals show a green "Verified on {date}" line + badge appears live
5. Rejections show the reviewer's note + a fresh upload form so they can
   fix and resubmit

---

## Non-goals / known gaps

- **No expiry** — badges are permanent once approved. A future
  enhancement: yearly re-verification (add `verified_until` column,
  auto-flip to `pending` when it passes).
- **No email notifications** — approvals/rejections are only visible when
  the vendor next visits `/vendor/certifications`. Adding a Mailable +
  queued dispatch on state change would close this loop.
- **No versioning** — a resubmission overwrites the previous doc entirely.
  If we ever need audit history, add a `vendor_certification_claim_history`
  side table on state changes.
- **Not surfaced yet on `/brands` grid or product cards** — the data is
  in the payload, but the pill component still needs to be added to
  those templates. Filed as a follow-up under PMAP doc #3 (USP-based
  filters).

---

## Testing

Manual smoke test on a fresh install:

```bash
# 1. Migrate
php artisan migrate

# 2. Log in as any vendor user, hit /vendor/certifications
#    - Upload a fake PDF for both types
#    - Verify status shows "pending"

# 3. Log in as admin, hit /admin/certifications
#    - See both claims in the queue
#    - Click download → verify file streams correctly
#    - Approve one → verify badge appears on /brand/{slug} + Discord post fires
#    - Reject the other → verify vendor page shows the reject note
```
