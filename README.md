# MultiBrands Module for Dolibarr — v1.2.8

**Author:** atlasbase.net  
**License:** GPL v3+  
**Requires:** Dolibarr v17.0+  
**Tested on:** Dolibarr v23.0

Issue proposals, invoices, and orders under **multiple brand identities** from a single Dolibarr company. Each brand gets its own logo, company name, address, colors, legal text, and bank account on generated PDFs — without creating separate companies.

---

## Features

| Feature | Detail |
|---------|--------|
| 🏷️ Unlimited brands | Each with logo, name, address, colors, legal text |
| 📄 Branded PDF templates | Proposals (azur/cyan), Invoices (crabe), Orders (eratosthene) |
| 🔗 Per-customer default | Set a default brand per thirdparty — auto-applied to new documents |
| 🔄 Auto-trigger | Brand inherited from the thirdparty on document creation |
| 🧩 Extrafields | `brand_code` dropdown added to proposals, invoices, orders, thirdparties |
| 🛠️ Repair tool | One-click fix for missing extrafields via the setup page |

---

## Installation

### Step 1 — Upload

Copy the `multibrands/` folder to your Dolibarr custom modules directory:

```
htdocs/custom/multibrands/
```

> ⚠️ The folder **must** be named `multibrands` (no hyphens). A differently named folder will break model discovery.

### Step 2 — Enable the Module

Go to **Home → Setup → Modules/Applications** → **Products** tab → find **MultiBrands** → click the toggle to enable it.

### Step 3 — Set Default PDF Templates

After enabling, tell Dolibarr to use the branded templates by default:

- **Proposals:** `Setup → Commercial → Proposals → Default PDF model` → select `cyan_branded`
- **Invoices:** `Setup → Accounting → Invoices → Default PDF model` → select `crabe_branded`
- **Orders:** `Setup → Commercial → Orders → Default PDF model` → select `eratosthene_branded`

> If the branded models don't appear in the dropdowns, disable and re-enable the module to refresh the model cache.

---

## Usage

### 1. Create Your Brands

Go to **Tools (Outils) → MultiBrands** in the left sidebar, or navigate to:
```
/custom/multibrands/admin/setup.php
```

Click **New Brand** and fill in:

| Field | Description |
|-------|-------------|
| **Label** | Display name (e.g. "Acme France") |
| **Code** | Short machine code — lowercase + underscores only (e.g. `acme_france`) |
| **Logo** | PNG / JPG / GIF / WebP, max 2 MB |
| **Company Name** | Overrides your global company name on PDFs |
| **Address / Zip / Town / Country** | Full address block printed on PDFs |
| **Phone / Email / Website** | Contact details on PDFs |
| **Bank Account** | Links to a Dolibarr bank account (shown on invoices) |
| **Primary Color** | Main color (used in PDF headers) |
| **Secondary Color** | Accent color (used in PDF footers) |
| **Legal Text** | Registration numbers, VAT number, share capital — printed in footer |
| **Footer Text** | Tagline or thank-you message at the bottom of PDFs |
| **Default Brand** | ✅ Check this to auto-assign when no brand is explicitly selected |
| **Active** | Uncheck to hide from all dropdowns without deleting |

### 2. Assign a Default Brand to a Customer

1. Open any **Thirdparty (Tiers)** record
2. Go to the **"Additional attributes" (Attributs complémentaires)** tab
3. Set the **Default Brand** dropdown to the brand used for this customer
4. Save

All new documents created for this thirdparty will automatically inherit this brand.

### 3. Set the Brand on a Document

On any **Proposal / Invoice / Order** card:

1. The **Brand** dropdown appears directly on the edit form (or in the "Additional attributes" tab)
2. Select the brand to use for this specific document
3. Save the document

> If no brand is set on the document, the system falls back to the thirdparty's default brand, then to the global default brand.

### 4. Generate a Branded PDF

1. Open the Proposal / Invoice / Order
2. Click the **Generate PDF** button
3. In the model dropdown, select a branded template:
   - `cyan_branded` or `azur_branded` for proposals
   - `crabe_branded` for invoices
   - `eratosthene_branded` for orders
4. Click **Generate** — the PDF will use the selected brand's logo, company info, colors, legal text, and footer

---

## Per-Brand Dynamic Models

When you create a brand with code `my_brand`, the module automatically generates dedicated PDF model files:

- `pdf_cyan_my_brand.modules.php`
- `pdf_azur_my_brand.modules.php`
- `pdf_crabe_my_brand.modules.php`
- `pdf_eratosthene_my_brand.modules.php`

These appear in Dolibarr's model dropdown labelled with the brand name. Using them means the brand is hardcoded into the model — no extrafield selection needed.

---

## Setup Page — Diagnostics

The setup page at **Tools → MultiBrands** includes a diagnostics panel:

| Check | What it means |
|-------|--------------|
| **Logo Directory: Writable** | The `documents/multibrands/brands/logos/` folder exists and is writable |
| **Brands defined** | Number of brands in the database |
| **Extrafield brand_code @ propal** | The Brand dropdown is registered for proposals |
| **Extrafield brand_code @ societe** | The Default Brand dropdown is registered for thirdparties |
| **Extrafield brand_code @ facture** | The Brand dropdown is registered for invoices |
| **Extrafield brand_code @ commande** | The Brand dropdown is registered for orders |

If any extrafield shows **Missing**, click the **Repair Extrafields** button to recreate them.

---

## Troubleshooting

**Brand dropdown not visible on proposal/invoice/order**
→ Go to the setup page → click **Repair Extrafields**. This sets `list=1` and `printable=1` on the extrafield, making it visible on document cards.

**`crabe_branded` or `eratosthene_branded` not in model dropdown**
→ Disable and re-enable the MultiBrands module. If still missing, check that the files exist in `custom/multibrands/core/modules/facture/doc/` and `custom/multibrands/core/modules/commande/doc/`.

**PDF still shows the main company info instead of brand**
→ Verify a brand is selected on the document (extrafield), and that you are using a `_branded` model (not the plain `crabe`, `azur`, etc.).

**Logo not showing on PDF**
→ Ensure the logo file is in `documents/multibrands/brands/logos/` (writable). Check the Diagnostics panel. Logo must be under 2 MB.

**Tools menu link gives a 404**
→ Run this SQL then disable/re-enable the module:
```sql
UPDATE llx_menu 
SET url = '/custom/multibrands/admin/setup.php'
WHERE url LIKE '%multi-brands/admin/setup.php%';
```

**Translation keys showing raw (e.g. `HowToUse`, `DefaultBrand`)**
→ Run **Repair Extrafields** and clear the Dolibarr file cache at `Home → Setup → Other → Clear cache`.

**Logo upload fails with "Invalid image file"**
→ The `php-fileinfo` extension is missing on the server. Ask your host to install it, or use a `.jpg` file (PHP's `getimagesize()` is used as a fallback).

---

## Diagnostic Tool

If you get a 500 / blank page, upload `debug.php` from the module root to your Dolibarr root directory and visit:
```
https://yoursite.com/debug.php
```

It checks: PHP paths, `main.inc.php` location, module file existence, database tables, extrafields, and PHP upload limits.

> Remove `debug.php` from the server root after debugging.

---

## File Map

```
multibrands/
├── core/
│   ├── modules/
│   │   ├── modMultiBrands.class.php               Module descriptor, DB schema, extrafields
│   │   ├── propale/doc/
│   │   │   ├── pdf_azur_branded.modules.php        Branded proposal (Dolibarr v17–22)
│   │   │   └── pdf_cyan_branded.modules.php        Branded proposal (Dolibarr v23+)
│   │   ├── facture/doc/
│   │   │   └── pdf_crabe_branded.modules.php       Branded invoice
│   │   └── commande/doc/
│   │       └── pdf_eratosthene_branded.modules.php Branded order
│   ├── substitutions/
│   │   └── functions_multibrands.lib.php           Email substitution variables
│   └── triggers/
│       └── interface_99_modMultiBrands_MultiBrandsWorkflow.class.php
├── admin/
│   └── setup.php                                   Brand management UI + diagnostics
├── class/
│   └── multibrand.class.php                        ORM class + PDF model generator
├── lib/
│   └── multibrands.lib.php                         Brand injection helpers
├── langs/
│   ├── en_US/multibrands.lang
│   └── fr_FR/multibrands.lang
├── debug.php                                        Standalone diagnostic tool
├── multibrands.info.yml
└── README.md
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| **v1.2.8** | 2026-04-26 | Fixed extrafield visibility (`list=1`, `printable=1`); fixed `langfile` reference (`multibrands@multibrands`); fixed invoice/order model discovery (load abstract parent before extending); fixed menu URL stuck on old `multi-brands` path; added PRG redirect after Repair Extrafields; bumped all model files to load `modules_*.php` abstract before concrete class |
| **v1.2.8** | 2026-04-26 | Renamed module folder from `multi-brands` to `multibrands`; fixed model discovery (added `models => 1` to module_parts); bumped `$this->name` to match folder; hardcoded `MAIN_MODULE_MULTIBRANDS` constant; menu URL updated to `/custom/multibrands/` |
| **v1.1.5** | 2026-04-25 | Dolibarr v23 support: `pdf_cyan_branded` template; fixed logo preview (`cache` + `entity` params); fixed PDF logo path (logos subdirectory); dynamic per-brand PDF model generation |
| **v1.1.0** | — | Complete rewrite: bulletproof includes, diagnostic tool, all document types |
| **v1.0.2** | — | Fixed include path issues |
| **v1.0.1** | — | Hotfix for missing main.inc.php |
| **v1.0.0** | — | Initial release |

---

Built by atlasbase.net
