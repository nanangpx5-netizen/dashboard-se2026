import mysql.connector

try:
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="bps_jember_se2026"
    )
    cursor = conn.cursor()
    
    tables = ['sipw_import', 'master_sls', 'sipw_assignment', 'mfd_kec', 'wilayah_kerja', 'users', 'dash_monitoring_summary']
    
    print("=" * 80)
    print("ROW COUNTS FOR KEY TABLES")
    print("=" * 80)
    for t in tables:
        try:
            cursor.execute(f"SELECT COUNT(*) FROM `{t}`")
            cnt = cursor.fetchone()[0]
            print(f"Table `{t}` row count: {cnt}")
        except Exception as ex:
            print(f"Table `{t}` error or not exists: {ex}")
            
    # Check sample data in sipw_import
    try:
        cursor.execute("SELECT * FROM sipw_import LIMIT 1")
        row = cursor.fetchone()
        if row:
            # print columns names
            cursor.execute("DESCRIBE sipw_import")
            cols = [r[0] for r in cursor.fetchall()]
            print("\nSample row in `sipw_import`:")
            for c, val in zip(cols, row):
                print(f"  {c}: {val}")
    except Exception as ex:
        print("Error getting sipw_import sample:", ex)

    # Check sample data in master_sls
    try:
        cursor.execute("SELECT * FROM master_sls LIMIT 1")
        row = cursor.fetchone()
        if row:
            cursor.execute("DESCRIBE master_sls")
            cols = [r[0] for r in cursor.fetchall()]
            print("\nSample row in `master_sls`:")
            for c, val in zip(cols, row):
                print(f"  {c}: {val}")
    except Exception as ex:
        print("Error getting master_sls sample:", ex)

    cursor.close()
    conn.close()
except Exception as e:
    print("Error:", e)
