import { createClient } from "@supabase/supabase-js";

const supabaseUrl = import.meta.env.https://dbievfcifwvpkazstpof.supabase.co/rest/v1/;
const supabaseAnonKey = import.meta.env.sb_publishable_d9S2ggDM61GpZGBCYeJMtA_jqfaqVmQ;

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
