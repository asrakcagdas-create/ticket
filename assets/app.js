// /assets/app.js

function $(selector){
  return document.querySelector(selector);
}

function getPIN(){
  return localStorage.getItem("PIN") || "";
}

function doLogout(){
  localStorage.removeItem("PIN");
  location.href="/admin/login.html";
}

async function apiGet(route, params={}){
  const url = new URL("/api/index.php", location.origin);
  url.searchParams.set("r", route);
  for(const [k,v] of Object.entries(params)){
    url.searchParams.set(k, String(v));
  }

  const res = await fetch(url.toString(), {
    method: "GET",
    headers: {
      "X-PIN": getPIN()
    }
  });

  const txt = await res.text();
  try { return JSON.parse(txt); }
  catch(e){ return { ok:false, error:"bad_json", detail: txt }; }
}

async function apiPost(route, body={}){
  const url = new URL("/api/index.php?r="+encodeURIComponent(route), location.origin);

  const res = await fetch(url.toString(), {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-PIN": getPIN()
    },
    body: JSON.stringify(body)
  });

  const txt = await res.text();
  try { return JSON.parse(txt); }
  catch(e){ return { ok:false, error:"bad_json", detail: txt }; }
}

async function whoAmI(){
  const r = await apiGet("auth.me");
  if(!r.ok){
    location.href = "/admin/login.html";
    return null;
  }
  return r;
}

function esc(s){
  return String(s ?? "")
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}
