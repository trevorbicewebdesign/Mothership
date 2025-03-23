# 📄 invoice-edit.js — Invoice Form Interaction Logic (Joomla Component)

This script enhances an interactive invoice editing form in the Joomla administrator interface.
It provides real-time subtotal and total calculation, input formatting, and field synchronization
for invoice items. Designed for compatibility with dynamic tables of invoice rows.

## 🧩 Functionality Overview

### 1. Subtotal Calculation Per Row
- Monitors changes to `quantity` and `rate` inputs in each row.
- Calculates subtotal as `rate * quantity`, rounded to 2 decimal places.
- Subtotal is stored in the corresponding `subtotal` input field.

### 2. Total Invoice Calculation
- Loops through all invoice rows to aggregate their `subtotal` values.
- Updates the main invoice `#jform_total` input with the grand total (2 decimal places).

### 3. Hour/Minute ↔ Quantity Sync
- When `hours` or `minutes` change, the script:
  - Converts values into a decimal `quantity` (e.g., `1h 30m → 1.5`)
  - Updates the `quantity` field.
- When `quantity` loses focus (on blur), it:
  - Converts back into `hours` and `minutes`
  - Rounds to nearest minute
  - Updates both fields.

### 4. Rate Input Masking
- Rate fields use right-to-left currency-style input:
  - Removes non-digits
  - Treats raw input as cents (`"1234"` → `12.34`)
  - Applies fixed-point formatting (`.toFixed(2)`)
- Live updates occur on every `input` event.

### 5. Quantity Field Formatting
- The `quantity` field accepts manual decimal input (e.g., `1.5`).
- Not formatted during typing to prevent overwriting user input.
- On blur, value is rounded and formatted to 2 decimal places.
- Only then are hours/minutes synced.

### 6. Page Load Initialization
- On document ready:
  - Each table row is scanned.
  - Quantity is converted into hours/minutes.
  - Subtotal is calculated.
  - Grand total is computed and displayed.

## ⚙️ Event Binding Summary

| Event     | Target Field(s)          | Action Description                                      |
|-----------|--------------------------|----------------------------------------------------------|
| `input`   | `[hours]`, `[minutes]`   | Updates `quantity` and subtotal                         |
| `input`   | `[quantity]`             | Updates subtotal only                                   |
| `blur`    | `[quantity]`             | Converts to hours/minutes, formats quantity             |
| `input`   | `[rate]`                 | Applies masking, updates subtotal                       |
| `blur`    | `[rate]`                 | Final formatting of currency                            |
| `ready`   | `document`               | Initializes all fields, subtotals, and totals           |

## 🔒 Notes
- All calculations are performed client-side for immediate feedback.
- Backend logic **must recompute** subtotals and totals for data integrity.
- Input fields should use `type="text"` for `rate` and `quantity` if masking is active.