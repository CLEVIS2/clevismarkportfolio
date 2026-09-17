import React, { useEffect, useState } from "react";
import { ArrowDown, ArrowLeft, ArrowUp, LogOut, Pencil, Plus, Save, Trash2, Upload, X } from "lucide-react";
import { supabase } from "../lib/supabase";

const COLORS = { bg: "#030014", panel: "#11111b", lime: "#CCFF00", white: "#FFFFFF", muted: "rgba(255,255,255,0.58)" };
const EMPTY_PROJECT = {
  title: "", short_description: "", full_description: "", category: "WEBSITE", image_url: "", image_urls: [],
  project_url: "", tools: "", project_date: "", featured: false, status: "draft", sort_order: 0,
};

function AdminStyles() {
  return <style>{`
    .admin-root{min-height:100dvh;background:${COLORS.bg};color:${COLORS.white};font-family:var(--font-sans);}
    .admin-shell{width:min(1180px,100%);margin:0 auto;padding:24px clamp(18px,4vw,56px) 64px;}
    .admin-panel{background:${COLORS.panel};border:1px solid rgba(255,255,255,.12);}
    .admin-input{width:100%;background:transparent;border:1px solid rgba(255,255,255,.18);color:${COLORS.white};padding:12px 13px;outline:none;font:inherit;border-radius:3px;}
    .admin-input:focus{border-color:${COLORS.lime};}
    .admin-input::placeholder{color:rgba(255,255,255,.35);}
    .admin-label{display:block;color:${COLORS.muted};font:11px var(--font-mono);letter-spacing:.12em;margin-bottom:8px;text-transform:uppercase;}
    .admin-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid rgba(255,255,255,.2);padding:11px 15px;font:11px var(--font-mono);letter-spacing:.1em;text-transform:uppercase;cursor:pointer;transition:opacity .2s,border-color .2s;}
    .admin-button:hover{opacity:.8;border-color:${COLORS.lime};}
    .admin-button-primary{background:${COLORS.lime};border-color:${COLORS.lime};color:#000;}
    .admin-button-danger{color:#ff7d7d;border-color:rgba(255,125,125,.4);}
    .admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;}
    @media(max-width:700px){.admin-grid{grid-template-columns:1fr}.admin-header{align-items:flex-start;flex-direction:column}.admin-table-row{grid-template-columns:1fr!important;gap:12px!important}.admin-table-actions{justify-content:flex-start!important}}
  `}</style>;
}

function AuthScreen({ onLogin }) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  const submit = async (event) => {
    event.preventDefault();
    setBusy(true); setError("");
    const { data, error: loginError } = await supabase.auth.signInWithPassword({ email, password });
    if (loginError) setError(loginError.message);
    else onLogin(data.session);
    setBusy(false);
  };

  return <div className="admin-root flex min-h-screen items-center justify-center px-5"><AdminStyles /><form onSubmit={submit} className="admin-panel w-full max-w-md p-7 md:p-10">
    <p className="text-xs tracking-widest" style={{ color: COLORS.lime, fontFamily: "var(--font-mono)" }}>CLEVIS / ADMIN</p>
    <h1 className="mt-4 uppercase leading-none" style={{ fontFamily: "var(--font-display)", fontSize: "clamp(48px,10vw,82px)" }}>Sign in.</h1>
    <p className="mt-4 leading-relaxed" style={{ color: COLORS.muted }}>Manage the work shown in your public Projects section.</p>
    <div className="mt-8 flex flex-col gap-5">
      <label><span className="admin-label">EMAIL / USERNAME</span><input className="admin-input" type="email" autoComplete="email" value={email} onChange={(event) => setEmail(event.target.value)} required /></label>
      <label><span className="admin-label">PASSWORD</span><input className="admin-input" type="password" autoComplete="current-password" value={password} onChange={(event) => setPassword(event.target.value)} required /></label>
      {error && <p role="alert" className="text-sm" style={{ color: "#ff8b8b" }}>{error}</p>}
      <button className="admin-button admin-button-primary w-full" disabled={busy}>{busy ? "SIGNING IN..." : "SIGN IN"}</button>
    </div>
  </form></div>;
}

function ProjectEditor({ project, onSave, onCancel, onDelete, onUpload }) {
  const [draft, setDraft] = useState(project);
  const [busy, setBusy] = useState(false);
  const set = (field, value) => setDraft((current) => ({ ...current, [field]: value }));
  const save = async (event) => { event.preventDefault(); setBusy(true); await onSave(draft); setBusy(false); };
  const upload = async (event) => {
    const files = Array.from(event.target.files || []);
    if (!files.length) return;
    setBusy(true);
    const urls = await onUpload(files);
    setDraft((current) => ({ ...current, image_url: current.image_url || urls[0] || "", image_urls: [...(current.image_urls || []), ...urls] }));
    setBusy(false);
  };

  return <form onSubmit={save} className="admin-panel p-5 md:p-7">
    <div className="mb-6 flex items-start justify-between gap-4"><div><p className="text-xs tracking-widest" style={{ color: COLORS.lime, fontFamily: "var(--font-mono)" }}>{project.id ? "EDIT PROJECT" : "ADD PROJECT"}</p><h2 className="mt-2 text-3xl uppercase" style={{ fontFamily: "var(--font-display)" }}>{project.title || "New project"}</h2></div><button type="button" className="p-2" onClick={onCancel} aria-label="Close editor"><X size={20} /></button></div>
    <div className="admin-grid">
      <label><span className="admin-label">PROJECT TITLE</span><input className="admin-input" value={draft.title} onChange={(event) => set("title", event.target.value)} required /></label>
      <label><span className="admin-label">CATEGORY</span><select className="admin-input" value={draft.category} onChange={(event) => set("category", event.target.value)}>{["WEBSITE", "APP DESIGN", "POSTER DESIGN", "UI/UX DESIGN", "GRAPHIC DESIGN", "OTHER"].map((category) => <option key={category} value={category} style={{ background: COLORS.panel }}>{category}</option>)}</select></label>
      <label className="md:col-span-2"><span className="admin-label">SHORT DESCRIPTION</span><input className="admin-input" value={draft.short_description} onChange={(event) => set("short_description", event.target.value)} required /></label>
      <label className="md:col-span-2"><span className="admin-label">FULL PROJECT DESCRIPTION</span><textarea className="admin-input" rows={6} value={draft.full_description} onChange={(event) => set("full_description", event.target.value)} /></label>
      <label><span className="admin-label">PROJECT LINK / URL</span><input className="admin-input" type="url" placeholder="https://..." value={draft.project_url} onChange={(event) => set("project_url", event.target.value)} /></label>
      <label><span className="admin-label">TOOLS / SOFTWARE</span><input className="admin-input" placeholder="Figma, Photoshop..." value={draft.tools} onChange={(event) => set("tools", event.target.value)} /></label>
      <label><span className="admin-label">DATE</span><input className="admin-input" type="date" value={draft.project_date || ""} onChange={(event) => set("project_date", event.target.value)} /></label>
      <label><span className="admin-label">STATUS</span><select className="admin-input" value={draft.status} onChange={(event) => set("status", event.target.value)}><option value="draft" style={{ background: COLORS.panel }}>DRAFT</option><option value="published" style={{ background: COLORS.panel }}>PUBLISHED</option></select></label>
      <div className="md:col-span-2"><span className="admin-label">PROJECT IMAGES</span><label className="admin-button w-full"><Upload size={15} /> UPLOAD IMAGE(S)<input className="hidden" type="file" accept="image/*" multiple onChange={upload} /></label>{draft.image_urls?.length > 0 && <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">{draft.image_urls.map((url) => <img key={url} src={url} alt="Project preview" className="aspect-square w-full object-cover" />)}</div>}</div>
      <label className="flex items-center gap-3 md:col-span-2"><input type="checkbox" checked={draft.featured} onChange={(event) => set("featured", event.target.checked)} /> <span className="text-sm">FEATURED PROJECT</span></label>
    </div>
    <div className="mt-7 flex flex-wrap justify-between gap-3"><div>{project.id && <button type="button" className="admin-button admin-button-danger" onClick={() => onDelete(project.id)}><Trash2 size={15} /> DELETE</button>}</div><div className="flex gap-3"><button type="button" className="admin-button" onClick={onCancel}>CANCEL</button><button className="admin-button admin-button-primary" disabled={busy}><Save size={15} /> {busy ? "SAVING..." : "SAVE PROJECT"}</button></div></div>
  </form>;
}

function AdminDashboard({ session, onLogout }) {
  const [projects, setProjects] = useState([]);
  const [editor, setEditor] = useState(null);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState("");
  const [allowed, setAllowed] = useState(false);

  const load = async () => {
    try {
      const { data: admin, error: adminError } = await supabase
        .from("admins")
        .select("user_id")
        .eq("user_id", session.user.id)
        .maybeSingle();
      if (adminError) throw adminError;
      if (!admin) { setAllowed(false); setLoading(false); return; }
      setAllowed(true);
      const allProjects = [];
      for (let offset = 0; ; offset += 1000) {
        const { data, error } = await supabase
          .from("projects")
          .select("*")
          .order("sort_order", { ascending: true })
          .order("created_at", { ascending: false })
          .range(offset, offset + 999);
        if (error) throw error;
        allProjects.push(...(data || []));
        if (!data || data.length < 1000) break;
      }
      setProjects(allProjects);
    } catch (loadError) {
      setMessage(loadError.message || "Could not load the admin dashboard.");
    }
    setLoading(false);
  };
  useEffect(() => { load(); }, [session.user.id]);

  const uploadImages = async (files) => {
    const urls = [];
    for (const file of files) {
      const path = `${session.user.id}/${crypto.randomUUID()}-${file.name.replace(/[^a-zA-Z0-9._-]/g, "-")}`;
      const { error } = await supabase.storage.from("project-images").upload(path, file, { upsert: false, contentType: file.type });
      if (error) { setMessage(error.message); continue; }
      const { data } = supabase.storage.from("project-images").getPublicUrl(path);
      urls.push(data.publicUrl);
    }
    return urls;
  };
  const saveProject = async (project) => {
    const payload = { ...project, id: undefined, created_at: undefined, updated_at: undefined };
    delete payload.id; delete payload.created_at; delete payload.updated_at;
    if (!project.id) payload.sort_order = projects.length ? Math.max(...projects.map((item) => item.sort_order || 0)) + 1 : 1;
    const response = project.id ? await supabase.from("projects").update(payload).eq("id", project.id) : await supabase.from("projects").insert(payload);
    if (response.error) setMessage(response.error.message); else { setMessage("Project saved."); setEditor(null); await load(); }
  };
  const deleteProject = async (id) => {
    if (!window.confirm("Delete this project permanently?")) return;
    const { error } = await supabase.from("projects").delete().eq("id", id);
    if (error) setMessage(error.message); else { setEditor(null); await load(); }
  };
  const moveProject = async (index, direction) => {
    const target = index + direction;
    if (target < 0 || target >= projects.length) return;
    const current = projects[index]; const next = projects[target];
    await supabase.from("projects").update({ sort_order: next.sort_order }).eq("id", current.id);
    await supabase.from("projects").update({ sort_order: current.sort_order }).eq("id", next.id);
    await load();
  };

  if (loading) return <div className="admin-root"><AdminStyles /><div className="admin-shell">Loading dashboard...</div></div>;
  if (!allowed) return <div className="admin-root"><AdminStyles /><div className="admin-shell"><p className="text-lg">This account is not authorised as an admin.</p><button className="admin-button mt-6" onClick={onLogout}>LOG OUT</button></div></div>;
  return <div className="admin-root"><AdminStyles /><div className="admin-shell">
    <header className="admin-header mb-10 flex items-center justify-between gap-5"><div><p className="text-xs tracking-widest" style={{ color: COLORS.lime, fontFamily: "var(--font-mono)" }}>CLEVIS / ADMIN</p><h1 className="mt-2 text-5xl uppercase" style={{ fontFamily: "var(--font-display)" }}>Dashboard.</h1></div><button className="admin-button" onClick={onLogout}><LogOut size={15} /> LOG OUT</button></header>
    {message && <div className="mb-6 border p-4 text-sm" style={{ borderColor: COLORS.lime }}>{message}</div>}
    {editor ? <ProjectEditor project={editor} onSave={saveProject} onCancel={() => setEditor(null)} onDelete={deleteProject} onUpload={uploadImages} /> : <>
      <div className="mb-6 grid gap-4 sm:grid-cols-3"><div className="admin-panel p-5"><p className="admin-label">TOTAL PROJECTS</p><p className="text-4xl" style={{ fontFamily: "var(--font-display)" }}>{projects.length}</p></div><div className="admin-panel p-5"><p className="admin-label">PUBLISHED</p><p className="text-4xl" style={{ fontFamily: "var(--font-display)" }}>{projects.filter((project) => project.status === "published").length}</p></div><div className="admin-panel p-5"><p className="admin-label">FEATURED</p><p className="text-4xl" style={{ fontFamily: "var(--font-display)" }}>{projects.filter((project) => project.featured).length}</p></div></div>
      <div className="mb-5 flex items-center justify-between gap-4"><div><h2 className="text-3xl uppercase" style={{ fontFamily: "var(--font-display)" }}>Projects</h2><p className="mt-2 text-xs tracking-widest" style={{ color: COLORS.muted, fontFamily: "var(--font-mono)" }}>NO PROJECT LIMIT</p></div><button className="admin-button admin-button-primary" onClick={() => setEditor({ ...EMPTY_PROJECT })}><Plus size={15} /> ADD PROJECT</button></div>
      <div className="admin-panel divide-y" style={{ borderColor: "rgba(255,255,255,.12)" }}>{projects.map((project, index) => <div className="admin-table-row grid grid-cols-[1fr_auto] items-center gap-5 p-5" key={project.id}><div className="flex items-center gap-4">{project.image_url ? <img src={project.image_url} alt="" className="h-16 w-16 object-cover" /> : <div className="h-16 w-16" style={{ background: "rgba(204,255,0,.12)" }} />}<div><p className="text-xs tracking-widest" style={{ color: COLORS.lime, fontFamily: "var(--font-mono)" }}>{project.category} / {project.status}</p><h3 className="mt-1 text-xl">{project.title}</h3><p className="mt-1 text-sm" style={{ color: COLORS.muted }}>{project.short_description}</p></div></div><div className="admin-table-actions flex flex-wrap justify-end gap-2"><button className="admin-button" onClick={() => moveProject(index, -1)} aria-label="Move project up"><ArrowUp size={15} /></button><button className="admin-button" onClick={() => moveProject(index, 1)} aria-label="Move project down"><ArrowDown size={15} /></button><button className="admin-button" onClick={() => setEditor(project)}><Pencil size={15} /> EDIT</button></div></div>)}</div>
    </>}
  </div></div>;
}

export default function AdminApp() {
  const [session, setSession] = useState(null);
  const [ready, setReady] = useState(false);
  useEffect(() => {
    if (!supabase) { setReady(true); return undefined; }
    supabase.auth.getSession().then(({ data, error }) => {
      if (error) setSession(null);
      else setSession(data.session);
      setReady(true);
    });
    const { data: listener } = supabase.auth.onAuthStateChange((_event, nextSession) => setSession(nextSession));
    return () => listener.subscription.unsubscribe();
  }, []);
  const logout = async () => { await supabase?.auth.signOut(); setSession(null); };
  if (!supabase) return <div className="admin-root"><AdminStyles /><div className="admin-shell"><h1 className="text-4xl">Supabase is not configured.</h1><p className="mt-4" style={{ color: COLORS.muted }}>Add VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY to your Vercel environment variables.</p></div></div>;
  if (!ready) return <div className="admin-root"><AdminStyles /><div className="admin-shell">Loading...</div></div>;
  return session ? <AdminDashboard session={session} onLogout={logout} /> : <AuthScreen onLogin={setSession} />;
}
