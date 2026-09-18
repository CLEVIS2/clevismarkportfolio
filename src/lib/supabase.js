import { createClient } from "@supabase/supabase-js";

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL?.trim();
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY?.trim();

const isValidSupabaseUrl = (value) => {
  if (typeof value !== "string" || value.includes("your-project")) return false;

  try {
    const url = new URL(value);
    return url.protocol === "https:" && Boolean(url.hostname);
  } catch {
    return false;
  }
};

const isValidSupabaseKey = (value) =>
  typeof value === "string" &&
  value.length > 20 &&
  !value.includes("your-publishable") &&
  !value.includes("your-anon");

const hasValidConfig =
  isValidSupabaseUrl(supabaseUrl) && isValidSupabaseKey(supabaseAnonKey);

export const supabase = hasValidConfig
  ? createClient(supabaseUrl, supabaseAnonKey, {
      auth: {
        persistSession: true,
        autoRefreshToken: true,
        detectSessionInUrl: true,
      },
    })
  : null;

export const isSupabaseConfigured = Boolean(supabase);

if (!hasValidConfig && import.meta.env.DEV) {
  console.warn(
    "Supabase is not configured. Set VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY in Vercel and redeploy."
  );
}
