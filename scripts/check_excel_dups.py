import openpyxl

wb = openpyxl.load_workbook(r"c:\laragon\www\dashboard-se2026\data\mfd\sm22025\msubsls_25_2_3509.xlsx", data_only=True)
ws = wb['Sheet 1']

idsubsls_col_idx = 2  # 'idsubsls' is index 2 (column C)
ids = []

for row in ws.iter_rows(min_row=2, values_only=True):
    val = row[idsubsls_col_idx]
    if val:
        ids.append(str(val))
        
wb.close()

total = len(ids)
unique = len(set(ids))
print(f"Total idsubsls rows: {total}")
print(f"Unique idsubsls count: {unique}")
print(f"Duplicates count: {total - unique}")

# Let's find some duplicates
from collections import Counter
c = Counter(ids)
dups = [k for k, v in c.items() if v > 1]
print(f"Sample duplicate ids (first 5): {dups[:5]}")
for d in dups[:5]:
    print(f"  ID {d} occurs {c[d]} times")
