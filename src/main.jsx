import React from "react";
import { createRoot } from "react-dom/client";
import App from "../clevis-portfolio.jsx";
import AdminApp from "./admin/AdminApp.jsx";
import ProjectDetail from "./ProjectDetail.jsx";
import "./styles.css";

const path = window.location.pathname;
const projectMatch = path.match(/^\/project\/([^/]+)\/?$/);
const Root = path === "/admin" || path.startsWith("/admin/")
  ? AdminApp
  : projectMatch
    ? () => <ProjectDetail projectId={decodeURIComponent(projectMatch[1])} />
    : App;

createRoot(document.getElementById("root")).render(
  <React.StrictMode>
    <Root />
  </React.StrictMode>,
);
