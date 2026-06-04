import mysql.connector
import json

try:
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="bps_jember_se2026"
    )
    cursor = conn.cursor()
    
    print("=" * 80)
    print("LIST OF TABLES IN bps_jember_se2026")
    print("=" * 80)
    
    cursor.execute("SHOW TABLES")
    tables = [row[0] for row in cursor.fetchall()]
    print(f"Tables found: {tables}")
    
    for table in tables:
        print(f"\n--- Structure of {table} ---")
        cursor.execute(f"DESCRIBE `{table}`")
        columns = cursor.fetchall()
        for col in columns:
            print(f"  Field: {col[0]:<20} | Type: {col[1]:<20} | Null: {col[2]:<5} | Key: {col[3]:<5} | Default: {str(col[4]):<10} | Extra: {col[5]}")
            
        cursor.execute(f"SELECT COUNT(*) FROM `{table}`")
        row_count = cursor.fetchone()[0]
        print(f"  Total rows: {row_count}")
        
    cursor.close()
    conn.close()
except Exception as e:
    print("Error:", e)
