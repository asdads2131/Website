# Prompee Shop — Main Page Rebuild + Email Backend

## Context

The current prompee.shop is essentially just a payment/pricing page — three cards, email inputs, purchase buttons that redirect to RiskPay. There's no real landing page, no product showcase, no trust-building sections. LO wants a proper storefront that looks similar in aesthetic (dark theme, Inter font, blue/orange gradients) but modernized and intentionally designed — not vibe-coded slop.

The payment flow already works (Payment_path/index.html → checkout.html → RiskPay). We need to build a main page that funnels into that existing payment system, and add a backend to persist customer emails.

## What Exists

### Asset: Payment page
**Path:** `/home/urdb/Buisness/Payment_path/index.html`
**Purpose:** Pricing cards + RiskPay redirect

### Asset: Checkout page
**Path:** `/home/urdb/Buisness/Payment_path/checkout.html`
**Purpose:** Order summary + payment method selection

### Asset: Banner image
**Path:** `/home/urdb/Buisness/Pictures/Banner.png`
**Purpose:** Site banner/hero graphic

### Asset: Claude image
**Path:** `/home/urdb/Buisness/Pictures/Claude.png`
**Purpose:** Product image for Claude prompts

### Asset: Gemini image
**Path:** `/home/urdb/Buisness/Pictures/Gemini.jpg`
**Purpose:** Product image for Gemini prompts

### Asset: Grok image
**Path:** `/home/urdb/Buisness/Pictures/Grok.jpg`
**Purpose:** Product image for Grok prompts

## Design Direction

### Keep the existing aesthetic DNA:
- Dark background (#1C1C21), card bg (#1F1F26)
- Inter font family
- Blue-to-orange gradient accents (#26A7F0 → #F59A47)
- Noise texture overlay
- Subtle fadeUp animations

### Modernize with:
- Proper page sections (hero → products → pricing → footer)
- Product cards with the AI images (Claude, Gemini, Grok)
- Better visual hierarchy and spacing
- Responsive grid that actually breathes
- Smooth scroll navigation
- No framework bloat — vanilla HTML/CSS/JS

## Project Structure

```
/home/urdb/Buisness/Main_Page/
├── index.html              # Main landing page
├── css/
│   └── style.css           # All styles (no framework)
├── js/
│   └── main.js             # Frontend logic (email validation, nav, animations)
├── images/                 # Symlinked or copied from Pictures/
│   ├── Banner.png
│   ├── Claude.png
│   ├── Gemini.jpg
│   └── Grok.jpg
├── backend/
│   ├── server.js           # Express server (email storage + API)
│   ├── package.json        # Dependencies
│   └── data/
│       └── emails.db       # SQLite database for emails
└── PLAN.md                 # This plan (copy)
```

## Page Sections (top to bottom)

### 1. Navigation Bar
- Fixed top nav, transparent → solid on scroll
- "Prompee" logo (gradient text, left)
- Nav links: Products, Pricing, Contact (right)
- Smooth scroll to sections

### 2. Hero Section
- Banner.png as background/accent image
- Large gradient headline: "AI Prompts That Actually Work"
- Subtitle explaining the value prop
- CTA button scrolling to pricing

### 3. Products Showcase
- 3-column grid (responsive → stacks on mobile)
- Each card maps an image to a bundle:
  - Claude.png → Programmer Bundle (€29.75)
  - Grok.jpg → Research Bundle (€11.90)
  - Gemini.jpg → Programmer Bundle Lite (€22.61)
- Card layout: image top, product name, short description, "View Pricing" link scrolling to pricing section
- Hover effects (subtle scale + border glow)

### 4. Pricing Section
- Recreate the 3 pricing cards from the existing payment page
- Programmer Bundle (€29.75), Research Bundle (€11.90), Programmer Bundle Lite (€22.61)
- Email input + purchase button on each card
- On purchase: POST email to backend API, then redirect to Payment_path checkout

### 5. Footer
- Email (prompee@tutamail.com) + Telegram links
- Copyright 2026
- Back-to-top link

## Backend (Email Storage)

### Technology Stack
Minimal Node.js + Express + better-sqlite3:

### API Endpoints

| Method | Route       | Purpose                                |
|--------|-------------|----------------------------------------|
| POST   | /api/emails | Store email + product + timestamp      |
| GET    | /api/emails | List all stored emails (admin use)     |

### POST /api/emails body:
```json
{
  "email": "customer@example.com",
  "product": "Programmer Bundle",
  "amount": 29.75
}
```

### Database schema (SQLite):
```sql
CREATE TABLE emails (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL,
  product TEXT NOT NULL,
  amount REAL NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Why SQLite:
- Zero config, single file, no external DB server
- Perfect for this scale (email collection for a small shop)
- Easy to query, backup, or export later

## Purchase Flow

1. User fills email on pricing card → clicks Purchase
2. Frontend validates email format
3. JS sends POST to /api/emails (stores email in SQLite)
4. On success, redirects to Payment_path/index.html with query params (or directly to RiskPay checkout URL with amount/email/currency)
5. Payment_path handles the rest (RiskPay redirect)

## Implementation Steps

1. **Create project directory + structure** — mkdir, copy images
2. **Build css/style.css** — full stylesheet matching existing aesthetic, modernized layout
3. **Build index.html** — semantic HTML, all 5 sections, linked to CSS/JS
4. **Build js/main.js** — scroll nav, email validation, purchase handler (API call + redirect)
5. **Build backend/server.js** — Express server, SQLite setup, API routes, CORS, static file serving
6. **Build backend/package.json** — dependencies (express, better-sqlite3, cors)
7. **Integration test** — run server, verify email storage, verify redirect to payment
8. **Copy PLAN.md to project root**

## Verification

- [ ] npm install in backend/ runs clean
- [ ] node backend/server.js starts without errors
- [ ] Opening http://localhost:3000 serves the main page
- [ ] Clicking Purchase stores email in SQLite and redirects to payment
- [ ] GET /api/emails returns stored emails
- [ ] Page is responsive (mobile/tablet/desktop)
- [ ] All images load correctly
