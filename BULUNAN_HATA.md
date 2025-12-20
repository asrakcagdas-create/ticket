# Bulunan Hata (Error Found)

## 🔴 Ana Hata / Main Error

Script'te **AutoHotkey v1 sözdizimi hatası** bulundu.

**The script had an AutoHotkey v1 syntax error.**

---

## Sorun / Problem

`IniRead` ve `IniWrite` **fonksiyon** gibi kullanılmış, ama AutoHotkey v1'de bunlar **komut**.

`IniRead` and `IniWrite` were used as **functions**, but in AutoHotkey v1 they are **commands**.

---

## Yanlış Kod / Wrong Code ❌

```ahk
cols := IniRead(cfgFile, "GRID", prefix . "COLS", 7) + 0
x := IniRead(cfgFile, "P", key . "_X", "")
IniWrite, %x%, %cfgFile%, P, %key%_X
```

---

## Doğru Kod / Correct Code ✅

```ahk
colsKey := prefix . "COLS"
IniRead, cols, %cfgFile%, GRID, %colsKey%, 7
cols := cols + 0

xKey := key . "_X"
IniRead, x, %cfgFile%, P, %xKey%

IniWrite, %x%, %cfgFile%, P, %xKey%
```

---

## Neden Hata? / Why Error?

AutoHotkey v1'de:
- **Komut sözdizimi**: `IniRead, OutputVar, Filename, Section, Key, Default`
- **Fonksiyon sözdizimi DEĞİL**: `var := IniRead(file, section, key, default)` ❌

In AutoHotkey v1:
- **Command syntax**: `IniRead, OutputVar, Filename, Section, Key, Default`
- **NOT function syntax**: `var := IniRead(file, section, key, default)` ❌

---

## Düzeltilen Yerler / Fixed Locations

1. ✅ `ClickTableByArea()` fonksiyonu - `IniRead` kullanımı
2. ✅ `SavePoint()` fonksiyonu - `IniWrite` kullanımı  
3. ✅ `HasPoint()` fonksiyonu - `IniRead` kullanımı
4. ✅ `LoadPoint()` fonksiyonu - `IniRead` kullanımı

---

## Sonuç / Result

✅ Düzeltilmiş script: `lotus_pecas_auto.ahk`

✅ Fixed script: `lotus_pecas_auto.ahk`

📄 Detaylı açıklama: `HATA_DUZELTMELERI.md` (Türkçe) ve `LOTUS_AUTO_FIXES.md` (English)

📄 Detailed explanation: `HATA_DUZELTMELERI.md` (Turkish) and `LOTUS_AUTO_FIXES.md` (English)
