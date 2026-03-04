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
