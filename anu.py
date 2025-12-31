import streamlit as st
import pandas as pd
import numpy as np
from sqlalchemy import create_engine, text
from datetime import datetime
import io
import time
import instaloader
import xlsxwriter
import re
import osx
import hashlib

# ============ CONFIGURATION ============
DB_URL = "sqlite:///PLN_Ultimate_Monitoring_V7.db"
engine = create_engine(DB_URL, connect_args={"check_same_thread": False})

st.set_page_config(
    page_title="PLN Monitoring Medsos",
    layout="wide",
    page_icon="âš¡",
    initial_sidebar_state="collapsed"
)

# ============ AUTHENTICATION & ROLE SYSTEM ============
def init_auth_db():
    """Initialize users and roles table"""
    with engine.begin() as conn:
        conn.execute(text("""
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                unit TEXT,
                created_at TEXT
            )
        """))
        
        # Create default admin account (username: admin, password: admin123)
        admin_pass = hashlib.sha256('admin123'.encode()).hexdigest()
        try:
            conn.execute(text("""
                INSERT INTO users (username, password, role, unit, created_at)
                VALUES ('admin', :pass, 'admin', 'ADMIN', :ca)
            """), {"pass": admin_pass, "ca": datetime.now().strftime('%Y-%m-%d %H:%M:%S')})
        except:
            pass

def verify_password(password):
    """Hash password untuk keamanan"""
    return hashlib.sha256(password.encode()).hexdigest()

def login_user(username, password):
    """Verify credentials and return user info"""
    pass_hash = verify_password(password)
    try:
        result = pd.read_sql(
            text("SELECT id, username, role, unit FROM users WHERE username = :u AND password = :p"),
            engine,
            params={"u": username, "p": pass_hash}
        )
        if not result.empty:
            return {
                "id": result.iloc[0]['id'],
                "username": result.iloc[0]['username'],
                "role": result.iloc[0]['role'],
                "unit": result.iloc[0]['unit']
            }
    except:
        pass
    return None

def register_user(username, password, role='user', unit=''):
    """Register new user"""
    pass_hash = verify_password(password)
    try:
        with engine.begin() as conn:
            conn.execute(text("""
                INSERT INTO users (username, password, role, unit, created_at)
                VALUES (:u, :p, :r, :un, :ca)
            """), {
                "u": username,
                "p": pass_hash,
                "r": role,
                "un": unit,
                "ca": datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            })
        return True
    except:
        return False

def show_login_page():
    """Display login/register page"""
    st.markdown("""
        <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: linear-gradient(135deg, #001a2e 0%, #0a3a52 100%);
            border-radius: 12px;
            color: white;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .login-container h1 { text-align: center; font-size: 28px; margin-bottom: 30px; }
        .login-container input { width: 100%; margin: 10px 0; padding: 10px; border-radius: 6px; border: none; }
        .login-container button { width: 100%; margin: 10px 0; padding: 10px; background: #0052a3; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        </style>
    """, unsafe_allow_html=True)
    
    col1, col2, col3 = st.columns([1, 2, 1])
    with col2:
        st.markdown("""
            <div class='login-container'>
                <h1>âš¡ PLN MONITORING</h1>
            </div>
        """, unsafe_allow_html=True)
        
        tab1, tab2 = st.tabs(["Login", "Register"])
        
        with tab1:
            st.subheader("Login")
            username = st.text_input("Username", key="login_user")
            password = st.text_input("Password", type="password", key="login_pass")
            
            if st.button("Login", use_container_width=True):
                user = login_user(username, password)
                if user:
                    st.session_state.user = user
                    # set default dashboard immediately on login
                    if 'current_nav' not in st.session_state or not st.session_state.current_nav:
                        st.session_state.current_nav = "Dashboard Admin" if user.get('role') == 'admin' else "Dashboard User"
                    st.success(f"Welcome {user['username']}!")
                    time.sleep(1)
                    st.rerun()
                else:
                    st.error("Username atau password salah")
        
        with tab2:
            st.subheader("Daftar Akun Baru")
            new_user = st.text_input("Username", key="reg_user")
            new_pass = st.text_input("Password", type="password", key="reg_pass")
            new_pass_conf = st.text_input("Konfirmasi Password", type="password", key="reg_pass_conf")
            reg_unit = st.text_input("Unit Kerja", key="reg_unit")
            
            if st.button("Daftar", use_container_width=True):
                if new_pass != new_pass_conf:
                    st.error("Password tidak cocok")
                elif len(new_pass) < 6:
                    st.error("Password minimal 6 karakter")
                elif register_user(new_user, new_pass, 'user', reg_unit):
                    st.success("Akun berhasil dibuat! Silakan login.")
                else:
                    st.error("Username sudah terdaftar atau error")

# Initialize databases
init_auth_db()

# Check if user is logged in
if 'user' not in st.session_state:
    show_login_page()
    st.stop()

# Initialize default nav for admin on first login
if 'current_nav' not in st.session_state:
    user_role = st.session_state.user.get('role', 'user')
    if user_role == "admin":
        st.session_state.current_nav = "Dashboard Admin"
    else:
        st.session_state.current_nav = "Dashboard User"

# ============ CUSTOM CSS - Clean & Organized ============
st.markdown("""
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
    
    html, body, [class*="css"] { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        color: #1e293b; 
    }
    
    .main { background: #f8fafc; }

    header[data-testid="stHeader"],
    footer,
    [data-testid="stSidebar"],
    [data-testid="stSidebarCollapseButton"],
    .stToolbar,
    [data-testid="stDecoration"],
    div:has(> button[id*="nav_trigger"]) {
        display: none !important;
    }

    .app-header-wrapper {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 9999;
        width: 100%;
        margin: 0;
        padding: 0;
    }

    .app-header {
        background: linear-gradient(135deg, #001a2e 0%, #0a3a52 40%, #0052a3 100%);
        padding: 16px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 100px;
        flex-wrap: wrap;
        gap: 20px;
        width: 100%;
        box-shadow: 0 4px 12px rgba(0,82,163,0.15);
    }

    .header-logo-section {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .header-logo-icon {
        background: rgba(255, 255, 255, 0.2);
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        box-shadow: 0 8px 20px rgba(0,114,188,0.35);
        border: 1px solid rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
    }

    .header-logo-text h2 {
        color: white;
        font-size: 18px;
        font-weight: 800;
        margin: 0;
        line-height: 1.1;
    }

    .header-logo-text p {
        color: rgba(255,255,255,0.8);
        font-size: 12px;
        margin: 2px 0 0 0;
    }

    .header-date {
        color: white;
        font-size: 12px;
        font-weight: 500;
        background: rgba(255,255,255,0.1);
        padding: 8px 16px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.2);
    }

    .user-info {
        color: white;
        font-size: 12px;
        background: rgba(255,255,255,0.1);
        padding: 8px 16px;
        border-radius: 8px;
        display: flex;
        gap: 10px;
        align-items: center;
        border: 1px solid rgba(255,255,255,0.2);
    }

    .app-navbar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
        background: rgba(0,0,0,0.15);
        padding: 12px 16px;
        width: 100%;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        position: sticky;
        top: 164px;
        z-index: 9998;
    }

    .nav-btn {
        padding: 10px 18px;
        border: none;
        background: rgba(255,255,255,0.08);
        color: white;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.15);
        white-space: nowrap;
        flex-shrink: 0;
    }

    .nav-btn:hover {
        background: rgba(255,255,255,0.15);
        border-color: rgba(255,255,255,0.3);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255,187,36,0.2);
    }

    .nav-btn.active {
        background: linear-gradient(135deg, rgba(251,191,36,0.25) 0%, rgba(251,191,36,0.15) 100%);
        border-bottom: 3px solid #fbbf24;
        border-color: rgba(251,191,36,0.6);
        color: #fef08a;
        font-weight: 600;
    }

    .stMain {
        padding-top: 240px !important;
        padding-bottom: 180px !important;
    }

    .header-box {
        background: linear-gradient(135deg, #001a2e 0%, #0a3a52 100%);
        padding: 30px;
        border-radius: 12px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 8px 20px rgba(0,26,46,0.2);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .header-box h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
        font-weight: 800;
    }

    .header-box p {
        margin: 0;
        opacity: 0.9;
        font-weight: 400;
    }

    .metric-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }

    .metric-card:hover {
        box-shadow: 0 4px 16px rgba(0,82,163,0.1);
        transform: translateY(-2px);
    }

    .footer {
        text-align: center;
        padding: 30px;
        color: #64748b;
        font-size: 12px;
        border-top: 1px solid #e2e8f0;
        margin-top: 50px;
        background: #f8fafc;
    }

    .system-badge {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 12px 16px;
        border-radius: 20px;
        font-size: 11px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        border: 1px solid rgba(255,255,255,0.2);
    }

    .status-dot {
        width: 8px;
        height: 8px;
        background: white;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    /* Form Styling */
    .stForm {
        background: white;
        padding: 30px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .stExpander {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 12px;
    }

    .stDataFrame {
        border-radius: 8px;
        overflow: hidden;
    }

    /* Button Styling */
    .stButton button {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
    }

    .stButton button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .stTabs [data-baseweb="tab-list"] {
        background-color: #f8fafc;
        border-radius: 8px;
        gap: 4px;
        padding: 8px;
    }

    .stTabs [data-baseweb="tab"] {
        border-radius: 6px;
    }

    .stInfo, .stWarning, .stSuccess, .stError {
        border-radius: 8px;
        border-left: 4px solid;
    }

    @media (max-width: 768px) {
        .app-header {
            padding: 12px 16px;
            min-height: auto;
        }
        .header-logo-icon { width: 50px; height: 50px; font-size: 24px; }
        .header-logo-text h2 { font-size: 14px; }
        .header-date { font-size: 10px; }
        .nav-btn { font-size: 11px; padding: 8px 14px; }
        .stMain { padding-top: 240px !important; }
        .header-box { padding: 20px; }
        .header-box h1 { font-size: 22px; }
    }
    </style>
""", unsafe_allow_html=True)

# ============ INITIALIZE SESSION STATE & DATABASE ============
def init_db():
    """Initialize database tables"""
    try:
        with engine.begin() as conn:
            conn.execute(text("""
                CREATE TABLE IF NOT EXISTS daftar_akun_unit (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nama_unit TEXT,
                    username_ig TEXT UNIQUE
                )
            """))
            
            conn.execute(text("""
                CREATE TABLE IF NOT EXISTS monitoring_pln (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tanggal TEXT, bulan TEXT, tahun TEXT,
                    judul_pemberitaan TEXT, 
                    link_pemberitaan TEXT UNIQUE,
                    platform TEXT, tipe_konten TEXT, 
                    pic_unit TEXT, 
                    akun TEXT,
                    kategori TEXT,
                    likes INTEGER DEFAULT 0, 
                    comments INTEGER DEFAULT 0,
                    views INTEGER DEFAULT 0,
                    last_updated TEXT
                )
            """))

            conn.execute(text("""
                CREATE TABLE IF NOT EXISTS pengajuan_dokumentasi (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nama_pengaju TEXT,
                    user_id INTEGER,
                    nomor_telpon TEXT,
                    unit TEXT,
                    tanggal_acara TEXT,
                    jam_mulai TEXT,
                    jam_selesai TEXT,
                    output_link_drive TEXT,
                    output_type TEXT,
                    biaya REAL DEFAULT 0,
                    deadline_penyelesaian TEXT,
                    status TEXT DEFAULT 'pending',
                    hasil_link_drive TEXT,
                    added_to_calendar INTEGER DEFAULT 0,
                    created_at TEXT,
                    updated_at TEXT,
                    notes TEXT
                )
            """))

            conn.execute(text("""
                CREATE TABLE IF NOT EXISTS dokumentasi_calendar (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    pengajuan_id INTEGER,
                    tanggal TEXT,
                    nama_kegiatan TEXT,
                    unit TEXT,
                    status TEXT,
                    created_at TEXT
                )
            """))

            # --- Migration: ensure expected columns exist for backwards compatibility ---
            def ensure_columns(table_name, columns):
                # columns: dict of column_name -> column_definition (e.g. "user_id INTEGER")
                existing = [r[1] for r in conn.execute(text(f"PRAGMA table_info('{table_name}')")).fetchall()]
                for col, definition in columns.items():
                    if col not in existing:
                        try:
                            conn.execute(text(f"ALTER TABLE {table_name} ADD COLUMN {definition}"))
                        except Exception:
                            pass

            ensure_columns('pengajuan_dokumentasi', {
                'user_id': "user_id INTEGER",
                'nomor_telpon': "nomor_telpon TEXT",
                'unit': "unit TEXT",
                'tanggal_acara': "tanggal_acara TEXT",
                'jam_mulai': "jam_mulai TEXT",
                'jam_selesai': "jam_selesai TEXT",
                'output_link_drive': "output_link_drive TEXT",
                'output_type': "output_type TEXT",
                'biaya': "biaya REAL DEFAULT 0",
                'deadline_penyelesaian': "deadline_penyelesaian TEXT",
                'status': "status TEXT DEFAULT 'pending'",
                'hasil_link_drive': "hasil_link_drive TEXT",
                'hasil_link_1': "hasil_link_1 TEXT",
                'hasil_link_2': "hasil_link_2 TEXT",
                'hasil_link_3': "hasil_link_3 TEXT",
                'added_to_calendar': "added_to_calendar INTEGER DEFAULT 0",
                'created_at': "created_at TEXT",
                'updated_at': "updated_at TEXT",
                'notes': "notes TEXT"
            })

            ensure_columns('monitoring_pln', {
                'comments': "comments INTEGER DEFAULT 0"
            })

            ensure_columns('dokumentasi_calendar', {
                'pengajuan_id': "pengajuan_id INTEGER",
                'tanggal': "tanggal TEXT",
                'nama_kegiatan': "nama_kegiatan TEXT",
                'unit': "unit TEXT",
                'status': "status TEXT",
                'created_at': "created_at TEXT"
            })
    except Exception as e:
        st.error(f"Database initialization error: {e}")

init_db()

# ============ HELPER FUNCTIONS ============
def clean_txt(text_input):
    if not text_input: return "Konten Visual"
    res = re.sub(r'[^\x00-\x7f]', r'', text_input)
    return res.replace('\n', ' ').strip()

def get_month_order():
    return ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

def extract_username(input_str):
    if "instagram.com/" in input_str:
        return input_str.rstrip('/').split('/')[-1]
    return input_str.replace('@', '').strip()

# Helper function to apply date filter
def apply_date_filter(df):
    """Apply date range filter to dataframe if enabled"""
    if not df.empty and st.session_state.get('use_date_filter', False):
        # Parse tanggal column
        df['tanggal_parsed'] = pd.to_datetime(df['tanggal'], format='%d/%m/%Y', errors='coerce')
        date_from = st.session_state.get('date_filter_from')
        date_to = st.session_state.get('date_filter_to')
        
        if date_from and date_to:
            df = df[(df['tanggal_parsed'] >= pd.Timestamp(date_from)) & 
                    (df['tanggal_parsed'] <= pd.Timestamp(date_to))]
            df = df.drop('tanggal_parsed', axis=1)
    return df

# --- Analytics / Export Helpers ---
def color_rekap_style(val):
    try:
        if val >= 20:
            color = '#002d40; color: white;'
        elif val >= 10:
            color = '#0072bc; color: white;'
        elif val > 0:
            color = '#f0f9ff; color: #0369a1;'
        else:
            color = 'white; color: #e2e8f0;'
    except Exception:
        color = 'white; color: #e2e8f0;'
    return f'background-color: {color}; font-weight: 600; border: 1px solid #f1f5f9'


def generate_excel_report(df):
    output = io.BytesIO()
    with pd.ExcelWriter(output, engine='xlsxwriter') as writer:
        workbook = writer.book
        header_fmt = workbook.add_format({
            'bold': True,
            'font_color': '#ffffff',
            'bg_color': '#0072bc',
            'border': 1,
            'align': 'center',
            'valign': 'vcenter'
        })
        year_title_fmt = workbook.add_format({
            'bold': True,
            'font_size': 14,
            'font_color': '#0072bc',
            'underline': True
        })

        df.to_excel(writer, index=False, sheet_name='Data_Detail')
        worksheet1 = writer.sheets['Data_Detail']
        for col_num, value in enumerate(df.columns.values):
            worksheet1.write(0, col_num, value, header_fmt)
        worksheet1.set_column('A:Z', 18)

        if not df.empty:
            sheet_name = 'Rekapan Tahunan'
            worksheet2 = workbook.add_worksheet(sheet_name)

            daftar_tahun = sorted(df['tahun'].dropna().unique(), reverse=True)
            current_row = 0
            for thn in daftar_tahun:
                df_year = df[df['tahun'] == thn]

                rekap_thn = df_year.pivot_table(
                    index='pic_unit',
                    columns='bulan',
                    values='id',
                    aggfunc='count',
                    fill_value=0
                )

                full_months = get_month_order()
                for m in full_months:
                    if m not in rekap_thn.columns:
                        rekap_thn[m] = 0
                rekap_thn = rekap_thn[full_months]

                worksheet2.write(current_row, 0, f"REKAPITULASI TAHUN {thn}", year_title_fmt)
                current_row += 1

                worksheet2.write(current_row, 0, 'Unit Kerja', header_fmt)
                for col_num, month_name in enumerate(rekap_thn.columns.values):
                    worksheet2.write(current_row, col_num + 1, month_name, header_fmt)

                data_row = current_row + 1
                for unit_idx, (unit_name, row_data) in enumerate(rekap_thn.iterrows()):
                    worksheet2.write(data_row + unit_idx, 0, unit_name)
                    for col_idx, val in enumerate(row_data):
                        worksheet2.write(data_row + unit_idx, col_idx + 1, val)

                current_row = data_row + len(rekap_thn) + 3

            worksheet2.set_column('A:A', 30)
            worksheet2.set_column('B:M', 12)

    return output.getvalue()

def get_nav_for_role(role):
    """Return navigation options based on user role"""
    if role == "admin":
        return ["Dashboard Admin", "Rekapitulasi Monitoring", "Sinkronisasi Data", "Input Manual",
                "Pengajuan Dokumentasi", "Kalender Dokumentasi", "Pengaturan Unit", "Manajemen User", "Pengaturan Admin"]
    else:  # user
        return ["Dashboard User", "Kalender Dokumentasi", "Pengajuan Dokumentasi", "Dokumentasi Manual"]

# ============ CALENDAR RENDER HELPERS ============
import calendar

def parse_date_str(datestr):
    """Try to parse several date formats to a datetime.date object"""
    from datetime import datetime as _dt
    if not datestr or pd.isna(datestr):
        return None
    for fmt in ("%d/%m/%Y", "%Y-%m-%d", "%Y-%m-%d %H:%M:%S", "%d-%m-%Y"):
        try:
            return _dt.strptime(datestr, fmt).date()
        except Exception:
            continue
    try:
        # fallback: pandas
        return pd.to_datetime(datestr, dayfirst=True).date()
    except Exception:
        return None

def render_month_calendar(year, month, events=None):
    """Return HTML calendar for given month with events marked.

    events: list of dicts with keys 'tanggal' (str) and 'nama_kegiatan' and optional 'unit' and 'status'
    """
    cal = calendar.Calendar(firstweekday=6)  # week starts Sunday to mimic image
    weeks = cal.monthdayscalendar(year, month)
    events_map = {}
    if events is None:
        events = []
    for ev in events:
        d = parse_date_str(ev.get('tanggal'))
        if d and d.year == year and d.month == month:
            events_map.setdefault(d.day, []).append(ev)

    # Styles
    html = """
    <style>
    .mini-cal { 
        border-collapse: collapse; 
        width: 100%; 
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
    }
    .mini-cal th { 
        background: linear-gradient(135deg, #0052a3 0%, #0a3a52 100%);
        color: white;
        padding: 12px 6px; 
        font-weight: 700;
        font-size: 13px;
        text-align: center;
    }
    .mini-cal td { 
        border: 1px solid #e2e8f0; 
        width: 14.28%; 
        vertical-align: top; 
        height: 110px; 
        padding: 8px; 
        background: #fff;
        position: relative;
    }
    .mini-cal td:hover {
        background: #f0f9ff;
    }
    .mini-cal .daynum { 
        font-weight: 700; 
        color: #0f172a;
        font-size: 14px;
        margin-bottom: 6px;
    }
    .event-badge { 
        display: block; 
        margin-top: 4px; 
        background: #e6f4ea; 
        color: #065f46; 
        padding: 4px 6px; 
        border-radius: 4px; 
        font-size: 11px;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        border-left: 3px solid #10b981;
    }
    .event-badge.pending { 
        background: #fff7ed; 
        color: #92400e;
        border-left-color: #f59e0b;
    }
    .event-badge.approved { 
        background: #ecfdf5; 
        color: #065f46;
        border-left-color: #10b981;
    }
    .event-badge.done { 
        background: #edf2ff; 
        color: #312e81;
        border-left-color: #6366f1;
    }
    </style>
    """

    html += f"<table class='mini-cal'><thead><tr>"
    for wd in ['Ming', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']:
        html += f"<th>{wd}</th>"
    html += "</tr></thead><tbody>"

    for week in weeks:
        html += "<tr>"
        for day in week:
            if day == 0:
                html += "<td style='background:#f8fafc;'></td>"
            else:
                html += "<td>"
                html += f"<div class='daynum'>{day}</div>"
                if day in events_map:
                    for ev in events_map[day][:2]:
                        status = ev.get('status', '').lower()
                        cls = 'event-badge ' + (status if status in ['pending', 'approved', 'done'] else '')
                        title = ev.get('nama_kegiatan', ev.get('unit', 'Kegiatan'))
                        html += f"<div class='{cls}' title='{title} ({status.upper()})'>{title[:20]}</div>"
                    if len(events_map[day]) > 2:
                        html += f"<div class='event-badge' style='background:#d1d5db;color:#374151;'>+{len(events_map[day])-2} lainnya</div>"
                html += "</td>"
        html += "</tr>"
    html += "</tbody></table>"
    return html

# ============ RENDER HEADER & NAVIGATION ============
# Render Header
user_role = st.session_state.user.get('role', 'user')
user_name = st.session_state.user.get('username', 'User')

st.markdown(f"""
    <div class='app-header-wrapper'>
        <div class='app-header'>
            <div class='header-logo-section'>
                <div class='header-logo-icon'>âš¡</div>
                <div class='header-logo-text'>
                    <h2>PLN UID LAMPUNG</h2>
                    <p>DIGITAL MONITORING SYSTEM</p>
                </div>
            </div>
            <div class='header-date'>{datetime.now().strftime("%A, %d %B %Y")}</div>
            <div class='user-info'>
                ðŸ‘¤ {user_name} | <b>{user_role.upper()}</b>
            </div>
        </div>
    </div>
""", unsafe_allow_html=True)

# Initialize navigation
nav_options = get_nav_for_role(user_role)
if st.session_state.current_nav is None:
    st.session_state.current_nav = nav_options[0]

# Render Navigation Bar
nav_html = "<div class='app-navbar'>"
for idx, option in enumerate(nav_options):
    active_class = "active" if st.session_state.current_nav == option else ""
    nav_html += f"<button class='nav-btn {active_class}' onclick=\"document.getElementById('nav_trigger_{idx}').click();\">{option}</button>"
nav_html += "</div>"
st.markdown(nav_html, unsafe_allow_html=True)

# Hidden button columns for navigation
nav_cols = st.columns(len(nav_options))
for idx, option in enumerate(nav_options):
    with nav_cols[idx]:
        if st.button(option, key=f"nav_trigger_{idx}", use_container_width=True):
            st.session_state.current_nav = option
            st.rerun()

# Hide the nav buttons visually
st.markdown("""<style>
div:has(> button[id*="nav_trigger"]) { display: none !important; }
</style>""", unsafe_allow_html=True)

# Logout button
col_logout = st.columns([5, 1])
with col_logout[1]:
    if st.button("Logout", use_container_width=True, help="Keluar dari sistem"):
        del st.session_state.user
        st.rerun()

# ============ ROLE-BASED PAGE ROUTING ============
nav = st.session_state.current_nav

# === ADMIN PAGES ===
if user_role == "admin":
    
    # PAGE 1: ADMIN DASHBOARD
    if nav == "Dashboard Admin":
        st.markdown("<div class='header-box'><h1>ðŸ“Š Dashboard Admin</h1><p>Monitoring Performa Media Digital PLN Group</p></div>", unsafe_allow_html=True)

        df_main = pd.read_sql(text("SELECT * FROM monitoring_pln"), engine)
        # apply optional date filter if helper exists
        try:
            df_main = apply_date_filter(df_main)
        except Exception:
            pass

        if not df_main.empty:
            c1, c2, c3, c4, c5 = st.columns(5)
            c1.metric("TOTAL POST ðŸ–‹ï¸", len(df_main))
            c2.metric("TOTAL LIKES â¤ï¸", f"{int(df_main['likes'].sum()):,}")
            c3.metric("TOTAL COMMENTS ðŸ’¬", f"{int(df_main['comments'].sum()):,}")
            c4.metric("TOTAL VIEWS ðŸ‘€", f"{int(df_main['views'].sum()):,}")
            c5.metric("UNIT AKTIF ðŸ“", df_main['pic_unit'].nunique())

            st.markdown("---")

            col_a, col_b = st.columns([1.2, 0.8])
            with col_a:
                st.subheader("Tren Publikasi Bulanan")
                counts = df_main['bulan'].value_counts().reindex(get_month_order()).fillna(0)
                st.area_chart(counts)

            with col_b:
                st.subheader("Top 5 Unit Teraktif")
                unit_counts = df_main['pic_unit'].value_counts().head(5)
                st.bar_chart(unit_counts)

            st.markdown("### ðŸ† Top Akun (Performa Posting & Likes)")
            top_acc = df_main.groupby(['akun', 'pic_unit']).agg({
                'id': 'count',
                'likes': 'sum',
                'views': 'sum'
            }).rename(columns={'id': 'Jumlah Post', 'likes': 'Total Likes', 'views': 'Total Views'})
            top_acc = top_acc.sort_values(by=['Jumlah Post', 'Total Likes'], ascending=False)

            st.dataframe(
                top_acc.head(10),
                use_container_width=True,
                column_config={
                    "Total Likes": st.column_config.NumberColumn(format="%d â¤ï¸"),
                    "Total Views": st.column_config.NumberColumn(format="%d ðŸ‘€"),
                    "Jumlah Post": st.column_config.NumberColumn(format="%d ðŸ“")
                }
            )

            st.markdown("---")
            st.subheader("Data Monitoring Terbaru")
            st.dataframe(df_main.sort_values('last_updated', ascending=False).head(10), use_container_width=True, hide_index=True)
        else:
            st.info("Data belum tersedia. Silakan lakukan sinkronisasi data terlebih dahulu.")

    # PAGE 2: Rekapitulasi Monitoring
    elif nav == "Rekapitulasi Monitoring":
        st.markdown("<div class='header-box'><h1>Rekapitulasi Monitoring</h1><p>Hasil rekapan bulanan bahkan tahunan dengan data yang terperinci</p></div>", unsafe_allow_html=True)
        
        df_db = pd.read_sql(text("SELECT * FROM monitoring_pln"), engine)
        df_db = apply_date_filter(df_db)
        
        if not df_db.empty:
            col_space, col_ref, col_dl = st.columns([2, 1, 1])
            
            with col_ref:
                if st.button("ðŸ”„ Refresh Performa", use_container_width=True):
                    units_refresh = pd.read_sql(text("SELECT * FROM daftar_akun_unit"), engine)
                    if not units_refresh.empty:
                        with st.status("Updating...", expanded=False) as status:
                            for _, row in units_refresh.iterrows():
                                run_scraper(row['username_ig'], row['nama_unit'], limit=10)
                            status.update(label="Performa Terupdate!", state="complete")
                        st.rerun()

            with col_dl:
                st.download_button(
                    label="ðŸ“¥ Download Excel",
                    data=generate_excel_report(df_db),
                    file_name=f"Laporan_PLN_{datetime.now().strftime('%d%m%y')}.xlsx",
                    use_container_width=True
                )

            t1, t2 = st.tabs(["ðŸ“Š Rekapan Bulanan", "ðŸ“‘ Detail Monitoring"])
            
            with t1:
                st.markdown("### Heatmap Frekuensi Posting")

                # Ambil daftar tahun yang ada di data dan urutkan dari yang terbaru
                years_raw = df_db['tahun'].dropna().unique().tolist()
                def _year_key(x):
                    try:
                        return int(x)
                    except:
                        return 0
                years_sorted = sorted([str(y) for y in years_raw], key=_year_key, reverse=True)

                if not years_sorted:
                    st.info("Data tahun belum tersedia untuk rekap.")
                else:
                    tabs_years = st.tabs(["Semua"] + years_sorted)
                    for idx, y in enumerate(["Semua"] + years_sorted):
                        with tabs_years[idx]:
                            if y == "Semua":
                                df_year = df_db.copy()
                            else:
                                df_year = df_db[df_db['tahun'] == y]

                            if df_year.empty:
                                st.info(f"Tidak ada data untuk tahun {y}.")
                                continue

                            pivot = df_year.pivot_table(index='pic_unit', columns='bulan', values='id', aggfunc='count', fill_value=0)
                            bulan_urut = [b for b in get_month_order() if b in pivot.columns]
                            if bulan_urut:
                                pivot = pivot[bulan_urut]
                                st.dataframe(pivot.style.applymap(color_rekap_style), use_container_width=True)
                            else:
                                st.info("Data bulan belum tersedia untuk heatmap.")
        
            with t2:
                st.info("ðŸ’¡ **Cara Menghapus:** Klik pada ujung kiri baris hingga baris terpilih, tekan tombol **'Delete'** di keyboard Anda, lalu klik tombol **'Simpan Perubahan'** di bawah.")
                
                if 'engagement_total' in df_db.columns: 
                    df_db = df_db.drop(columns=['engagement_total'])
                
                ed = st.data_editor(
                    df_db, 
                    use_container_width=True, 
                    hide_index=True, 
                    num_rows="dynamic", 
                    disabled=["id", "last_updated"],
                    column_config={
                        "kategori": st.column_config.SelectboxColumn(
                            "Kategori",
                            options=["Korporat", "Influencer"],
                            required=True,
                        ),
                        "link_pemberitaan": st.column_config.LinkColumn("Link Postingan")
                    }
                )
                
                if st.button("Simpan Perubahan", use_container_width=True):
                    ed.to_sql("monitoring_pln", engine, if_exists="replace", index=False)
                    st.success("Database berhasil disinkronisasi!")
                    time.sleep(1)
                    st.rerun()
                    
        else:
            st.warning("Belum ada data di database.")

    # --- PAGE 3: SINKRONISASI DATA ---
    elif nav == "Sinkronisasi Data":
        st.markdown("<div class='header-box'><h1>Sinkronisasi Data</h1><p>Penarikan data otomatis dari Instagram (Korporat & Influencer)</p></div>", unsafe_allow_html=True)
        
        units = pd.read_sql(text("SELECT * FROM daftar_akun_unit"), engine)
        
        try:
            db_info = pd.read_sql(text("SELECT COUNT(*) as total, MAX(last_updated) as terakhir FROM monitoring_pln"), engine)
            total_data = db_info['total'][0]
            last_up = str(db_info['terakhir'][0])[:16] if db_info['terakhir'][0] else "-"
        except:
            total_data, last_up = 0, "-"
        
        ms1, ms2, ms3 = st.columns(3)
        ms1.metric("Unit Terdaftar", f"{len(units)} Akun")
        ms2.metric("Total Database", f"{total_data} Post")
        ms3.metric("Update Terakhir", last_up)

        st.markdown("---")
        
        with st.container():
            st.subheader("âš™ï¸ Pengaturan Sinkronisasi")
            

            cc1, cc2, cc3, cc4 = st.columns([1.4, 0.9, 0.9, 0.8])
            sync_mode = cc1.selectbox(
                "Target Sinkronisasi",
                ["Semua Akun Terdaftar", "Pilih Akun Unit Spesifik", "Input Manual Username Influencer", "Sinkronisasi via Link"],
                help="Pilih akun unit yang sudah terdaftar, input akun influencer baru, atau sinkronisasi berdasarkan link profil/post."
            )
            sync_month = cc2.selectbox("Filter Bulan", ["Semua"] + get_month_order())

            sync_limit = cc3.number_input("Minimal Post", 1, 100, 10, help="Jumlah minimal postingan yang akan diproses.")

            # Date mode dropdown on the same row
            date_mode = cc4.selectbox("Periode", ["Semua", "Pilih Rentang Tanggal"], index=0, key='date_mode')

            # Get date range from database for filter
            df_dates = pd.read_sql(text("SELECT tanggal FROM monitoring_pln ORDER BY tanggal"), engine)
            min_date, max_date = None, None
            if not df_dates.empty:
                df_dates['tanggal_parsed'] = pd.to_datetime(df_dates['tanggal'], format='%d/%m/%Y', errors='coerce')
                if df_dates['tanggal_parsed'].notna().any():
                    min_date = df_dates['tanggal_parsed'].min().date()
                    max_date = df_dates['tanggal_parsed'].max().date()

            if date_mode == "Semua":
                st.session_state.use_date_filter = False
                date_from = None
                date_to = None
            else:
                if not (min_date and max_date):
                    st.info("Data tanggal belum tersedia untuk melakukan filter rentang.")
                    st.session_state.use_date_filter = False
                    date_from = None
                    date_to = None
                else:
                    col_df1, col_df2 = st.columns(2)
                    with col_df1:
                        date_from = st.date_input("Dari Tanggal", value=min_date, min_value=min_date, max_value=max_date, key="date_from")
                    with col_df2:
                        date_to = st.date_input("Sampai Tanggal", value=max_date, min_value=min_date, max_value=max_date, key="date_to")
                    st.session_state.date_filter_from = date_from
                    st.session_state.date_filter_to = date_to
                    st.session_state.use_date_filter = True

            st.markdown("---")
            to_process = pd.DataFrame()
            target_kategori = "Korporat" 

            if sync_mode == "Input Manual Username Influencer":
                target_kategori = "Influencer"
                col_inf1, col_inf2 = st.columns(2)
                inf_user = col_inf1.text_input("Username Influencer (Contoh: jeromepolin)")
                inf_unit = col_inf2.selectbox("Hubungkan ke Unit Kerja", units['nama_unit'].tolist() if not units.empty else ["Pusat"])
                if inf_user:
                    to_process = pd.DataFrame([{"username_ig": inf_user, "nama_unit": inf_unit}])
            
            elif sync_mode == "Pilih Akun Unit Spesifik":
                if not units.empty:
                    sel_acc = st.multiselect("Pilih Unit Kerja", units['username_ig'].tolist())
                    to_process = units[units['username_ig'].isin(sel_acc)]
                else:
                    st.warning("Belum ada unit terdaftar.")

            elif sync_mode == "Sinkronisasi via Link":
                st.info("Masukkan link profil Instagram (https://www.instagram.com/username) atau link post untuk sinkronisasi.")
                col_link_a, col_link_b = st.columns([2,1])
                profile_link = col_link_a.text_input("Link Profil atau Post (https://www.instagram.com/...)" )
                map_unit = col_link_b.selectbox("Hubungkan ke Unit Kerja", units['nama_unit'].tolist() if not units.empty else ["Pusat"]) 
                if profile_link:
                    # determine if it's a profile or post link
                    if '/p/' in profile_link:
                        # single post - will be processed later via scrape_post_by_link
                        to_process = pd.DataFrame([{"username_ig": profile_link, "nama_unit": map_unit, "is_post_link": True}])
                    else:
                        # profile link - extract username
                        uname = extract_username(profile_link)
                        to_process = pd.DataFrame([{"username_ig": uname, "nama_unit": map_unit}])
            
            else: 
                to_process = units

        if st.button("MULAI SINKRONISASI", use_container_width=True):
            if to_process.empty:
                st.error("âŒ Tidak ada akun yang diproses. Mohon periksa input Anda.")
            else:
                prog_bar = st.progress(0)
                status_container = st.container()
                
                with status_container:
                    st.info("âš¡ Memulai proses penarikan data. Mohon jangan tutup halaman ini.")
                    log_expander = st.expander("Lihat Detail Log Proses", expanded=True)
                    
                    success_count = 0
                    for idx, row in to_process.iterrows():
                        u_clean = extract_username(row['username_ig'])
                        log_expander.write(f"Checking @{u_clean}...")
                        
                        # handle post-links specially if provided in to_process
                        if isinstance(row.get('username_ig'), str) and row.get('username_ig').startswith('http') and row.get('is_post_link', False):
                            # scrape single post by link
                            post_data = scrape_post_by_link(row['username_ig'])
                            if post_data:
                                with engine.begin() as conn:
                                    conn.execute(text("""
                                        INSERT INTO monitoring_pln (
                                            tanggal, bulan, tahun, judul_pemberitaan, 
                                            link_pemberitaan, platform, tipe_konten, 
                                            pic_unit, akun, kategori, likes, comments, views, last_updated
                                        )
                                        VALUES (:t, :b, :y, :j, :l, :p, :tk, :pic, :ak, :kat, :lk, :cm, :vw, :lu)
                                        ON CONFLICT(link_pemberitaan) DO UPDATE SET 
                                            likes=excluded.likes, 
                                            comments=excluded.comments,
                                            views=excluded.views,
                                            last_updated=excluded.last_updated,
                                            kategori=excluded.kategori
                                    """), {
                                        "t": post_data['tanggal'], "b": post_data['bulan'], "y": post_data['tahun'],
                                        "j": post_data['judul_pemberitaan'], "l": post_data['link_pemberitaan'],
                                        "p": post_data['platform'], "tk": post_data['tipe_konten'],
                                        "pic": row['nama_unit'], "ak": post_data['akun'], "kat": post_data['kategori'],
                                        "lk": post_data['likes'], "cm": post_data['comments'], "vw": post_data['views'],
                                        "lu": post_data['last_updated']
                                    })
                                success_count += 1
                        else:
                            new_data = run_scraper(u_clean, row['nama_unit'], sync_limit, sync_month)
                            
                            if not new_data.empty:
                                with engine.begin() as conn:
                                    for _, val in new_data.iterrows():
                                        conn.execute(text("""
                                            INSERT INTO monitoring_pln (
                                                tanggal, bulan, tahun, judul_pemberitaan, 
                                                link_pemberitaan, platform, tipe_konten, 
                                                pic_unit, akun, kategori, likes, comments, views, last_updated
                                            )
                                            VALUES (:t, :b, :y, :j, :l, :p, :tk, :pic, :ak, :kat, :lk, :cm, :vw, :lu)
                                            ON CONFLICT(link_pemberitaan) DO UPDATE SET 
                                                likes=excluded.likes, 
                                                comments=excluded.comments,
                                                views=excluded.views,
                                                last_updated=excluded.last_updated,
                                                kategori=excluded.kategori
                                        """), {
                                            "t": val['tanggal'], "b": val['bulan'], "y": val['tahun'], 
                                            "j": val['judul_pemberitaan'], "l": val['link_pemberitaan'], 
                                            "p": val['platform'], "tk": val['tipe_konten'],
                                            "pic": val['pic_unit'], "ak": val['akun'], 
                                            "kat": val['kategori'],
                                            "lk": val['likes'], "cm": val.get('comments', 0), "vw": val['views'],
                                            "lu": val['last_updated']
                                        })
                                success_count += 1
                        
                        prog_bar.progress((idx + 1) / len(to_process))
                    
                    st.balloons()
                    st.success(f"ðŸ“Š Sinkronisasi Selesai! {success_count} akun berhasil diperbarui.")
                    time.sleep(2)
                    st.rerun()

    # PAGE 4: INPUT MANUAL (Admin)
    elif nav == "Input Manual":
        st.markdown("<div class='header-box'><h1>Input Data Manual</h1><p>Tambahkan record monitoring Korporat atau Influencer</p></div>", unsafe_allow_html=True)
        
        units_list = pd.read_sql(text("SELECT nama_unit FROM daftar_akun_unit"), engine)['nama_unit'].tolist()
        
        tab_form, tab_link = st.tabs(["ðŸ“ Form Manual", "ðŸ”— Scraping dari Link"])
        
        with tab_form:
            with st.form("form_manual_admin", clear_on_submit=True):
                m_kat = st.radio("Jenis Konten", ["Korporat", "Influencer"], horizontal=True, help="Korporat: Postingan akun PLN | Influencer: Tagging dari akun orang lain")
                
                f1, f2 = st.columns(2)
                m_tgl = f1.date_input("Tanggal Konten")
                m_plat = f2.selectbox("Platform", ["Instagram", "Facebook", "TikTok", "YouTube", "Online News"])
                
                m_judul = st.text_area("Caption / Judul Konten")
                m_link = st.text_input("Link URL (Harus Unik)")
                
                f3, f4, f5 = st.columns(3)
                m_unit = f3.selectbox("Unit Kerja Terkait", units_list) if units_list else f3.text_input("Unit Kerja")
                
                m_akun = f4.text_input("Username / Nama Akun", placeholder="Contoh: @jeromepolin atau @pln_id")
                
                m_tipe = f5.selectbox("Tipe Konten", ["Feeds", "Reels", "Video", "Carousel", "News"])
                
                f6, f7, f8 = st.columns(3)
                m_lk = f6.number_input("Jumlah Likes", min_value=0, step=1)
                m_cm = f7.number_input("Jumlah Comments", min_value=0, step=1)
                m_vw = f8.number_input("Jumlah Views", min_value=0, step=1)
                
                if st.form_submit_button("Simpan ke Database"):
                    if m_link and m_unit and m_akun:
                        ml = get_month_order()
                        nama_bulan = ml[m_tgl.month - 1]
                        
                        with engine.begin() as conn:
                            conn.execute(text("""
                                INSERT INTO monitoring_pln (
                                    tanggal, bulan, tahun, judul_pemberitaan, link_pemberitaan, 
                                    platform, tipe_konten, pic_unit, akun, kategori, likes, comments, views, last_updated
                                )
                                VALUES (:t, :b, :y, :j, :l, :p, :tk, :pic, :ak, :kat, :lk, :cm, :vw, :lu)
                                ON CONFLICT(link_pemberitaan) DO UPDATE SET 
                                    likes=excluded.likes, 
                                    comments=excluded.comments,
                                    views=excluded.views,
                                    kategori=excluded.kategori,
                                    last_updated=excluded.last_updated
                            """), {
                                "t": m_tgl.strftime("%d/%m/%Y"), 
                                "b": nama_bulan, 
                                "y": str(m_tgl.year), 
                                "j": clean_txt(m_judul), 
                                "l": m_link, 
                                "p": m_plat, 
                                "tk": m_tipe, 
                                "pic": m_unit, 
                                "ak": m_akun,
                                "kat": m_kat, 
                                "lk": m_lk, 
                                "cm": m_cm,
                                "vw": m_vw, 
                                "lu": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                            })
                        st.success(f"Berhasil menyimpan data {m_kat}: {m_akun}!")
                    else:
                        st.error("Mohon isi Link, Akun, dan Unit.")
        
        with tab_link:
            st.subheader("Scraping dari Link Instagram")
            st.info("Masukkan link Instagram post untuk mengambil data otomatis")
            
            link_input = st.text_input("Masukkan link Instagram (contoh: https://www.instagram.com/p/ABC123...)", placeholder="https://www.instagram.com/p/")
            
            if link_input:
                if st.button("ðŸ” Scrape Post", use_container_width=True):
                    with st.spinner("Mengambil data..."):
                        post_data = scrape_post_by_link(link_input)
                        
                        if post_data:
                            st.success("Data berhasil diambil!")
                            
                            col_l1, col_l2 = st.columns(2)
                            with col_l1:
                                st.write(f"**Username:** {post_data['akun']}")
                                st.write(f"**Tanggal:** {post_data['tanggal']}")
                                st.write(f"**Tipe:** {post_data['tipe_konten']}")
                            
                            with col_l2:
                                st.write(f"**Likes:** {post_data['likes']}")
                                st.write(f"**Comments:** {post_data['comments']}")
                                st.write(f"**Views:** {post_data['views']}")
                            
                            st.write(f"**Caption:** {post_data['judul_pemberitaan']}")
                            
                            if st.button("âœ… Simpan ke Database", use_container_width=True):
                                with engine.begin() as conn:
                                    conn.execute(text("""
                                        INSERT INTO monitoring_pln (
                                            tanggal, bulan, tahun, judul_pemberitaan, link_pemberitaan,
                                            platform, tipe_konten, pic_unit, akun, kategori, likes, comments, views, last_updated
                                        )
                                        VALUES (:t, :b, :y, :j, :l, :p, :tk, :pic, :ak, :kat, :lk, :cm, :vw, :lu)
                                        ON CONFLICT(link_pemberitaan) DO UPDATE SET
                                            likes=excluded.likes,
                                            comments=excluded.comments,
                                            views=excluded.views,
                                            last_updated=excluded.last_updated
                                    """), {
                                        "t": post_data["tanggal"],
                                        "b": post_data["bulan"],
                                        "y": post_data["tahun"],
                                        "j": post_data["judul_pemberitaan"],
                                        "l": post_data["link_pemberitaan"],
                                        "p": post_data["platform"],
                                        "tk": post_data["tipe_konten"],
                                        "pic": post_data["pic_unit"],
                                        "ak": post_data["akun"],
                                        "kat": post_data["kategori"],
                                        "lk": post_data["likes"],
                                        "cm": post_data["comments"],
                                        "vw": post_data["views"],
                                        "lu": post_data["last_updated"]
                                    })
                                st.success("âœ… Data berhasil disimpan ke database!")
                                time.sleep(1)
                                st.rerun()

    # PAGE 4: PENGAJUAN DOKUMENTASI (Admin)
    elif nav == "Pengajuan Dokumentasi":
        st.markdown("<div class='header-box'><h1>ðŸ“‹ Kelola Pengajuan Dokumentasi</h1><p>Proses dan monitor pengajuan dokumentasi dari user</p></div>", unsafe_allow_html=True)
        
        df_admin = pd.read_sql(text("SELECT * FROM pengajuan_dokumentasi ORDER BY created_at DESC"), engine)
        
        if df_admin.empty:
            st.info("Belum ada pengajuan dokumentasi.")
        else:
            col1, col2, col3 = st.columns(3)
            with col1:
                st.metric("Total Pengajuan", len(df_admin))
            with col2:
                pending_count = len(df_admin[df_admin['status'] == 'pending'])
                st.metric("Pending", pending_count)
            with col3:
                approved_count = len(df_admin[df_admin['status'] == 'approved'])
                st.metric("Disetujui", approved_count)
            
            st.markdown("---")
            
            filter_status = st.selectbox("Filter berdasarkan status", ["Semua", "pending", "approved", "rejected", "done"])
            
            if filter_status != "Semua":
                df_admin = df_admin[df_admin['status'] == filter_status]
            
            for _, row in df_admin.iterrows():
                # display with localized status labels and clearer action layout
                status_color = {"pending": "ðŸŸ¡", "approved": "ðŸŸ¢", "rejected": "ðŸ”´", "done": "âœ…", "submitted": "ðŸ“¤"}
                status_label_map = {"pending": "Menunggu", "approved": "Disetujui", "rejected": "Ditolak", "done": "Selesai", "submitted": "Dilaporkan"}
                display_status = status_label_map.get((row.get('status') or '').lower(), (row.get('status') or '').capitalize())

                with st.expander(f"{status_color.get(row['status'], 'âšª')} ID {row['id']} â€¢ {row['nama_pengaju']} â€” {row['tanggal_acara']} ({display_status})"):
                    left, right = st.columns([2,1])
                    with left:
                        st.markdown(f"**Nama Pengaju:** {row['nama_pengaju']}")
                        
                        # Display phone number with WhatsApp link
                        phone = row.get('nomor_telpon', '')
                        if phone:
                            phone_clean = ''.join(filter(str.isdigit, phone))
                            if phone_clean.startswith('0'):
                                phone_clean = '62' + phone_clean[1:]
                            elif not phone_clean.startswith('62'):
                                phone_clean = '62' + phone_clean
                            wa_link = f"https://wa.me/{phone_clean}"
                            st.markdown(f"**Nomor Telpon:** [{phone}]({wa_link}) ðŸ“± [Hubungi via WhatsApp](https://wa.me/{phone_clean}?text=Halo%20{row['nama_pengaju']}%2C%20ini%20terkait%20pengajuan%20dokumentasi%20Anda)")
                        else:
                            st.markdown(f"**Nomor Telpon:** -")
                        
                        st.markdown(f"**Unit:** {row['unit']}")
                        st.markdown(f"**Tanggal Acara:** {row['tanggal_acara']}")
                        st.markdown(f"**Waktu:** {row['jam_mulai']} - {row['jam_selesai']}")
                        st.markdown(f"**Output Type:** {row['output_type']} | **Biaya:** Rp {row.get('biaya', 0):,.0f}")
                        if row.get('notes'):
                            st.markdown(f"**Catatan:** {row.get('notes')}" )
                        if row.get('output_link_drive'):
                            st.markdown(f"**Link Drive Pengajuan:** {row.get('output_link_drive')}")

                    with right:
                        st.markdown(f"**Status:** **{display_status}**")
                        # hasil input always visible for admin (pre-filled if exists)
                        h1_key = f"hasil1_input_{row['id']}"
                        h2_key = f"hasil2_input_{row['id']}"
                        h3_key = f"hasil3_input_{row['id']}"
                        existing_h1 = row.get('hasil_link_1') or ''
                        existing_h2 = row.get('hasil_link_2') or ''
                        existing_h3 = row.get('hasil_link_3') or ''
                        st.write("Masukkan hingga 3 link hasil (opsional). Kosongkan bila tidak ada.")
                        hasil1 = st.text_input("Link Hasil 1", value=existing_h1, key=h1_key)
                        hasil2 = st.text_input("Link Hasil 2", value=existing_h2, key=h2_key)
                        hasil3 = st.text_input("Link Hasil 3", value=existing_h3, key=h3_key)
                        if st.button("ðŸ’¾ Simpan Hasil & Tandai Selesai", key=f"savehasil_{row['id']}"):
                            # allow saving even if all empty (but warn)
                            if not (hasil1 or hasil2 or hasil3):
                                st.warning("Tidak ada link dimasukkan. Jika ingin menandai selesai tanpa link, konfirmasi lagi.")
                            # persist
                            combined = ' | '.join([l for l in [hasil1, hasil2, hasil3] if l]) if (hasil1 or hasil2 or hasil3) else (row.get('hasil_link_drive') or '')
                            with engine.begin() as conn:
                                conn.execute(text("UPDATE pengajuan_dokumentasi SET status='done', hasil_link_drive=:h_all, hasil_link_1=:h1, hasil_link_2=:h2, hasil_link_3=:h3, updated_at=:u WHERE id=:id"),
                                             {"h_all": combined, "h1": hasil1, "h2": hasil2, "h3": hasil3, "u": datetime.now().strftime('%Y-%m-%d %H:%M:%S'), "id": row['id']})
                                conn.execute(text("UPDATE dokumentasi_calendar SET status='done' WHERE pengajuan_id=:id"), {"id": row['id']})
                            st.success("Hasil tersimpan dan status diubah menjadi 'Selesai'. Kalender diperbarui.")
                            st.rerun()

                    st.markdown("---")
                    act1, act2, act3 = st.columns(3)
                    with act1:
                        if st.button("âœ… Setujui", key=f"approve_{row['id']}"):
                            with engine.begin() as conn:
                                conn.execute(text("UPDATE pengajuan_dokumentasi SET status='approved', updated_at=:u WHERE id=:id"),
                                            {"u": datetime.now().strftime('%Y-%m-%d %H:%M:%S'), "id": row['id']})
                                existing = conn.execute(text("SELECT COUNT(*) as cnt FROM dokumentasi_calendar WHERE pengajuan_id = :id"), {"id": row['id']}).fetchone()[0]
                                if existing == 0:
                                    conn.execute(text("INSERT INTO dokumentasi_calendar (pengajuan_id, tanggal, nama_kegiatan, unit, status, created_at) VALUES (:pid, :tgl, :nk, :unit, :st, :ca)"), {
                                        "pid": row['id'],
                                        "tgl": row['tanggal_acara'],
                                        "nk": row['nama_pengaju'],
                                        "unit": row['unit'],
                                        "st": 'approved',
                                        "ca": datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                                    })
                                conn.execute(text("UPDATE pengajuan_dokumentasi SET added_to_calendar=1 WHERE id=:id"), {"id": row['id']})
                            st.success("Pengajuan disetujui dan ditambahkan ke kalender.")
                            st.rerun()
                    
                    with act2:
                        if st.button("âŒ Tolak", key=f"reject_{row['id']}"):
                            with engine.begin() as conn:
                                conn.execute(text("UPDATE pengajuan_dokumentasi SET status='rejected', updated_at=:u WHERE id=:id"),
                                            {"u": datetime.now().strftime('%Y-%m-%d %H:%M:%S'), "id": row['id']})
                            st.error("Pengajuan ditolak.")
                            st.rerun()

                    with act3:
                        if st.button("ðŸ“… Tambah ke Kalender", key=f"cal_{row['id']}"):
                            with engine.begin() as conn:
                                existing = conn.execute(text("SELECT COUNT(*) as cnt FROM dokumentasi_calendar WHERE pengajuan_id = :id"), {"id": row['id']}).fetchone()[0]
                                if existing == 0:
                                    conn.execute(text("INSERT INTO dokumentasi_calendar (pengajuan_id, tanggal, nama_kegiatan, unit, status, created_at) VALUES (:pid, :tgl, :nk, :unit, :st, :ca)"), {
                                        "pid": row['id'],
                                        "tgl": row['tanggal_acara'],
                                        "nk": row['nama_pengaju'],
                                        "unit": row['unit'],
                                        "st": row['status'],
                                        "ca": datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                                    })
                                    conn.execute(text("UPDATE pengajuan_dokumentasi SET added_to_calendar=1 WHERE id=:id"), {"id": row['id']})
                                    st.success("Event ditambahkan ke kalender.")
                                else:
                                    st.info("Event sudah ada di kalender.")
                            st.rerun()

            # --- Laporan Hasil Dokumentasi (ringkasan untuk admin) ---
            st.markdown("---")
            st.subheader("ðŸ“‚ Laporan Hasil Dokumentasi")
            try:
                df_hasil = pd.read_sql(text("SELECT id, nama_pengaju, unit, tanggal_acara, status, hasil_link_drive, hasil_link_1, hasil_link_2, hasil_link_3, updated_at FROM pengajuan_dokumentasi WHERE (hasil_link_drive IS NOT NULL OR hasil_link_1 IS NOT NULL OR hasil_link_2 IS NOT NULL OR hasil_link_3 IS NOT NULL) ORDER BY updated_at DESC"), engine)
                if df_hasil.empty:
                    st.info("Belum ada hasil dokumentasi yang diupload oleh Admin.")
                else:
                    # display combined links nicely
                    def combine_links(r):
                        parts = []
                        for k in ['hasil_link_1','hasil_link_2','hasil_link_3']:
                            v = r.get(k)
                            if v and str(v).strip():
                                parts.append(v)
                        if parts:
                            return '\n'.join(parts)
                        return r.get('hasil_link_drive') or ''

                    df_hasil['Hasil_Links'] = df_hasil.apply(combine_links, axis=1)
                    display_df = df_hasil[['id','nama_pengaju','unit','tanggal_acara','status','Hasil_Links','updated_at']]
                    st.dataframe(display_df, use_container_width=True, hide_index=True)
                    # allow quick edit of hasil links from admin summary
                    with st.expander("Edit/Perbarui Link Hasil (Pilih ID dan masukkan link baru)"):
                        edit_id = st.number_input("ID Pengajuan", min_value=1, value=int(df_hasil.iloc[0]['id'])) if not df_hasil.empty else st.number_input("ID Pengajuan", min_value=1, value=1)
                        # fetch current links for selected id
                        cur_row = None
                        try:
                            cur_row = pd.read_sql(text("SELECT hasil_link_drive, hasil_link_1, hasil_link_2, hasil_link_3 FROM pengajuan_dokumentasi WHERE id=:id"), engine, params={"id": int(edit_id)})
                            if not cur_row.empty:
                                cur_row = cur_row.iloc[0]
                        except Exception:
                            cur_row = None

                        cur_h1 = cur_row['hasil_link_1'] if cur_row is not None and 'hasil_link_1' in cur_row else ''
                        cur_h2 = cur_row['hasil_link_2'] if cur_row is not None and 'hasil_link_2' in cur_row else ''
                        cur_h3 = cur_row['hasil_link_3'] if cur_row is not None and 'hasil_link_3' in cur_row else ''
                        cur_all = cur_row['hasil_link_drive'] if cur_row is not None and 'hasil_link_drive' in cur_row else ''

                        new_h1 = st.text_input("Hasil Link 1 (Drive/URL)", value=cur_h1)
                        new_h2 = st.text_input("Hasil Link 2 (Drive/URL)", value=cur_h2)
                        new_h3 = st.text_input("Hasil Link 3 (Drive/URL)", value=cur_h3)
                        if st.button("ðŸ’¾ Perbarui Link Hasil"):
                            with engine.begin() as conn:
                                combined = ' | '.join([l for l in [new_h1, new_h2, new_h3] if l]) if (new_h1 or new_h2 or new_h3) else cur_all
                                conn.execute(text("UPDATE pengajuan_dokumentasi SET hasil_link_drive=:h_all, hasil_link_1=:h1, hasil_link_2=:h2, hasil_link_3=:h3, updated_at=:u WHERE id=:id"), {"h_all": combined, "h1": new_h1, "h2": new_h2, "h3": new_h3, "u": datetime.now().strftime('%Y-%m-%d %H:%M:%S'), "id": int(edit_id)})
                            st.success("Link hasil diperbarui")
                            st.rerun()
            except Exception as e:
                st.error(f"Error loading laporan hasil: {e}")

    # PAGE 5: KALENDER DOKUMENTASI
    elif nav == "Kalender Dokumentasi":
        st.markdown("<div class='header-box'><h1>ðŸ“… Kalender Dokumentasi</h1><p>Lihat jadwal kegiatan dokumentasi yang sudah disetujui</p></div>", unsafe_allow_html=True)
        
        df_cal = pd.read_sql(text("SELECT * FROM dokumentasi_calendar ORDER BY tanggal DESC"), engine)
        
        if df_cal.empty:
            st.info("Kalender masih kosong")
        else:
            col1, col2 = st.columns(2)
            with col1:
                st.metric("Total Event", len(df_cal))
            with col2:
                st.metric("Unit", df_cal['unit'].nunique())
            
            st.markdown("---")
            # Choose month/year
            today = datetime.now()
            sel_col1, sel_col2 = st.columns([1,1])
            with sel_col1:
                sel_month = st.selectbox("Bulan", list(range(1,13)), index=today.month-1)
            with sel_col2:
                sel_year = st.number_input("Tahun", min_value=2000, max_value=2100, value=today.year)

            events = df_cal.to_dict('records')
            cal_html = render_month_calendar(sel_year, sel_month, events)
            st.markdown(cal_html, unsafe_allow_html=True)

            st.markdown("---")
            st.subheader("Daftar Kegiatan (Detail)")
            st.dataframe(df_cal[['tanggal', 'nama_kegiatan', 'unit', 'status']], use_container_width=True, hide_index=True)

    # PAGE 6: PENGATURAN UNIT
    elif nav == "Pengaturan Unit":
        st.markdown("<div class='header-box'><h1>âš™ï¸ Pengaturan Unit</h1><p>Kelola daftar unit dan akun Instagram resmi</p></div>", unsafe_allow_html=True)
        
        ca, cb = st.columns([1, 1.5])
        
        with ca:
            st.subheader("Tambah Unit")
            with st.form("add_u", clear_on_submit=True):
                un = st.text_input("Nama Unit")
                ig = st.text_input("Username IG")
                if st.form_submit_button("Daftarkan"):
                    if un and ig:
                        with engine.begin() as conn:
                            conn.execute(text("INSERT OR IGNORE INTO daftar_akun_unit (nama_unit, username_ig) VALUES (:n, :u)"), 
                                       {"n": un, "u": extract_username(ig)})
                        st.rerun()
        
        with cb:
            st.subheader("Daftar Unit Terdaftar")
            ud = pd.read_sql(text("SELECT * FROM daftar_akun_unit"), engine)
            st.dataframe(ud, use_container_width=True, hide_index=True)
            
            if not ud.empty:
                target = st.selectbox("Hapus Unit", ud['username_ig'].tolist())
                if st.button("ðŸ—‘ï¸ Hapus Akun Permanen"):
                    with engine.begin() as conn:
                        conn.execute(text("DELETE FROM monitoring_pln WHERE akun = :u OR akun = :ua"), 
                                    {"u": target, "ua": f"@{target}"})
                        conn.execute(text("DELETE FROM daftar_akun_unit WHERE username_ig = :u"), {"u": target})
                    st.success(f"Akun {target} dihapus.")
                    time.sleep(1)
                    st.rerun()

    # PAGE 7: MANAJEMEN USER
    elif nav == "Manajemen User":
        st.markdown("<div class='header-box'><h1>ðŸ‘¥ Manajemen User</h1><p>Kelola akun user dan roles</p></div>", unsafe_allow_html=True)
        
        tab_view, tab_add, tab_edit, tab_reset = st.tabs(["ðŸ‘ï¸ Lihat User", "âž• Tambah User", "âœï¸ Edit/Hapus User", "ðŸ” Reset Password"])
        
        with tab_view:
            st.subheader("Daftar Seluruh User")
            try:
                df_users = pd.read_sql(text("SELECT id, username, role, unit, created_at FROM users ORDER BY created_at DESC"), engine)
                if not df_users.empty:
                    st.metric("Total User", len(df_users))
                    st.dataframe(df_users, use_container_width=True, hide_index=True)
                else:
                    st.info("Belum ada user terdaftar.")
            except Exception as e:
                st.error(f"Error: {e}")
        
        with tab_add:
            st.subheader("Tambah User Baru")
            with st.form("form_add_user"):
                new_username = st.text_input("Username", help="Username unik untuk login")
                new_password = st.text_input("Password", type="password", help="Minimal 6 karakter")
                new_password_conf = st.text_input("Konfirmasi Password", type="password")
                new_role = st.selectbox("Role", ["user", "admin"])
                new_unit = st.text_input("Unit Kerja", help="Unit yang bertanggung jawab")
                
                if st.form_submit_button("âž• Tambah User", use_container_width=True):
                    if not new_username or len(new_username) < 3:
                        st.error("Username minimal 3 karakter")
                    elif new_password != new_password_conf:
                        st.error("Password tidak cocok")
                    elif len(new_password) < 6:
                        st.error("Password minimal 6 karakter")
                    elif register_user(new_username, new_password, new_role, new_unit):
                        st.success(f"âœ… User '{new_username}' berhasil ditambahkan sebagai {new_role}")
                    else:
                        st.error("Username sudah terdaftar atau error lainnya")
        
        with tab_edit:
            st.subheader("Edit atau Hapus User")
            try:
                df_users = pd.read_sql(text("SELECT id, username, role, unit FROM users WHERE role != 'admin' ORDER BY username"), engine)
                if not df_users.empty:
                    selected_user = st.selectbox("Pilih User", df_users['username'].tolist())
                    selected_row = df_users[df_users['username'] == selected_user].iloc[0]
                    selected_id = selected_row['id']
                    
                    col1, col2, col3 = st.columns(3)
                    
                    with col1:
                        new_role = st.selectbox("Role Baru", ["user", "admin"], index=0 if selected_row['role'] == 'user' else 1)
                    
                    with col2:
                        new_unit = st.text_input("Unit Kerja Baru", value=selected_row['unit'])
                    
                    with col3:
                        st.write("")
                        st.write("")
                        if st.button("ðŸ’¾ Update User"):
                            with engine.begin() as conn:
                                conn.execute(text("UPDATE users SET role=:r, unit=:u WHERE id=:id"), 
                                           {"r": new_role, "u": new_unit, "id": selected_id})
                            st.success(f"âœ… User '{selected_user}' berhasil diupdate")
                            time.sleep(1)
                            st.rerun()
                    
                    st.markdown("---")
                    if st.button("ðŸ—‘ï¸ Hapus User Permanen", use_container_width=True):
                        with engine.begin() as conn:
                            conn.execute(text("DELETE FROM users WHERE id=:id"), {"id": selected_id})
                            conn.execute(text("DELETE FROM pengajuan_dokumentasi WHERE user_id=:uid"), {"uid": selected_id})
                        st.warning(f"âš ï¸ User '{selected_user}' dan semua pengajuannya telah dihapus")
                        time.sleep(1)
                        st.rerun()
                else:
                    st.info("Tidak ada user (selain admin) untuk diedit.")
            except Exception as e:
                st.error(f"Error: {e}")
        
        with tab_reset:
            st.subheader("Reset Password User")
            st.info("ðŸ’¡ Admin dapat mereset password user. User akan diberi password baru yang harus diubah saat login berikutnya.")
            try:
                df_users = pd.read_sql(text("SELECT id, username, role, unit FROM users WHERE role != 'admin' ORDER BY username"), engine)
                if not df_users.empty:
                    selected_user_reset = st.selectbox("Pilih User untuk Reset Password", df_users['username'].tolist(), key="reset_user")
                    selected_row_reset = df_users[df_users['username'] == selected_user_reset].iloc[0]
                    selected_id_reset = selected_row_reset['id']
                    
                    st.write(f"**Username:** {selected_user_reset}")
                    st.write(f"**Role:** {selected_row_reset['role']}")
                    st.write(f"**Unit:** {selected_row_reset['unit']}")
                    
                    col_reset1, col_reset2 = st.columns(2)
                    
                    with col_reset1:
                        new_temp_password = st.text_input("Password Baru (Temporary)", value="temppass123", help="Password sementara yang diberikan ke user")
                    
                    with col_reset2:
                        st.write("")
                        st.write("")
                        if st.button("ðŸ” Reset Password User", use_container_width=True):
                            if len(new_temp_password) < 6:
                                st.error("Password minimal 6 karakter")
                            else:
                                new_pass_hash = verify_password(new_temp_password)
                                with engine.begin() as conn:
                                    conn.execute(text("UPDATE users SET password=:p WHERE id=:id"), 
                                               {"p": new_pass_hash, "id": selected_id_reset})
                                st.success(f"âœ… Password '{selected_user_reset}' berhasil direset ke: **{new_temp_password}**")
                                st.warning(f"âš ï¸ Pastikan user mengubah password ini saat login berikutnya!")
                                time.sleep(2)
                                st.rerun()
                else:
                    st.info("Tidak ada user (selain admin) untuk di-reset passwordnya.")
            except Exception as e:
                st.error(f"Error: {e}")

    # PAGE 8: PENGATURAN ADMIN
    elif nav == "Pengaturan Admin":
        st.markdown("<div class='header-box'><h1>ðŸ” Pengaturan Admin</h1><p>Kelola password dan keamanan akun admin</p></div>", unsafe_allow_html=True)
        
        tab_pwd, tab_info = st.tabs(["ðŸ”‘ Ubah Password", "â„¹ï¸ Informasi Admin"])
        
        with tab_pwd:
            st.subheader("Ubah Password Admin")
            with st.form("form_change_pwd"):
                old_pass = st.text_input("Password Lama", type="password")
                new_pass = st.text_input("Password Baru", type="password", help="Minimal 6 karakter")
                new_pass_conf = st.text_input("Konfirmasi Password Baru", type="password")
                
                if st.form_submit_button("ðŸ” Ubah Password", use_container_width=True):
                    # Verify old password
                    admin_id = st.session_state.user['id']
                    old_pass_hash = verify_password(old_pass)
                    check = pd.read_sql(text("SELECT id FROM users WHERE id=:id AND password=:p"), 
                                       engine, params={"id": admin_id, "p": old_pass_hash})
                    
                    if check.empty:
                        st.error("âŒ Password lama tidak sesuai")
                    elif new_pass != new_pass_conf:
                        st.error("âŒ Password baru tidak cocok")
                    elif len(new_pass) < 6:
                        st.error("âŒ Password baru minimal 6 karakter")
                    else:
                        new_pass_hash = verify_password(new_pass)
                        with engine.begin() as conn:
                            conn.execute(text("UPDATE users SET password=:p WHERE id=:id"), 
                                       {"p": new_pass_hash, "id": admin_id})
                        st.success("âœ… Password berhasil diubah!")
                        time.sleep(2)
                        st.rerun()
        
        with tab_info:
            st.subheader("Informasi Admin")
            admin_user = st.session_state.user
            st.write(f"**Username:** {admin_user['username']}")
            st.write(f"**Role:** {admin_user['role']}")
            st.write(f"**Unit:** {admin_user['unit']}")
            st.write(f"**Login Terakhir:** {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}")

# === USER PAGES ===
else:  # user role
    
    # PAGE 1: USER DASHBOARD
    if nav == "Dashboard User":
        st.markdown("<div class='header-box'><h1>ðŸ“Š Dashboard User</h1><p>Ringkasan pengajuan dokumentasi Anda</p></div>", unsafe_allow_html=True)
        
        user_id = st.session_state.user['id']
        df_user_pengajuan = pd.read_sql(text("SELECT * FROM pengajuan_dokumentasi WHERE user_id = :uid"), 
                                       engine, params={"uid": user_id})
        
        col1, col2, col3, col4 = st.columns(4)
        
        try:
            with col1:
                st.metric("ðŸ“‹ Total Pengajuan", len(df_user_pengajuan))
            with col2:
                pending = len(df_user_pengajuan[df_user_pengajuan['status'] == 'pending'])
                st.metric("â³ Menunggu", pending)
            with col3:
                approved = len(df_user_pengajuan[df_user_pengajuan['status'] == 'approved'])
                st.metric("âœ… Disetujui", approved)
            with col4:
                done = len(df_user_pengajuan[df_user_pengajuan['status'] == 'done'])
                st.metric("ðŸŽ‰ Selesai", done)
        except Exception as e:
            st.error(f"Error loading metrics: {e}")
        
        st.markdown("---")
        st.subheader("ðŸ“Œ Pengajuan Terbaru")
        if not df_user_pengajuan.empty:
            df_recent = df_user_pengajuan.sort_values('created_at', ascending=False).head(5)
            display_cols = ['tanggal_acara', 'output_type', 'status', 'deadline_penyelesaian']
            st.dataframe(df_recent[display_cols], use_container_width=True, hide_index=True)
            
            st.markdown("---")
            st.subheader("ðŸ“Š Ringkasan Status")
            status_summary = df_user_pengajuan['status'].value_counts().to_dict()
            col_s1, col_s2, col_s3 = st.columns(3)
            with col_s1:
                st.info(f"**Pending:** {status_summary.get('pending', 0)}")
            with col_s2:
                st.success(f"**Approved:** {status_summary.get('approved', 0)}")
            with col_s3:
                st.success(f"**Done:** {status_summary.get('done', 0)}")
        else:
            st.info("Anda belum membuat pengajuan apapun. Silakan buat pengajuan baru di menu 'Pengajuan Dokumentasi'.")

    # PAGE 2: KALENDER DOKUMENTASI (User version)
    elif nav == "Kalender Dokumentasi":
        st.markdown("<div class='header-box'><h1>ðŸ“… Kalender Dokumentasi (Publik)</h1><p>Lihat semua jadwal kegiatan dokumentasi yang sudah tercatat â€” semua user dapat melihat bookingan.</p></div>", unsafe_allow_html=True)

        user_id = st.session_state.user['id']
        # By default show all events (public). User can optionally filter to only their submissions.
        show_only_mine = st.checkbox("Tampilkan hanya pengajuan saya", value=False)

        try:
            if show_only_mine:
                df_cal = pd.read_sql(text("""
                    SELECT c.*, p.user_id, p.nama_pengaju FROM dokumentasi_calendar c
                    LEFT JOIN pengajuan_dokumentasi p ON c.pengajuan_id = p.id
                    WHERE p.user_id = :uid
                    ORDER BY c.tanggal DESC
                """), engine, params={"uid": user_id})
            else:
                df_cal = pd.read_sql(text("""
                    SELECT c.*, p.user_id, p.nama_pengaju FROM dokumentasi_calendar c
                    LEFT JOIN pengajuan_dokumentasi p ON c.pengajuan_id = p.id
                    ORDER BY c.tanggal DESC
                """), engine)
        except Exception as e:
            st.error(f"Error loading calendar: {e}")
            df_cal = pd.DataFrame()

        if df_cal.empty:
            st.info("Belum ada event di kalender.")
        else:
            col1, col2 = st.columns(2)
            with col1:
                st.metric("Total Event", len(df_cal))
            with col2:
                st.metric("Unit", df_cal['unit'].nunique())

            st.markdown("---")
            # Choose month/year
            today = datetime.now()
            sel_col1, sel_col2 = st.columns([1,1])
            with sel_col1:
                sel_month = st.selectbox("Bulan", list(range(1,13)), index=today.month-1)
            with sel_col2:
                sel_year = st.number_input("Tahun", min_value=2000, max_value=2100, value=today.year)

            events = df_cal.to_dict('records')
            cal_html = render_month_calendar(sel_year, sel_month, events)
            st.markdown(cal_html, unsafe_allow_html=True)

            st.markdown("---")
            st.subheader("Daftar Kegiatan (Detail)")
            # Show who submitted (nama_pengaju) for transparency
            display_cols = ['tanggal', 'nama_kegiatan', 'unit', 'status', 'nama_pengaju'] if 'nama_pengaju' in df_cal.columns else ['tanggal', 'nama_kegiatan', 'unit', 'status']
            st.dataframe(df_cal[display_cols], use_container_width=True, hide_index=True)

    # PAGE 3: PENGAJUAN DOKUMENTASI (User)
    elif nav == "Pengajuan Dokumentasi":
        st.markdown("<div class='header-box'><h1>ðŸ“‹ Pengajuan Dokumentasi</h1><p>Ajukan permintaan dokumentasi kegiatan Anda</p></div>", unsafe_allow_html=True)
        
        user_id = st.session_state.user['id']
        
        # Form submission
        st.subheader("ðŸ“ Buat Pengajuan Baru")
        st.info("ðŸ’¡ Isi semua field yang diperlukan untuk mengajukan dokumentasi kegiatan Anda.")
        
        try:
            units_list = pd.read_sql(text("SELECT nama_unit FROM daftar_akun_unit ORDER BY nama_unit"), engine)['nama_unit'].tolist()
        except Exception:
            units_list = []
        
        with st.form("form_pengajuan", clear_on_submit=True):
            col_a, col_b = st.columns(2)
            
            with col_a:
                nama_pengaju = st.text_input("ðŸ‘¤ Nama Pengaju", value=st.session_state.user['username'], help="Nama orang yang mengajukan dokumentasi")
            with col_b:
                nomor_telpon = st.text_input("ðŸ“± Nomor Telepon", placeholder="+62xxx atau 08xx", help="Nomor telepon yang dapat dihubungi untuk konfirmasi")
            
            col_c, col_d = st.columns(2)
            with col_c:
                unit = st.selectbox("ðŸ¢ Unit Kerja", units_list) if units_list else st.text_input("ðŸ¢ Unit Kerja")
            with col_d:
                tanggal_acara = st.date_input("ðŸ“… Tanggal Acara", help="Kapan kegiatan dilaksanakan?")
            
            col_e, col_f = st.columns(2)
            with col_e:
                output_type = st.selectbox("ðŸ“¸ Output Dokumentasi", ["Video", "Foto", "Link Saja", "Tidak Perlu"], help="Jenis hasil dokumentasi yang dibutuhkan")
            with col_f:
                jam_mulai = st.time_input("ðŸ• Jam Mulai", help="Jam mulai kegiatan")
            
            col_g, col_h = st.columns(2)
            with col_g:
                jam_selesai = st.time_input("ðŸ•‘ Jam Selesai", help="Jam selesai kegiatan")
            with col_h:
                biaya = st.number_input("ðŸ’° Estimasi Biaya (Rp)", min_value=0, step=10000, value=0, help="Perkiraan biaya dokumentasi")
            
            col_i, col_j = st.columns(2)
            with col_i:
                output_link_drive = st.text_input("ðŸ”— Link Drive Kegiatan", placeholder="https://drive.google.com/...", help="Link folder Google Drive tempat file kegiatan disimpan")
            with col_j:
                deadline = st.date_input("â° Deadline Penyelesaian", help="Kapan hasil dokumentasi harus selesai?")
            
            notes = st.text_area("ðŸ“Œ Catatan / Keterangan Tambahan", placeholder="Jelaskan detail kegiatan atau instruksi khusus (opsional)", height=100)
            
            if st.form_submit_button("ðŸ“¤ Kirim Pengajuan", use_container_width=True):
                # Validation
                errors = []
                if not nama_pengaju or len(nama_pengaju) < 3:
                    errors.append("Nama pengaju minimal 3 karakter")
                if not nomor_telpon or len(nomor_telpon) < 8:
                    errors.append("Nomor telepon harus diisi dengan benar (minimal 8 karakter)")
                if not unit:
                    errors.append("Unit kerja harus dipilih")
                if tanggal_acara is None:
                    errors.append("Tanggal acara harus diisi")
                if jam_mulai is None or jam_selesai is None:
                    errors.append("Jam mulai dan jam selesai harus diisi")
                
                if errors:
                    st.error("âŒ Silakan perbaiki error berikut:\n" + "\n".join([f"- {e}" for e in errors]))
                else:
                    try:
                        now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                        with engine.begin() as conn:
                            conn.execute(text("""
                                INSERT INTO pengajuan_dokumentasi (
                                    nama_pengaju, user_id, nomor_telpon, unit, tanggal_acara, jam_mulai, jam_selesai,
                                    output_link_drive, output_type, biaya, deadline_penyelesaian,
                                    status, notes, created_at, updated_at
                                ) VALUES (:np, :uid, :tel, :u, :ta, :jm, :js, :od, :ot, :bi, :dl, 'pending', :nt, :ca, :ua)
                            """), {
                                "np": nama_pengaju,
                                "uid": user_id,
                                "tel": nomor_telpon,
                                "u": unit,
                                "ta": tanggal_acara.strftime("%d/%m/%Y"),
                                "jm": jam_mulai.strftime("%H:%M"),
                                "js": jam_selesai.strftime("%H:%M"),
                                "od": output_link_drive,
                                "ot": output_type,
                                "bi": biaya,
                                "dl": deadline.strftime("%d/%m/%Y"),
                                "nt": notes,
                                "ca": now,
                                "ua": now
                            })
                        st.success("âœ… Pengajuan berhasil dikirim! Admin akan menanggapi dalam waktu 1-2 hari kerja.")
                        st.balloons()
                        time.sleep(2)
                        st.rerun()
                    except Exception as e:
                        st.error(f"âŒ Error saat mengirim pengajuan: {e}")
        
        st.markdown("---")
        st.subheader("ðŸ“‹ Riwayat Pengajuan Anda")
        
        try:
            df_pengajuan = pd.read_sql(text("SELECT * FROM pengajuan_dokumentasi WHERE user_id = :uid ORDER BY created_at DESC"), 
                                       engine, params={"uid": user_id})
            
            if df_pengajuan.empty:
                st.info("Anda belum membuat pengajuan apapun")
            else:
                # Show stats
                status_summary = df_pengajuan['status'].value_counts().to_dict()
                col_s1, col_s2, col_s3, col_s4 = st.columns(4)
                with col_s1:
                    st.metric("Total", len(df_pengajuan))
                with col_s2:
                    st.metric("Menunggu", status_summary.get('pending', 0))
                with col_s3:
                    st.metric("Disetujui", status_summary.get('approved', 0))
                with col_s4:
                    st.metric("Selesai", status_summary.get('done', 0))
                
                st.markdown("---")
                
                # Show expanders for each submission
                status_emoji = {"pending": "ðŸŸ¡", "approved": "ðŸŸ¢", "rejected": "ðŸ”´", "done": "âœ…", "submitted": "ðŸ“¤"}
                status_label_map = {"pending": "Menunggu", "approved": "Disetujui", "rejected": "Ditolak", "done": "Selesai", "submitted": "Dilaporkan"}
                for idx, (_, row) in enumerate(df_pengajuan.iterrows()):
                    st_status = (row.get('status') or '').lower()
                    status_text = status_label_map.get(st_status, (row.get('status') or '').upper())
                    
                    can_edit = st_status == 'pending'  # Only allow edit if status is pending
                    
                    with st.expander(f"{status_emoji.get(st_status, 'âšª')} {row['tanggal_acara']} â€” {row['output_type']} ({status_text})"):
                        col1, col2 = st.columns(2)
                        with col1:
                            st.write(f"**Nama Pengaju:** {row['nama_pengaju']}")
                            st.write(f"**Nomor Telpon:** {row.get('nomor_telpon', '-')}")
                            st.write(f"**Unit:** {row['unit']}")
                            st.write(f"**Tanggal Acara:** {row['tanggal_acara']}")
                            st.write(f"**Waktu:** {row['jam_mulai']} - {row['jam_selesai']}")
                        with col2:
                            st.write(f"**Output:** {row['output_type']}")
                            st.write(f"**Biaya:** Rp {row.get('biaya', 0):,.0f}")
                            st.write(f"**Deadline:** {row.get('deadline_penyelesaian', '-')}")
                            st.write(f"**Status:** **{status_text}**")
                        
                        if row.get('notes'):
                            st.write(f"**Catatan:** {row['notes']}")
                        
                        if row['hasil_link_drive']:
                            st.markdown(f"**Hasil Dokumentasi:**\n{row['hasil_link_drive']}")
                        
                        # Edit option for pending submissions
                        if can_edit:
                            st.markdown("---")
                            st.info("âœï¸ Anda dapat mengedit pengajuan ini sebelum disetujui oleh Admin")
                            
                            with st.form(f"edit_pengajuan_{row['id']}"):
                                edit_nama = st.text_input("Nama Pengaju", value=row['nama_pengaju'])
                                edit_telpon = st.text_input("Nomor Telpon", value=row.get('nomor_telpon', ''))
                                edit_unit = st.text_input("Unit Kerja", value=row['unit'])
                                edit_output = st.selectbox("Output Dokumentasi", ["Video", "Foto", "Link Saja", "Tidak Perlu"], 
                                                           index=["Video", "Foto", "Link Saja", "Tidak Perlu"].index(row['output_type']))
                                edit_biaya = st.number_input("Estimasi Biaya (Rp)", min_value=0, value=int(row.get('biaya', 0)))
                                edit_notes = st.text_area("Catatan", value=row.get('notes', ''), height=80)
                                
                                if st.form_submit_button("ðŸ’¾ Simpan Perubahan", use_container_width=True):
                                    try:
                                        with engine.begin() as conn:
                                            conn.execute(text("""
                                                UPDATE pengajuan_dokumentasi 
                                                SET nama_pengaju=:np, nomor_telpon=:tel, unit=:u, output_type=:ot, 
                                                    biaya=:bi, notes=:nt, updated_at=:ua 
                                                WHERE id=:id
                                            """), {
                                                "np": edit_nama,
                                                "tel": edit_telpon,
                                                "u": edit_unit,
                                                "ot": edit_output,
                                                "bi": edit_biaya,
                                                "nt": edit_notes,
                                                "ua": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                                "id": row['id']
                                            })
                                        st.success("âœ… Pengajuan berhasil diperbarui!")
                                        time.sleep(1)
                                        st.rerun()
                                    except Exception as e:
                                        st.error(f"âŒ Error: {e}")
        except Exception as e:
            st.error(f"Error loading submissions: {e}")

    # PAGE 4: DOKUMENTASI MANUAL
    elif nav == "Dokumentasi Manual":
        st.markdown("<div class='header-box'><h1>ðŸ“¸ Dokumentasi (Hanya Lihat)</h1><p>Hasil dokumentasi akan diinput oleh Admin. Anda hanya dapat melihat status dan hasil setelah Admin mengunggahnya.</p></div>", unsafe_allow_html=True)

        user_id = st.session_state.user['id']

        df_user = pd.read_sql(text("SELECT id, tanggal_acara, unit, status, nomor_telpon, hasil_link_drive, hasil_link_1, hasil_link_2, hasil_link_3 FROM pengajuan_dokumentasi WHERE user_id = :uid ORDER BY created_at DESC"),
                      engine, params={"uid": user_id})

        if df_user.empty:
            st.info("Anda belum membuat pengajuan apapun. Silakan buat pengajuan di menu 'Pengajuan Dokumentasi'.")
        else:
            st.subheader("Riwayat Pengajuan & Hasil Dokumentasi")
            for _, row in df_user.iterrows():
                status_emoji = {"pending": "ðŸŸ¡", "approved": "ðŸŸ¢", "rejected": "ðŸ”´", "done": "âœ…", "submitted": "ðŸ“¤"}
                st.markdown(f"**{status_emoji.get(row['status'], 'âšª')} {row['tanggal_acara']} â€” {row['unit']}**")
                
                # Show phone number with WhatsApp link
                phone = row.get('nomor_telpon', '')
                if phone:
                    phone_clean = ''.join(filter(str.isdigit, phone))
                    if phone_clean.startswith('0'):
                        phone_clean = '62' + phone_clean[1:]
                    elif not phone_clean.startswith('62'):
                        phone_clean = '62' + phone_clean
                    wa_link = f"https://wa.me/{phone_clean}"
                    st.caption(f"ðŸ“± Nomor: [{phone}]({wa_link})")
                
                if row['status'] == 'done':
                    links = []
                    for k in ('hasil_link_1','hasil_link_2','hasil_link_3'):
                        v = row.get(k)
                        if v and str(v).strip():
                            links.append(v)
                    # fallback to legacy combined field
                    if not links and row.get('hasil_link_drive'):
                        links = [row.get('hasil_link_drive')]

                    if links:
                                st.markdown("- âœ… Hasil tersedia:")
                                for l in links:
                                    # label link type
                                    lbl = "Link"
                                    if 'drive.google' in l or 'drive.google.com' in l or 'docs.google' in l:
                                        lbl = 'Drive'
                                    elif 'youtube.com' in l or 'youtu.be' in l:
                                        lbl = 'Video'
                                    else:
                                        # image file heuristic
                                        if any(l.lower().endswith(ext) for ext in ('.jpg', '.jpeg', '.png', '.webp', '.gif')) or 'googleusercontent' in l or 'photos.app.goo' in l:
                                            lbl = 'Foto'
                                    st.markdown(f"  - **{lbl}:** [{l}]({l})")
                    else:
                        st.markdown("- âœ… Selesai â€” namun belum ada link hasil yang diunggah oleh Admin.")
                elif row['status'] == 'approved':
                    st.markdown("- â³ Pengajuan telah disetujui. Menunggu Admin mengunggah hasil dokumentasi.")
                elif row['status'] == 'pending':
                    st.markdown("- â³ Pengajuan sedang menunggu persetujuan Admin.")
                elif row['status'] == 'rejected':
                    st.markdown("- âŒ Pengajuan ditolak oleh Admin.")
                elif row['status'] == 'submitted':
                    st.markdown("- ðŸ“¤ Hasil sudah dilaporkan (status: submitted). Menunggu review Admin.")
                else:
                    st.markdown(f"- Status: {row['status']}")
                st.markdown("---")

# ============ SCRAPER ENGINE ============
def run_scraper(username, unit_name, limit=20, target_month="Semua", kategori_input="Korporat"):
    """Scrape Instagram posts"""
    clean_username = extract_username(username)
    L = instaloader.Instaloader()
    L.context.user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    
    try:
        profile = instaloader.Profile.from_username(L.context, clean_username)
        results = []
        month_map = {i+1: m for i, m in enumerate(get_month_order())}
        count = 0
        
        for post in profile.get_posts():
            cur_month = month_map[post.date.month]
            if target_month != "Semua" and cur_month != target_month:
                continue
            if count >= limit:
                break
            
            is_vid = post.is_video
            results.append({
                "tanggal": post.date.strftime("%d/%m/%Y"),
                "bulan": cur_month,
                "tahun": str(post.date.year),
                "judul_pemberitaan": clean_txt(post.caption[:500] if post.caption else "Konten Visual"),
                "link_pemberitaan": f"https://www.instagram.com/p/{post.shortcode}/",
                "platform": "Instagram",
                "tipe_konten": "Reels" if is_vid else "Feeds",
                "pic_unit": unit_name,
                "akun": f"@{clean_username}",
                "kategori": kategori_input,
                "likes": post.likes,
                "comments": post.comments,
                "views": post.video_view_count if is_vid else 0,
                "last_updated": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
            })
            count += 1
            time.sleep(1)
        
        return pd.DataFrame(results)
    except Exception as e:
        st.error(f"Gagal mengambil data @{clean_username}: {e}")
        return pd.DataFrame()

def scrape_post_by_link(post_url):
    """Scrape single Instagram post by URL"""
    try:
        shortcode = post_url.split('/p/')[-1].rstrip('/')
        L = instaloader.Instaloader()
        L.context.user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        post = instaloader.Post.from_shortcode(L.context, shortcode)
        
        is_vid = post.is_video
        month_map = {i+1: m for i, m in enumerate(get_month_order())}
        
        result = {
            "tanggal": post.date.strftime("%d/%m/%Y"),
            "bulan": month_map[post.date.month],
            "tahun": str(post.date.year),
            "judul_pemberitaan": clean_txt(post.caption[:500] if post.caption else "Konten Visual"),
            "link_pemberitaan": f"https://www.instagram.com/p/{post.shortcode}/",
            "platform": "Instagram",
            "tipe_konten": "Reels" if is_vid else "Feeds",
            "pic_unit": "Manual Input",
            "akun": f"@{post.owner.username}",
            "kategori": "Manual",
            "likes": post.likes,
            "comments": post.comments,
            "views": post.video_view_count if is_vid else 0,
            "last_updated": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }
        return result
    except Exception as e:
        st.error(f"Gagal mengambil data dari URL: {e}")
        return None

# ============ FOOTER ============
st.markdown("---")
st.markdown("""
    <div class='footer'>
        âš¡ <b>PLN UID LAMPUNG DIGITAL MONITORING SYSTEM</b> â€¢ 2025<br>
        &copy; PLN Komunikasi dan TJSL. All Rights Reserved.
    </div>
    
    <div class='system-badge'>
        <div class='status-dot'></div>
        <span class='status-text'>SYSTEM ONLINE</span>
    </div>
""", unsafe_allow_html=True)

# ============ FOOTER ============
st.markdown("""
<div class='app-footer'>
    <div class='footer-content'>
        <div class='footer-section'>
            <h4>PLN UID LAMPUNG</h4>
            <p>Digital Monitoring System v2.0</p>
        </div>
        <div class='footer-section'>
            <h4>Support</h4>
            <p>Contact Admin untuk bantuan teknis</p>
        </div>
        <div class='footer-divider'></div>
        <div class='footer-bottom'>
            <p>&copy; 2024 PT PLN (Persero) UIU Lampung. All rights reserved.</p>
            <p style='font-size: 11px; margin-top: 8px;'>Sistem ini dilindungi dan digunakan secara internal.</p>
        </div>
    </div>
</div>

<style>
.app-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: linear-gradient(135deg, #0a2342 0%, #001a2e 100%);
    border-top: 1px solid rgba(255,255,255,0.1);
    padding: 20px 32px;
    width: 100%;
    box-shadow: 0 -4px 12px rgba(0,0,0,0.15);
}

.footer-content {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    align-items: center;
}

.footer-section {
    color: white;
}

.footer-section h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 600;
    color: #fbbf24;
}

.footer-section p {
    margin: 0;
    font-size: 12px;
    color: rgba(255,255,255,0.7);
}

.footer-divider {
    grid-column: 1 / -1;
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 15px 0;
}

.footer-bottom {
    grid-column: 1 / -1;
    text-align: center;
    padding-top: 10px;
}

.footer-bottom p {
    margin: 0;
    font-size: 11px;
    color: rgba(255,255,255,0.6);
}

.stMain {
    padding-bottom: 180px !important;
}

@media (max-width: 768px) {
    .app-footer {
        padding: 16px 16px;
    }
    .footer-content {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    .footer-section h4 {
        font-size: 12px;
    }
    .footer-section p {
        font-size: 11px;
    }
}
</style>
""", unsafe_allow_html=True)