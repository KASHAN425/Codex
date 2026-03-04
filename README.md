# Loomi Crochet Store (Vercel Ready)

A complete Loomi-themed handcraft e-commerce starter using HTML, CSS, Bootstrap, JavaScript, jQuery, PHP, and MySQL.

## Included functionality
- Loomi-themed storefront design (soft handmade brand style)
- Separate user login/register and separate admin login
- Role-based authorization (admin/user)
- Admin-only product add, update, and delete
- User shopping cart (localStorage)
- User checkout with shipping/phone details
- Order creation with stock deduction
- User order history endpoint
- MySQL schema and Loomi-style starter seed data

## Deploy on Vercel
- Uses `@vercel/php` for backend endpoints in `/api`
- Static frontend served from `index.html`
- Set environment variables from `.env.example`
- Import `sql/schema.sql` into your MySQL database

## Default admin account
- Email: `admin@store.com`
- Password: `Admin@123`


## Vercel deployment checklist
- Set project **Root Directory** to this repository root (where `index.html` exists).
- Add environment variables in Vercel project settings: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.
- Use an external MySQL service (Vercel does not provide local MySQL).
- After first deploy, open `/api/products.php` directly to confirm API runtime works.


## Troubleshooting (Vercel 404 / 500)
- If root URL shows `404: NOT_FOUND`, keep `vercel.json` in project root and redeploy.
- If `/api/categories.php` or `/api/products.php` returns 500, verify Vercel env vars: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.
- Public catalog endpoints now return an empty list + warning JSON when DB is unavailable, so the frontend can still render.
