import { createClient } from "@supabase/supabase-js";

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY;

const hasValidConfig =
  typeof supabaseUrl === "string" &&
  supabaseUrl.startsWith("https://") &&
  !supabaseUrl.includes("your-project") &&
  typeof supabaseAnonKey === "string" &&
  supabaseAnonKey.length > 20 &&
  !supabaseAnonKey.includes("your-publishable");

export const supabase = hasValidConfig
  ? createClient(supabaseUrl, supabaseAnonKey, {
      auth: { persistSession: true, autoRefreshToken: true, detectSessionInUrl: true },
    })
  : null;

export const isSupabaseConfigured = Boolean(supabase);
