# Clevis Mark Portfolio

A Vite and React portfolio with a Supabase-backed project manager.

## Run locally

```bash
npm install
copy .env.example .env.local
npm run dev
```

Open `http://localhost:5173/` for the portfolio and `http://localhost:5173/admin` for project management.

Before using the admin page, add the Supabase URL and publishable key to `.env.local`, run `supabase/schema.sql`, and create an Auth user linked from `public.admins`. Full setup details are in [ADMIN_SETUP.md](ADMIN_SETUP.md).

## Build

```bash
npm run build
npm run preview
```

## Routes

- `/` - public portfolio
- `/admin` - protected project dashboard
- `/project/<id>` - published project details

## Deployment

The project is configured for Vercel with the SPA rewrites in `vercel.json`. Add `VITE_SUPABASE_URL` and `VITE_SUPABASE_ANON_KEY` to the Vercel environment variables for each deployment environment before deploying.

Never commit `.env.local` or a Supabase service-role key.