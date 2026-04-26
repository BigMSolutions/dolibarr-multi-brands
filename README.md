# MultiBrands Module for Dolibarr v1.1.4

**Author:** atlasbase.net  
**License:** GPL v3+

Run multiple brands / DBAs under a single Dolibarr company. Each brand gets its own logo, company name, address, colors, legal text, and footer on generated PDFs.

---

## What It Does

- **Proposals** (azur template) → `azur_branded`
- **Invoices** (crabe template) → `crabe_branded`
- **Orders** (eratosthene template) → `eratosthene_branded`
- **Email templates** → substitution variables pick up brand info

Each brand gets:
- Logo, Company name, Address, Phone, Email, URL
- Bank account linkage
- Primary/secondary colors
- Legal text and footer text
- Default brand (auto-assigned when none selected)

---

## Installation

### 1. Upload

Copy the entire `multi-brands/` folder to:
```
htdocs/custom/multi-brands/
```

### 2. Enable

Go to **Home → Setup → Modules/Applications** → **Products** section → enable **MultiBrands**.

### 3. Set Default PDF Templates

- **Proposals:** Setup → Proposals → Default model → `azur_branded`
- **Invoices:** Setup → Invoices → Default model → `crabe_branded`
- **Orders:** Setup → Orders → Default model → `eratosthene_branded`

---

## Configuration

### Create Brands

**Tools → MultiBrands**

| Field | Purpose |
|-------|---------|
| Label | Display name |
| Code | Machine name: lowercase + underscores |
| Logo | PNG/JPG/GIF/WebP, max 2MB |
| Company Name | Overrides global name on PDFs |
| Address/Zip/Town/Country | Full contact block |
| Phone/Email/URL | Contact details |
| Bank Account | Linked Dolibarr bank account |
| Colors | Primary (header), Secondary (footer) |
| Legal Text | Registration numbers, capital |
| Footer Text | Thank you message or tagline |
| Default | Auto-assign when no brand selected |
| Active | Show/hide from selectors |

### Set Customer Defaults

On any **Thirdparty** → **Extrafields** tab → select **Default Brand**. All new documents auto-inherit it.

### Select Brand on Documents

On **Proposal/Invoice/Order** → **Extrafields** tab → select **Brand**. Generate PDF — branding switches instantly.

---

## Diagnostic Tool

If you get a 500 error, upload `debug.php` to your Dolibarr root and visit it in your browser:
```
https://yoursite.com/debug.php
```

It will show:
- Whether main.inc.php is found
- Whether module files are in the right place
- Whether database tables exist
- PHP paths and error log location

---

## Troubleshooting

**Brand dropdown is empty on documents**
→ Create at least one brand, then reload the document page.

**PDF still shows old company info**
→ Make sure you've selected the branded PDF template as default.

**Logo not showing**
→ Check file is under 2MB and `documents/multibrands/brands/logos/` is writable.

**Trigger not auto-assigning**
→ Verify trigger is active: Home → Setup → Security → Triggers.

---

## File Map

```
multi-brands/
├── core/modules/modMultiBrands.class.php          v1.1.0
├── core/triggers/interface_99_...Workflow.class.php
├── core/substitutions/functions_multibrands.lib.php
├── core/modules/propale/doc/pdf_azur_branded.modules.php
├── core/modules/facture/doc/pdf_crabe_branded.modules.php
├── core/modules/commande/doc/pdf_eratosthene_branded.modules.php
├── admin/setup.php                                (with bulletproof includes)
├── class/multibrand.class.php
├── lib/multibrands.lib.php
├── langs/en_US/multibrands.lang
├── debug.php                                      (diagnostic tool)
└── README.md
```

---

## Version History

- **v1.1.4** — Fixed logo preview (added cache/entity params), fixed PDF model discovery (added models modulepart), fixed logo path for PDF generation (logos subdirectory)
- **v1.1.0** — Complete rewrite: bulletproof includes, diagnostic tool, all document types
- **v1.0.2** — Fixed include path issues
- **v1.0.1** — Hotfix for missing main.inc.php
- **v1.0.0** — Initial release

---

Built by atlasbase.net
