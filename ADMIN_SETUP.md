# Admin and Projects Setup

The portfolio remains at `/`. The secure admin area is `/admin`, and public project detail pages are `/project/<project-id>`.

## 1. Create Supabase resources

1. Create a project at [supabase.com](https://supabase.com).
2. Open **SQL Editor** and run `supabase/schema.sql`.
3. Run `supabase/seed.sql` once. It preserves the three projects that were already hardcoded in the portfolio.
4. In **Authentication > Providers**, keep Email enabled.
5. In **Storage**, confirm the `project-images` bucket exists and is public for image delivery. Upload and delete permissions are still limited to admins by the storage policies.

## 2. Create the first admin

1. In Supabase, open **Authentication > Users > Add user**.
2. Create the admin email and password. Use that email and password at `/admin`.
3. Copy the new user's UUID.
4. In SQL Editor, run:

```sql
insert into public.admins (user_id)
values ('PASTE_THE_AUTH_USER_UUID_HERE');
```

The admin UI checks this table after login. A valid Supabase user who is not in `public.admins` cannot manage projects.

## 3. Environment variables

Copy `.env.example` to `.env.local` for local development and fill in:

- `VITE_SUPABASE_URL`: Supabase Project URL
- `VITE_SUPABASE_ANON_KEY`: Supabase publishable anon key

Never add a Supabase service-role key to Vite variables or frontend code. The anon key is designed for browser use with RLS enabled.

In Vercel, add the same two variables under **Project Settings > Environment Variables** for **Production**, **Preview**, and **Development**, then redeploy.

## 4. Add the first project

1. Visit `https://YOUR-VERCEL-DOMAIN.vercel.app/admin`.
2. Sign in with the admin user.
3. Click **ADD PROJECT**.
4. Fill in the title, short and full descriptions, category, live URL, tools, date, status, and featured toggle.
5. Upload one or more images.
6. Choose **PUBLISHED** and click **SAVE PROJECT**.

Drafts stay private. Published projects appear in the existing Projects drawer and their detail page opens at `/project/<id>`.

## 5. Deploy from GitHub to Vercel

```bash
git add .
git commit -m "Add Supabase project admin"
git push origin main
```

Import the GitHub repository into Vercel, keep the detected Vite build settings, add the two environment variables, and deploy. `vercel.json` keeps direct requests to `/admin` and `/project/...` inside the React app.

## Files added or changed

- `src/lib/supabase.js`: safe browser Supabase client using Vite environment variables.
- `src/lib/projects.js`: published-project queries and legacy fallback.
- `src/admin/AdminApp.jsx`: login, dashboard, CRUD, image uploads, ordering, featured/status controls.
- `src/ProjectDetail.jsx`: public project detail page.
- `src/main.jsx`: `/admin` and `/project/:id` routing.
- `clevis-portfolio.jsx`: public Projects section now reads published database projects.
- `supabase/schema.sql`: tables, RLS policies, storage bucket, and updated timestamp trigger.
- `supabase/seed.sql`: migration seed for the existing three projects.
- `.env.example`: required variable names.
- `vercel.json`: SPA route rewrites.
