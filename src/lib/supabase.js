import { createClient } from "@supabase/supabase-js";

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY;
console.log("Supabase URL:", import.meta.env.VITE_SUPABASE_URL)
console.log("Supabase Key:", import.meta.env.VITE_SUPABASE_ANON_KEY?.slice(0,10) + "...")

const hasValidConfig =
  typeof supabaseUrl === "string" &&
  supabaseUrl.startsWith("https://ybvnjbbcuqsebjfleoxs.supabase.co/rest/v1/") &&
  !supabaseUrl.includes("clevismarkportfolio.vercel.app") &&
  typeof supabaseAnonKey === "string" &&
  supabaseAnonKey.length > 20 &&
  !supabaseAnonKey.includes("your-publishable");

export const supabase = hasValidConfig
  ? createClient(supabaseUrl, supabaseAnonKey, {
      auth: { persistSession: true, autoRefreshToken: true, detectSessionInUrl: true },
    })
  : null;

export const isSupabaseConfigured = Boolean(supabase);
