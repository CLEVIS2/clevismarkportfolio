import React, { useEffect, useState } from "react";
import { ArrowLeft, ArrowUpRight } from "lucide-react";
import { fetchProjectById } from "./lib/projects";

export default function ProjectDetail({ projectId }) {
  const [project, setProject] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    fetchProjectById(projectId)
      .then(setProject)
      .catch((loadError) => setError(loadError.message || "Project could not be loaded."));
  }, [projectId]);

  return <main className="clevis-root min-h-screen" style={{ background: "#030014", color: "#fff", fontFamily: "var(--font-sans)" }}>
    <div className="mx-auto max-w-5xl px-6 py-8 md:px-10 md:py-12">
      <a href="/" className="inline-flex items-center gap-2 text-xs tracking-widest opacity-70 hover:opacity-100" style={{ fontFamily: "var(--font-mono)" }}><ArrowLeft size={16} /> BACK TO PORTFOLIO</a>
      {error && <p className="mt-16" style={{ color: "#CCFF00" }}>{error}</p>}
      {!project && !error && <p className="mt-16 opacity-60">Loading project...</p>}
      {project && <article className="mt-16">
        <p className="text-xs tracking-widest" style={{ color: "#CCFF00", fontFamily: "var(--font-mono)" }}>{project.category} {project.project_date ? `/ ${project.project_date}` : ""}</p>
        <h1 className="mt-4 uppercase leading-none" style={{ fontFamily: "var(--font-display)", fontSize: "clamp(58px,12vw,150px)" }}>{project.title}</h1>
        <p className="mt-8 max-w-2xl text-lg leading-relaxed" style={{ color: "rgba(255,255,255,.72)" }}>{project.short_description}</p>
        {project.image_url && <img src={project.image_url} alt={project.title} className="mt-12 max-h-[70vh] w-full object-cover" />}
        {project.image_urls?.length > 0 && <div className="mt-5 grid gap-5 sm:grid-cols-2">{project.image_urls.filter((url) => url !== project.image_url).map((url) => <img key={url} src={url} alt="" className="w-full object-cover" />)}</div>}
        <div className="mt-12 grid gap-10 border-t pt-8 md:grid-cols-[2fr_1fr]" style={{ borderColor: "rgba(255,255,255,.16)" }}>
          <div><p className="text-xs tracking-widest opacity-50" style={{ fontFamily: "var(--font-mono)" }}>ABOUT THE PROJECT</p><p className="mt-4 whitespace-pre-line text-lg leading-relaxed" style={{ color: "rgba(255,255,255,.78)" }}>{project.full_description || project.short_description}</p></div>
          <div className="flex flex-col gap-6"><div><p className="text-xs tracking-widest opacity-50" style={{ fontFamily: "var(--font-mono)" }}>TOOLS</p><p className="mt-2">{project.tools || "Not specified"}</p></div>{project.project_url && <a href={project.project_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 text-xs tracking-widest" style={{ color: "#CCFF00", fontFamily: "var(--font-mono)" }}>OPEN LIVE PROJECT <ArrowUpRight size={15} /></a>}</div>
        </div>
      </article>}
    </div>
  </main>;
}
