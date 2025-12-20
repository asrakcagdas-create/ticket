# Lotus Pecas Auto Input - Error Fixes

## Errors Found and Fixed

### Main Error: Incorrect IniRead/IniWrite Syntax

The original script used **function syntax** for `IniRead` and `IniWrite`, but in **AutoHotkey v1**, these are **commands**, not functions.

### Specific Issues Fixed:

#### 1. **ClickTableByArea Function** (Line ~190)
**❌ WRONG (Function Syntax):**
```ahk
cols := IniRead(cfgFile, "GRID", prefix . "COLS", 7) + 0
```

**✅ FIXED (Command Syntax):**
```ahk
colsKey := prefix . "COLS"
IniRead, cols, %cfgFile%, GRID, %colsKey%, 7
cols := cols + 0
```

#### 2. **SavePoint Function** (Line ~250)
**❌ WRONG (Function Syntax):**
```ahk
IniWrite, %x%, %cfgFile%, P, %key%_X
IniWrite, %y%, %cfgFile%, P, %key%_Y
```

**Issue:** String concatenation in command parameters requires variables

**✅ FIXED (Command Syntax):**
```ahk
xKey := key . "_X"
yKey := key . "_Y"
IniWrite, %x%, %cfgFile%, P, %xKey%
IniWrite, %y%, %cfgFile%, P, %yKey%
```

#### 3. **HasPoint Function** (Line ~258)
**❌ WRONG (Function Syntax):**
```ahk
x := IniRead(cfgFile, "P", key . "_X", "")
y := IniRead(cfgFile, "P", key . "_Y", "")
```

**✅ FIXED (Command Syntax):**
```ahk
xKey := key . "_X"
yKey := key . "_Y"
IniRead, x, %cfgFile%, P, %xKey%
IniRead, y, %cfgFile%, P, %yKey%
```

#### 4. **LoadPoint Function** (Line ~265)
**❌ WRONG (Function Syntax):**
```ahk
x := IniRead(cfgFile, "P", key . "_X", 0) + 0
y := IniRead(cfgFile, "P", key . "_Y", 0) + 0
```

**✅ FIXED (Command Syntax):**
```ahk
xKey := key . "_X"
yKey := key . "_Y"
IniRead, x, %cfgFile%, P, %xKey%, 0
IniRead, y, %cfgFile%, P, %yKey%, 0
x := x + 0
y := y + 0
```

## Why This Matters

In AutoHotkey v1, there's a critical difference between **commands** and **functions**:

- **Commands** use syntax: `Command, OutputVar, Param1, Param2`
- **Functions** use syntax: `result := Function(param1, param2)`

`IniRead` and `IniWrite` are **commands** in AHK v1, not functions. Using the wrong syntax causes the script to fail or behave unexpectedly.

## AutoHotkey v1 vs v2

Note: AutoHotkey v2 does use function syntax for these operations, but this script is explicitly marked as "AutoHotkey v1" and must use command syntax.

## Files Modified

- `lotus_pecas_auto.ahk` - Created with all corrections applied

## Testing

To test the script:
1. Ensure AutoHotkey v1 is installed
2. Create the directories: `C:\Orders\`
3. Run the script with AutoHotkey v1
4. Use calibration hotkeys (Ctrl+Alt+R, N, T, etc.) to set up points
5. Test with F9 to process orders or F10 to enable auto-watching
