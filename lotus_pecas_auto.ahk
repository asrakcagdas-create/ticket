; =========================
; Lotus Pecas Auto Input - AutoHotkey v1
; =========================
#NoEnv
#SingleInstance Force
SendMode Input
SetTitleMatchMode, 2
CoordMode, Mouse, Window

orderFile := "C:\Orders\order.json"
cfgFile   := "C:\Orders\lotus_points.ini"
lotusTitle := "Lotus"

watching := 0
lastSig := ""

; ---------- KALİBRASYON ----------
^!r::SavePoint("AREA_RAUCHER")
^!n::SavePoint("AREA_NICHT")
^!t::SavePoint("AREA_TERASSE")

^!1::SavePoint("GRID_R_T1")
^!2::SavePoint("GRID_R_T2")
^!3::SavePoint("GRID_R_ROW2")

^!4::SavePoint("GRID_N_T1")
^!5::SavePoint("GRID_N_T2")
^!6::SavePoint("GRID_N_ROW2")

^!7::SavePoint("GRID_T_T1")
^!8::SavePoint("GRID_T_T2")
^!9::SavePoint("GRID_T_ROW2")

^!s::SavePoint("SEARCH_BTN")
^!o::SavePoint("OK_BTN")
^!b::SavePoint("SEND_BTN")

F9::HandleOrder()
F10::ToggleWatch()
return

; =========================
; WATCHER
; =========================
ToggleWatch(){
  global watching
  if (watching){
    SetTimer, WatchTick, Off
    watching := 0
    TrayTip, Lotus Auto, Durduruldu, 1
  } else {
    SetTimer, WatchTick, 700
    watching := 1
    TrayTip, Lotus Auto, order.json izleniyor (F10 ile kapat), 3
  }
}

WatchTick:
  global orderFile, lastSig
  if !FileExist(orderFile)
    return
  FileGetTime, t, %orderFile%, M
  FileGetSize, s, %orderFile%
  sig := t . "|" . s
  if (sig = lastSig)
    return
  lastSig := sig
  HandleOrder()
return

; =========================
; MAIN
; =========================
HandleOrder(){
  global orderFile, lotusTitle
  if !FileExist(orderFile)
    return

  FileRead, json, %orderFile%
  json := Trim(json)
  if (json = "")
    return

  ; ---- area ----
  area := "raucher"
  if RegExMatch(json, "s)""area""\s*:\s*""([^""]+)""", am)
    area := LCase(am1)

  ; ---- table ----
  if !RegExMatch(json, "s)""table""\s*:\s*(\d+)", tm){
    MsgBox, 48, Lotus Auto, JSON table okunamadı.`n`nTam içerik:`n %json%
    return
  }
  table := tm1 + 0

  ; ---- items ----
  items := []
  pos := 1
  Loop {
    if !RegExMatch(json, "s)""name""\s*:\s*""([^""]+)""\s*,\s*""qty""\s*:\s*(\d+)", mm, pos)
      break
    name := mm1
    qty  := mm2 + 0
    items.Push({name:name, qty:qty})
    pos := mmPos + StrLen(mm)
  }

  if (items.Length() = 0){
    MsgBox, 48, Lotus Auto, JSON'da ürün bulunamadı.`n`nTam içerik:`n %json%
    return
  }

  ; ---- Lotus penceresi ----
  if !WinExist(lotusTitle){
    MsgBox, 48, Lotus Auto, Lotus penceresi bulunamadı. Program açık mı?
    return
  }
  WinActivate, %lotusTitle%
  Sleep, 250

  ; ---- Bölüm seç ----
  if !SelectArea(area)
    return
  Sleep, 250

  ; ---- Masa seç ----
  if !ClickTableByArea(area, table)
    return
  Sleep, 300

  ; ---- Ürün ekleme kalibrasyonu ----
  if !HasPoint("SEARCH_BTN") || !HasPoint("OK_BTN"){
    MsgBox, 48, Lotus Auto, Ürün arama kalibrasyonu eksik.`nCtrl+Alt+S (🔍) ve Ctrl+Alt+O (✅) ile kaydedin.
    return
  }

  for i, it in items {
    AddItem(it.name, it.qty)
    Sleep, 200
  }

  ; ---- Gönder ----
  if HasPoint("SEND_BTN"){
    p := LoadPoint("SEND_BTN")
    Click, % p.x, % p.y
    Sleep, 300
  }
}

; =========================
; AREA SELECT
; =========================
SelectArea(area){
  area := LCase(area)
  if (InStr(area, "nich") || InStr(area, "non") || InStr(area, "nicht"))
    key := "AREA_NICHT"
  else if (InStr(area, "tera") || InStr(area, "terr"))
    key := "AREA_TERASSE"
  else
    key := "AREA_RAUCHER"

  if !HasPoint(key){
    MsgBox, 48, Lotus Auto, Bölüm butonu kalibrasyonu eksik: %key% `nCtrl+Alt+R / N / T tuşlarıyla kaydedin.
    return 0
  }
  p := LoadPoint(key)
  Click, % p.x, % p.y
  return 1
}

; =========================
; TABLE CLICK
; =========================
ClickTableByArea(area, table){
  global cfgFile
  area := LCase(area)

  if (InStr(area, "nich") || InStr(area, "non") || InStr(area, "nicht")){
    prefix := "GRID_N_"
    start  := 29
  } else if (InStr(area, "tera") || InStr(area, "terr")){
    prefix := "GRID_T_"
    start  := 47
  } else {
    prefix := "GRID_R_"
    start  := 1
  }

  t1k := prefix . "T1"
  t2k := prefix . "T2"
  r2k := prefix . "ROW2"

  if !HasPoint(t1k) || !HasPoint(t2k) || !HasPoint(r2k){
    MsgBox, 48, Lotus Auto, Masa grid kalibrasyonu eksik: %prefix% `nİlk, yan ve alt satır noktalarını kaydedin.
    return 0
  }

  p1 := LoadPoint(t1k)
  p2 := LoadPoint(t2k)
  pr := LoadPoint(r2k)

  dx := (p2.x - p1.x)
  dy := (pr.y - p1.y)

  ; FIXED: IniRead is a command in AHK v1, not a function
  colsKey := prefix . "COLS"
  IniRead, cols, %cfgFile%, GRID, %colsKey%, 7
  cols := cols + 0

  idx  := table - start

  if (idx < 0){
    MsgBox, 48, Lotus Auto, Masa numarası %table% bu bölüm için çok küçük. Başlangıç: %start%
    return 0
  }

  r := Floor(idx / cols)
  c := Mod(idx, cols)
  x := Round(p1.x + c * dx)
  y := Round(p1.y + r * dy)

  Click, %x%, %y%
  return 1
}

; =========================
; ADD ITEM
; =========================
AddItem(name, qty){
  p  := LoadPoint("SEARCH_BTN")
  ok := LoadPoint("OK_BTN")

  Loop, %qty% {
    Click, % p.x, % p.y
    Sleep, 220
    Send, ^a
    Sleep, 60
    SendRaw, %name%
    Sleep, 120
    Send, {Enter}
    Sleep, 220
    Click, % ok.x, % ok.y
    Sleep, 220
  }
}

; =========================
; POINTS (INI)
; =========================
SavePoint(key){
  global cfgFile
  MouseGetPos, x, y
  ; FIXED: IniWrite is a command in AHK v1, not a function
  xKey := key . "_X"
  yKey := key . "_Y"
  IniWrite, %x%, %cfgFile%, P, %xKey%
  IniWrite, %y%, %cfgFile%, P, %yKey%
  TrayTip, Lotus Auto, Kaydedildi: %key% = %x%,%y%, 2
}

HasPoint(key){
  global cfgFile
  ; FIXED: IniRead is a command in AHK v1, not a function
  xKey := key . "_X"
  yKey := key . "_Y"
  IniRead, x, %cfgFile%, P, %xKey%
  IniRead, y, %cfgFile%, P, %yKey%
  ; Check for ERROR (returned when key not found) or empty string
  return (x != "" && x != "ERROR" && y != "" && y != "ERROR")
}

LoadPoint(key){
  global cfgFile
  ; FIXED: IniRead is a command in AHK v1, not a function
  xKey := key . "_X"
  yKey := key . "_Y"
  IniRead, x, %cfgFile%, P, %xKey%, 0
  IniRead, y, %cfgFile%, P, %yKey%, 0
  ; Convert to numeric, handling ERROR case (converts to 0)
  x := (x = "ERROR") ? 0 : (x + 0)
  y := (y = "ERROR") ? 0 : (y + 0)
  return {x:x, y:y}
}

LCase(s){
  StringLower, out, s
  return out
}
