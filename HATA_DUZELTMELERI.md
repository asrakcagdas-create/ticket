# Lotus Pecas Auto Input - Hata Düzeltmeleri

## Bulunan ve Düzeltilen Hatalar

### Ana Hata: Yanlış IniRead/IniWrite Sözdizimi

Orijinal script'te `IniRead` ve `IniWrite` **fonksiyon sözdizimi** ile kullanılmış, ancak **AutoHotkey v1**'de bunlar **komuttur**, fonksiyon değildir.

### Düzeltilen Spesifik Hatalar:

#### 1. **ClickTableByArea Fonksiyonu** (~190. satır)
**❌ YANLIŞ (Fonksiyon Sözdizimi):**
```ahk
cols := IniRead(cfgFile, "GRID", prefix . "COLS", 7) + 0
```

**✅ DÜZELTİLDİ (Komut Sözdizimi):**
```ahk
colsKey := prefix . "COLS"
IniRead, cols, %cfgFile%, GRID, %colsKey%, 7
cols := cols + 0
```

#### 2. **SavePoint Fonksiyonu** (~250. satır)
**❌ YANLIŞ:**
```ahk
IniWrite, %x%, %cfgFile%, P, %key%_X
IniWrite, %y%, %cfgFile%, P, %key%_Y
```

**Sorun:** Komut parametrelerinde string birleştirme için değişken gerekir

**✅ DÜZELTİLDİ:**
```ahk
xKey := key . "_X"
yKey := key . "_Y"
IniWrite, %x%, %cfgFile%, P, %xKey%
IniWrite, %y%, %cfgFile%, P, %yKey%
```

#### 3. **HasPoint Fonksiyonu** (~258. satır)
**❌ YANLIŞ (Fonksiyon Sözdizimi):**
```ahk
x := IniRead(cfgFile, "P", key . "_X", "")
y := IniRead(cfgFile, "P", key . "_Y", "")
```

**✅ DÜZELTİLDİ (Komut Sözdizimi):**
```ahk
xKey := key . "_X"
yKey := key . "_Y"
IniRead, x, %cfgFile%, P, %xKey%
IniRead, y, %cfgFile%, P, %yKey%
```

#### 4. **LoadPoint Fonksiyonu** (~265. satır)
**❌ YANLIŞ (Fonksiyon Sözdizimi):**
```ahk
x := IniRead(cfgFile, "P", key . "_X", 0) + 0
y := IniRead(cfgFile, "P", key . "_Y", 0) + 0
```

**✅ DÜZELTİLDİ (Komut Sözdizimi):**
```ahk
xKey := key . "_X"
yKey := key . "_Y"
IniRead, x, %cfgFile%, P, %xKey%, 0
IniRead, y, %cfgFile%, P, %yKey%, 0
x := x + 0
y := y + 0
```

## Neden Önemli?

AutoHotkey v1'de **komutlar** ve **fonksiyonlar** arasında kritik fark vardır:

- **Komutlar** sözdizimi: `Komut, ÇıktıDeğişkeni, Param1, Param2`
- **Fonksiyonlar** sözdizimi: `sonuc := Fonksiyon(param1, param2)`

`IniRead` ve `IniWrite` AHK v1'de **komuttur**, fonksiyon değildir. Yanlış sözdizimi kullanımı script'in çalışmamasına veya beklenmedik davranışlara neden olur.

## AutoHotkey v1 vs v2

Not: AutoHotkey v2 bu işlemler için fonksiyon sözdizimi kullanır, ancak bu script açıkça "AutoHotkey v1" olarak işaretlenmiştir ve komut sözdizimi kullanmalıdır.

## Değiştirilen Dosyalar

- `lotus_pecas_auto.ahk` - Tüm düzeltmeler uygulanarak oluşturuldu
- `LOTUS_AUTO_FIXES.md` - İngilizce döküman
- `HATA_DUZELTMELERI.md` - Türkçe döküman (bu dosya)

## Test Etme

Script'i test etmek için:
1. AutoHotkey v1'in yüklü olduğundan emin olun
2. Dizinleri oluşturun: `C:\Orders\`
3. Script'i AutoHotkey v1 ile çalıştırın
4. Noktaları ayarlamak için kalibrasyon kısayollarını kullanın (Ctrl+Alt+R, N, T, vb.)
5. F9 ile siparişleri işleyin veya F10 ile otomatik izlemeyi etkinleştirin

## Kullanılan Kısayollar

### Kalibrasyon:
- **Ctrl+Alt+R**: Raucher bölgesi
- **Ctrl+Alt+N**: Nicht-Raucher bölgesi
- **Ctrl+Alt+T**: Terasse bölgesi
- **Ctrl+Alt+1-9**: Grid noktaları
- **Ctrl+Alt+S**: Arama butonu (🔍)
- **Ctrl+Alt+O**: OK butonu (✅)
- **Ctrl+Alt+B**: Gönder butonu

### Kullanım:
- **F9**: Manuel sipariş işleme
- **F10**: Otomatik izleme açma/kapama
