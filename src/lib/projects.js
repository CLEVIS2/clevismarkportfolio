import { supabase } from "./supabase";

export const LEGACY_PROJECTS = [
  {
    id: "legacy-01",
    number: "01",
    title: "Your Website Project",
    category: "WEBSITE",
    short_description: "Add your website project here so clients can explore the live experience.",
    full_description: "",
    image_url: "",
    image_urls: [],
    project_url: "",
    tools: "",
    project_date: "",
    featured: false,
    status: "published",
    sort_order: 1,
  },
  {
    id: "legacy-02",
    number: "02",
    title: "Your App Project",
    category: "APP DESIGN",
    short_description: "Add your app prototype, case study, or app store link here.",
    full_description: "",
    image_url: "",
    image_urls: [],
    project_url: "",
    tools: "",
    project_date: "",
    featured: false,
    status: "published",
    sort_order: 2,
  },
  {
    id: "legacy-03",
    number: "03",
    title: "Your Poster Project",
    category: "POSTER DESIGN",
    short_description: "Add a hosted poster image or project page here for clients to view.",
    full_description: "",
    image_url: "",
    image_urls: [],
    project_url: "",
    tools: "",
    project_date: "",
    featured: false,
    status: "published",
    sort_order: 3,
  },
];

export function normalizeProject(project, index = 0) {
  return {
    ...project,
    number: String(index + 1).padStart(2, "0"),
    short_description: project.short_description || project.description || "",
    full_description: project.full_description || "",
    image_urls: project.image_urls || [],
    project_url: project.project_url || project.url || "",
    tools: project.tools || "",
    project_date: project.project_date || "",
    featured: Boolean(project.featured),
    status: project.status || "published",
    sort_order: project.sort_order ?? index + 1,
  };
}

export async function fetchPublishedProjects() {
  if (!supabase) return LEGACY_PROJECTS;
  const projects = [];
  for (let offset = 0; ; offset += 1000) {
    const { data, error } = await supabase
      .from("projects")
      .select("*")
      .eq("status", "published")
      .order("sort_order", { ascending: true })
      .order("created_at", { ascending: false })
      .range(offset, offset + 999);
    if (error) throw error;
    projects.push(...(data || []));
    if (!data || data.length < 1000) break;
  }
  return projects.map(normalizeProject);
}

export async function fetchProjectById(id) {
  if (!supabase) return LEGACY_PROJECTS.find((project) => project.id === id) || null;
  const { data, error } = await supabase.from("projects").select("*").eq("id", id).eq("status", "published").single();
  if (error) throw error;
  return normalizeProject(data);
}
