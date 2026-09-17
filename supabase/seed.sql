-- Run once after schema.sql. This preserves the three projects already shown by the portfolio.
insert into public.projects (
  title, short_description, full_description, category, project_url,
  featured, status, sort_order
)
select * from (
  values
    ('Your Website Project', 'Add your website project here so clients can explore the live experience.', '', 'WEBSITE', '', false, 'published', 1),
    ('Your App Project', 'Add your app prototype, case study, or app store link here.', '', 'APP DESIGN', '', false, 'published', 2),
    ('Your Poster Project', 'Add a hosted poster image or project page here for clients to view.', '', 'POSTER DESIGN', '', false, 'published', 3)
) as initial_projects(title, short_description, full_description, category, project_url, featured, status, sort_order)
where not exists (select 1 from public.projects);
