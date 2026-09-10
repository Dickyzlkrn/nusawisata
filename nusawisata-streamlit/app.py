"""
NusaWisata — Prototype Sistem Rekomendasi Pariwisata Indonesia
Metodologi: K-Means Clustering (User Segmentation) + User-Based Collaborative Filtering (UBCF Cosine Similarity)
UI Redesign: Modern, Premium, Clean, & Responsive Web Application Experience.
"""

from pathlib import Path
import folium
import numpy as np
import pandas as pd
import plotly.express as px
import streamlit as st
from streamlit_folium import st_folium

from components.maps import create_destinations_map, create_single_destination_map
from services.cf_service import evaluate_system, recommend_for_user
from services.kmeans_service import (
    extract_user_features,
    run_user_clustering,
)

# ─────────────────────────────────────────────────────────────
# 1. Page Configuration & Custom Modern Styling (Plus Jakarta Sans)
# ─────────────────────────────────────────────────────────────
st.set_page_config(
    page_title="NusaWisata — Rekomendasi Wisata Indonesia",
    page_icon="🏝️",
    layout="wide",
    initial_sidebar_state="expanded",
)

st.markdown(
    """
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    html, body, [class*="css"] {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Container Spacing */
    .block-container {
        padding-top: 1.8rem;
        padding-bottom: 2.5rem;
        padding-left: 2.2rem;
        padding-right: 2.2rem;
        max-width: 1300px;
    }

    /* Modern Card Styles */
    .nw-card {
        background: #1E293B;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 1.35rem;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.35);
        transition: transform 0.2s ease, border-color 0.2s ease;
        margin-bottom: 1.25rem;
    }
    .nw-card:hover {
        border-color: rgba(59, 130, 246, 0.4);
    }

    /* Stat Box with Icon */
    .nw-stat-card {
        background: linear-gradient(145deg, #1E293B, #162032);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 16px;
        padding: 1.2rem 1.4rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
    }
    .nw-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: rgba(37, 99, 235, 0.15);
        color: #38BDF8;
    }
    .nw-stat-val {
        font-size: 1.6rem;
        font-weight: 800;
        color: #F8FAFC;
        line-height: 1.2;
    }
    .nw-stat-label {
        font-size: 0.8rem;
        color: #94A3B8;
        font-weight: 500;
        margin-top: 2px;
    }

    /* Badges */
    .nw-badge {
        display: inline-block;
        padding: 0.22rem 0.65rem;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .nw-badge-blue { background: rgba(37, 99, 235, 0.2); color: #60A5FA; border: 1px solid rgba(59, 130, 246, 0.3); }
    .nw-badge-emerald { background: rgba(16, 185, 129, 0.2); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .nw-badge-amber { background: rgba(245, 158, 11, 0.2); color: #FBBF24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .nw-badge-purple { background: rgba(168, 85, 247, 0.2); color: #C084FC; border: 1px solid rgba(168, 85, 247, 0.3); }

    /* Match Pill */
    .nw-match-pill {
        background: linear-gradient(90deg, rgba(16, 185, 129, 0.2), rgba(37, 99, 235, 0.2));
        border: 1px solid rgba(16, 185, 129, 0.4);
        color: #34D399;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Hero Banner */
    .nw-hero {
        background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 20px;
        padding: 2.2rem 2.4rem;
        margin-bottom: 1.8rem;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.4);
        position: relative;
        overflow: hidden;
    }
    .nw-hero h1 {
        font-size: 2.1rem;
        font-weight: 800;
        color: #F8FAFC;
        margin-bottom: 0.5rem;
        letter-spacing: -0.02em;
    }
    .nw-hero p {
        font-size: 1.05rem;
        color: #94A3B8;
        max-width: 680px;
        line-height: 1.5;
        margin-bottom: 0;
    }

    /* Section Headers */
    .nw-section-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #F8FAFC;
        margin-bottom: 0.35rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .nw-section-subtitle {
        font-size: 0.88rem;
        color: #94A3B8;
        margin-bottom: 1.25rem;
    }

    /* Sidebar Styling */
    [data-testid="stSidebar"] {
        background-color: #0F172A;
        border-right: 1px solid rgba(255, 255, 255, 0.06);
    }
    .nw-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0.5rem 0.2rem 1.2rem 0.2rem;
    }
    .nw-brand-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #F8FAFC;
        letter-spacing: -0.02em;
    }
    .nw-brand-subtitle {
        font-size: 0.72rem;
        color: #38BDF8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* User Profile Bottom Box */
    .nw-user-bottom {
        background: #1E293B;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        padding: 0.9rem;
        margin-top: 1.5rem;
    }
    .nw-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563EB, #7C3AED);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: white;
        font-size: 0.95rem;
    }
    </style>
    """,
    unsafe_allow_html=True,
)

# ─────────────────────────────────────────────────────────────
# 2. Data Initialization & Real-Time Session State
# ─────────────────────────────────────────────────────────────
DATA_DIR = Path(__file__).parent / "data"


@st.cache_data
def load_csv_data():
    u = pd.read_csv(DATA_DIR / "users.csv")
    d = pd.read_csv(DATA_DIR / "destinations.csv")
    r = pd.read_csv(DATA_DIR / "ratings.csv")
    return u, d, r


raw_users, raw_destinations, raw_ratings = load_csv_data()

if "ratings_df" not in st.session_state:
    st.session_state["ratings_df"] = raw_ratings.copy()

if "users_df" not in st.session_state:
    st.session_state["users_df"] = raw_users.copy()

if "destinations_df" not in st.session_state:
    st.session_state["destinations_df"] = raw_destinations.copy()

if "active_user_id" not in st.session_state:
    st.session_state["active_user_id"] = 2  # Budi Santoso (15 ratings)

# Dialog trigger state
if "dialog_target_dest_id" not in st.session_state:
    st.session_state["dialog_target_dest_id"] = None


# K-Means clustering caching
@st.cache_data(show_spinner=False)
def get_user_clusters_cached(ratings_token: int, k: int = 3):
    feats = extract_user_features(st.session_state["ratings_df"], st.session_state["destinations_df"])
    res = run_user_clustering(feats, k=k, random_state=42)
    return feats, res


ratings_hash = len(st.session_state["ratings_df"]) + int(st.session_state["ratings_df"]["rating"].sum())
user_features_df, clustering_result = get_user_clusters_cached(ratings_hash, k=3)
user_clusters = clustering_result.get("user_clusters", {})

# User cluster descriptor
CLUSTER_DESCRIPTIONS = {
    1: "Wisatawan Budaya & Berkelanjutan",
    2: "Penjelajah Alam & Bahari Terpilih",
    3: "Wisatawan Urban & Rekreasi Santai",
}

# Active User Context
active_uid = st.session_state["active_user_id"]
active_user_match = st.session_state["users_df"][st.session_state["users_df"]["user_id"] == active_uid]

if not active_user_match.empty:
    active_user_name = active_user_match.iloc[0]["name"]
    active_user_email = active_user_match.iloc[0]["email"]
else:
    active_user_name = f"Wisatawan #{active_uid}"
    active_user_email = f"user{active_uid}@example.com"

active_cluster_id = user_clusters.get(active_uid, 1)
active_cluster_name = CLUSTER_DESCRIPTIONS.get(active_cluster_id, f"Klaster #{active_cluster_id}")

active_user_ratings = st.session_state["ratings_df"][st.session_state["ratings_df"]["user_id"] == active_uid]
active_ratings_count = len(active_user_ratings)
active_avg_rating = float(active_user_ratings["rating"].mean()) if active_ratings_count > 0 else 0.0

# ─────────────────────────────────────────────────────────────
# 3. Interactive Modals (st.dialog)
# ─────────────────────────────────────────────────────────────
@st.dialog("📍 Informasi Lengkap Destinasi")
def show_destination_detail_dialog(dest_id: int):
    dest_match = st.session_state["destinations_df"][st.session_state["destinations_df"]["destination_id"] == dest_id]
    if dest_match.empty:
        st.error("Destinasi tidak ditemukan.")
        return

    row = dest_match.iloc[0]
    p_formatted = f"Rp {float(row['price']):,.0f}".replace(",", ".")

    st.markdown(f"### {row['name']}")
    st.markdown(
        f"""
        <div style="display: flex; gap: 6px; margin-bottom: 12px;">
            <span class="nw-badge nw-badge-blue">{row['category']}</span>
            <span class="nw-badge nw-badge-emerald">📍 {row['province']}</span>
            <span class="nw-badge nw-badge-amber">⭐ {row['google_rating']} / 5.0</span>
        </div>
        """,
        unsafe_allow_html=True,
    )

    st.write(f"**💵 Tiket Masuk:** `{p_formatted}`")
    st.write(f"**🌐 Koordinat:** `{row['latitude']}, {row['longitude']}`")
    st.markdown("**📝 Deskripsi:**")
    st.write(row.get("description", "Keindahan panorama pariwisata unggulan Indonesia."))

    st.markdown("**🗺️ Peta Lokasi (OpenStreetMap):**")
    m_single = create_single_destination_map(row, zoom_start=11)
    st_folium(m_single, width=None, height=220, returned_objects=[])

    if st.button("Tutup", use_container_width=True):
        st.rerun()


@st.dialog("⭐ Beri Rating Destinasi")
def show_rating_dialog(dest_id: int):
    dest_match = st.session_state["destinations_df"][st.session_state["destinations_df"]["destination_id"] == dest_id]
    if dest_match.empty:
        st.error("Destinasi tidak ditemukan.")
        return

    row = dest_match.iloc[0]
    existing_r = st.session_state["ratings_df"][
        (st.session_state["ratings_df"]["user_id"] == active_uid)
        & (st.session_state["ratings_df"]["destination_id"] == dest_id)
    ]
    curr_val = int(existing_r.iloc[0]["rating"]) if not existing_r.empty else 5

    st.markdown(f"Berikan ulasan Anda untuk: **{row['name']}**")
    st.caption(f"Input oleh: **{active_user_name}** (Klaster #{active_cluster_id})")

    new_val = st.slider("Rating Bintang (1 - 5):", 1, 5, curr_val, 1)

    c1, c2 = st.columns(2)
    if c1.button("💾 Simpan Rating", use_container_width=True, type="primary"):
        df_r = st.session_state["ratings_df"].copy()
        if not existing_r.empty:
            df_r.loc[
                (df_r["user_id"] == active_uid) & (df_r["destination_id"] == dest_id), "rating"
            ] = new_val
        else:
            new_entry = pd.DataFrame([{"user_id": int(active_uid), "destination_id": int(dest_id), "rating": int(new_val)}])
            df_r = pd.concat([df_r, new_entry], ignore_index=True)

        st.session_state["ratings_df"] = df_r
        st.toast(f"Rating {new_val} ⭐ berhasil disimpan!")
        st.rerun()

    if c2.button("Batal", use_container_width=True):
        st.rerun()


# ─────────────────────────────────────────────────────────────
# 4. Modern Sidebar Navigation & Profile Component
# ─────────────────────────────────────────────────────────────
with st.sidebar:
    st.markdown(
        """
        <div class="nw-brand">
            <span style="font-size: 2.2rem;">🏝️</span>
            <div>
                <div class="nw-brand-title">NusaWisata</div>
                <div class="nw-brand-subtitle">Sistem Rekomendasi Cerdas</div>
            </div>
        </div>
        """,
        unsafe_allow_html=True,
    )
    st.markdown("<hr style='border-color: rgba(255,255,255,0.06); margin: 0 0 1rem 0;'>", unsafe_allow_html=True)

    nav_selection = st.radio(
        "Menu Navigasi",
        options=[
            "🏠 Beranda",
            "🧭 Destinasi",
            "⭐ Rekomendasi",
            "📝 Rating Saya",
            "🗺️ Peta",
            "📈 Evaluasi Model",
            "👤 Pengaturan / Profil",
            "⚙️ Admin Dataset",
        ],
        label_visibility="collapsed",
    )

    st.markdown("<hr style='border-color: rgba(255,255,255,0.06); margin: 1.5rem 0 1rem 0;'>", unsafe_allow_html=True)

    # Sticky User Profile at bottom of sidebar
    avatar_initials = "".join([part[0].upper() for part in active_user_name.split()[:2]]) or "W"
    st.markdown(
        f"""
        <div class="nw-user-bottom">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="nw-avatar">{avatar_initials}</div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.88rem; font-weight: 700; color: #F8FAFC; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {active_user_name}
                    </div>
                    <div style="font-size: 0.72rem; color: #64748B; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {active_user_email}
                    </div>
                </div>
            </div>
            <div style="margin-top: 10px; display: flex; gap: 6px;">
                <span class="nw-badge nw-badge-blue">Klaster #{active_cluster_id}</span>
                <span class="nw-badge nw-badge-emerald">{active_ratings_count} Rating</span>
            </div>
        </div>
        """,
        unsafe_allow_html=True,
    )

# ─────────────────────────────────────────────────────────────
# 5. Global Header Helper
# ─────────────────────────────────────────────────────────────
def render_header(greeting_title: str, subtitle: str):
    st.markdown(
        f"""
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.4rem; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 1rem;">
            <div>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: #F8FAFC; margin: 0;">{greeting_title}</h2>
                <div style="font-size: 0.88rem; color: #94A3B8; margin-top: 4px;">{subtitle}</div>
            </div>
            <div style="display: flex; gap: 8px;">
                <span class="nw-badge nw-badge-purple">User ID: #{active_uid}</span>
                <span class="nw-badge nw-badge-blue">{active_cluster_name}</span>
            </div>
        </div>
        """,
        unsafe_allow_html=True,
    )


# Precompute Recommendations for active user
recs_engine_result = recommend_for_user(
    user_id=active_uid,
    ratings_df=st.session_state["ratings_df"],
    destinations_df=st.session_state["destinations_df"],
    users_df=st.session_state["users_df"],
    user_clusters=user_clusters,
    k_neighbors=10,
    limit=6,
    within_cluster=True,
    min_ratings_threshold=3,
)
recommended_destinations = recs_engine_result.get("recommendations", pd.DataFrame())
is_cold_start = recs_engine_result.get("is_cold_start", False)
recs_count = len(recommended_destinations)

# ─────────────────────────────────────────────────────────────
# 6. PAGE: Beranda (Main Consumer Web App Portal)
# ─────────────────────────────────────────────────────────────
if nav_selection == "🏠 Beranda":
    render_header(
        greeting_title=f"Selamat Datang, {active_user_name}! 👋",
        subtitle=f"Anda telah menilai {active_ratings_count} destinasi. Rekomendasi di bawah disesuaikan dengan pola preferensi Klaster #{active_cluster_id}.",
    )

    # 1. Hero & Search Banner
    st.markdown(
        """
        <div class="nw-hero">
            <h1>Temukan Destinasi yang Sesuai untuk Anda</h1>
            <p>
                Jelajahi surga tersembunyi, kekayaan budaya, dan panorama bahari Indonesia dengan dukungan 
                kecerdasan buatan berbasis <b>K-Means User Segmentation</b> & <b>Collaborative Filtering</b>.
            </p>
        </div>
        """,
        unsafe_allow_html=True,
    )

    # 2. Interactive Search & Quick Filters
    df_dests = st.session_state["destinations_df"].copy()

    with st.container():
        st.markdown('<div class="nw-section-title">🔍 Pencarian Cepat Destinasi</div>', unsafe_allow_html=True)
        q_col1, q_col2, q_col3, q_col4 = st.columns([2.5, 1.2, 1.2, 1.2])

        search_kw = q_col1.text_input("Nama Destinasi", placeholder="Ketik nama tempat (contoh: Bromo, Labuan Bajo...)", label_visibility="collapsed")
        all_cats = ["Semua Kategori"] + sorted(df_dests["category"].dropna().unique().tolist())
        sel_cat = q_col2.selectbox("Kategori", all_cats, label_visibility="collapsed")
        all_provs = ["Semua Provinsi"] + sorted(df_dests["province"].dropna().unique().tolist())
        sel_prov = q_col3.selectbox("Provinsi", all_provs, label_visibility="collapsed")
        max_p = int(df_dests["price"].max())
        sel_max_price = q_col4.selectbox(
            "Maks. Harga Tiket",
            options=[max_p, 25000, 50000, 100000, 200000],
            format_func=lambda x: "Semua Harga" if x == max_p else f"≤ Rp {x:,.0f}".replace(",", "."),
            label_visibility="collapsed",
        )

    # 3. Personal Stat Cards
    st.markdown("<div style='height: 0.8rem;'></div>", unsafe_allow_html=True)
    st.markdown('<div class="nw-section-title">📊 Statistik Personal Anda</div>', unsafe_allow_html=True)

    s1, s2, s3, s4 = st.columns(4)
    with s1:
        st.markdown(
            f"""
            <div class="nw-stat-card">
                <div class="nw-stat-icon">⭐</div>
                <div>
                    <div class="nw-stat-val">{active_ratings_count}</div>
                    <div class="nw-stat-label">Rating Diberikan</div>
                </div>
            </div>
            """,
            unsafe_allow_html=True,
        )
    with s2:
        st.markdown(
            f"""
            <div class="nw-stat-card">
                <div class="nw-stat-icon">📈</div>
                <div>
                    <div class="nw-stat-val">{active_avg_rating:.1f}</div>
                    <div class="nw-stat-label">Rata-Rata Rating</div>
                </div>
            </div>
            """,
            unsafe_allow_html=True,
        )
    with s3:
        st.markdown(
            f"""
            <div class="nw-stat-card">
                <div class="nw-stat-icon">👥</div>
                <div>
                    <div class="nw-stat-val">#{active_cluster_id}</div>
                    <div class="nw-stat-label">{active_cluster_name}</div>
                </div>
            </div>
            """,
            unsafe_allow_html=True,
        )
    with s4:
        st.markdown(
            f"""
            <div class="nw-stat-card">
                <div class="nw-stat-icon">🎯</div>
                <div>
                    <div class="nw-stat-val">{recs_count}</div>
                    <div class="nw-stat-label">Rekomendasi Siap</div>
                </div>
            </div>
            """,
            unsafe_allow_html=True,
        )

    st.markdown("<div style='height: 1.5rem;'></div>", unsafe_allow_html=True)

    # 4. Section: Rekomendasi Untuk Anda
    st.markdown('<div class="nw-section-title">⭐ Rekomendasi Khusus Untuk Anda</div>', unsafe_allow_html=True)
    st.markdown(
        f'<div class="nw-section-subtitle">Dihitung otomatis melalui <b>K-Means + UBCF</b>. Hanya menyajikan destinasi yang belum pernah Anda beri rating.</div>',
        unsafe_allow_html=True,
    )

    if is_cold_start:
        st.warning(
            f"💡 **Status Cold-Start:** Anda baru memiliki {active_ratings_count} rating (< 3 rating). "
            f"Menyajikan destinasi terfavorit nasional sebagai fallback sebelum profil preferensi terbentuk sempurna."
        )

    if recommended_destinations.empty:
        st.info("Belum ada destinasi yang cocok dengan kombinasi filter Anda saat ini.")
    else:
        # Display 6 modern cards in 3 columns
        rec_cols = st.columns(3)
        for idx, (_, r_row) in enumerate(recommended_destinations.iterrows()):
            with rec_cols[idx % 3]:
                rank_no = r_row.get("recommendation_rank", idx + 1)
                pred_r = float(r_row.get("predicted_rating", r_row.get("google_rating", 4.5)))
                match_pct = min(99, int((pred_r / 5.0) * 100))
                p_idr = f"Rp {float(r_row['price']):,.0f}".replace(",", ".")

                st.markdown(
                    f"""
                    <div class="nw-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span class="nw-badge nw-badge-blue">#{rank_no} Pilihan Utama</span>
                            <span class="nw-match-pill">✨ {match_pct}% Cocok</span>
                        </div>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #F8FAFC; margin: 4px 0 2px 0; line-height: 1.3;">
                            {r_row['name']}
                        </h3>
                        <div style="font-size: 0.82rem; color: #94A3B8; margin-bottom: 10px;">
                            📍 {r_row['province']} • <span style="color: #34D399; font-weight: 600;">{r_row['category']}</span>
                        </div>
                        <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; padding: 8px 12px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.7rem; color: #94A3B8; font-weight: 600;">PREDIKSI SKOR</div>
                                <div style="font-size: 1.25rem; font-weight: 800; color: #F59E0B;">⭐ {pred_r:.2f} <span style="font-size: 0.75rem; color: #64748B;">/ 5.0</span></div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.7rem; color: #94A3B8; font-weight: 600;">TIKET</div>
                                <div style="font-size: 0.95rem; font-weight: 700; color: #38BDF8;">{p_idr}</div>
                            </div>
                        </div>
                        <div style="font-size: 0.75rem; color: #CBD5E1; margin-bottom: 12px; line-height: 1.4;">
                            💡 {r_row.get('reason', 'Destinasi paling relevan dengan profil Anda.')}
                        </div>
                    </div>
                    """,
                    unsafe_allow_html=True,
                )
                b_col1, b_col2 = st.columns(2)
                if b_col1.button("ℹ️ Detail", key=f"rec_dtl_{r_row['destination_id']}", use_container_width=True):
                    show_destination_detail_dialog(int(r_row["destination_id"]))
                if b_col2.button("⭐ Beri Rating", key=f"rec_rate_{r_row['destination_id']}", use_container_width=True):
                    show_rating_dialog(int(r_row["destination_id"]))

    # 5. Section: Peta Interaktif Rekomendasi
    st.markdown("<div style='height: 1.5rem;'></div>", unsafe_allow_html=True)
    st.markdown('<div class="nw-section-title">🗺️ Peta Interaktif Rekomendasi & Unggulan</div>', unsafe_allow_html=True)
    st.markdown(
        '<div class="nw-section-subtitle">Visualisasi lokasi geografis destinasi rekomendasi pada peta Leaflet / OpenStreetMap.</div>',
        unsafe_allow_html=True,
    )

    if not recommended_destinations.empty:
        rec_map_obj = create_destinations_map(recommended_destinations, zoom_start=5, is_recommendation=True)
        st_folium(rec_map_obj, width=None, height=380, returned_objects=[])

    # 6. Section: Jelajahi Destinasi Populer
    st.markdown("<div style='height: 1.5rem;'></div>", unsafe_allow_html=True)
    st.markdown('<div class="nw-section-title">🧭 Jelajahi Destinasi Wisata Lainnya</div>', unsafe_allow_html=True)
    st.markdown(
        '<div class="nw-section-subtitle">Katalog destinasi wisata Indonesia yang dapat Anda saring dan berikan ulasan.</div>',
        unsafe_allow_html=True,
    )

    # Filtered catalog
    catalog_df = df_dests.copy()
    if search_kw.strip():
        catalog_df = catalog_df[catalog_df["name"].str.contains(search_kw.strip(), case=False, na=False)]
    if sel_cat != "Semua Kategori":
        catalog_df = catalog_df[catalog_df["category"] == sel_cat]
    if sel_prov != "Semua Provinsi":
        catalog_df = catalog_df[catalog_df["province"] == sel_prov]
    catalog_df = catalog_df[catalog_df["price"] <= sel_max_price]

    cat_cols = st.columns(3)
    for i, (_, c_row) in enumerate(catalog_df.head(6).iterrows()):
        with cat_cols[i % 3]:
            p_cat_idr = f"Rp {float(c_row['price']):,.0f}".replace(",", ".")
            st.markdown(
                f"""
                <div class="nw-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span class="nw-badge nw-badge-blue">{c_row['category']}</span>
                        <span style="color: #F59E0B; font-weight: 700; font-size: 0.88rem;">⭐ {c_row['google_rating']:.1f}</span>
                    </div>
                    <h4 style="font-size: 1.05rem; font-weight: 700; color: #F8FAFC; margin: 4px 0 2px 0;">{c_row['name']}</h4>
                    <div style="font-size: 0.8rem; color: #94A3B8; margin-bottom: 8px;">📍 {c_row['province']}</div>
                    <div style="font-size: 0.95rem; font-weight: 800; color: #38BDF8; margin-bottom: 10px;">{p_cat_idr}</div>
                </div>
                """,
                unsafe_allow_html=True,
            )
            c_b1, c_b2 = st.columns(2)
            if c_b1.button("Detail", key=f"cat_dtl_{c_row['destination_id']}", use_container_width=True):
                show_destination_detail_dialog(int(c_row["destination_id"]))
            if c_b2.button("Beri Rating", key=f"cat_rt_{c_row['destination_id']}", use_container_width=True):
                show_rating_dialog(int(c_row["destination_id"]))

    # 7. Section: Riwayat Rating Terkini
    st.markdown("<div style='height: 1.5rem;'></div>", unsafe_allow_html=True)
    st.markdown('<div class="nw-section-title">📝 Aktivitas Rating Terkini Anda</div>', unsafe_allow_html=True)

    if active_user_ratings.empty:
        st.info("Anda belum memiliki riwayat rating. Silakan beri ulasan pada destinasi di atas untuk mulai membentuk profil preferensi!")
    else:
        recent_ratings = active_user_ratings.tail(5).merge(st.session_state["destinations_df"], on="destination_id", how="left")
        st.dataframe(
            recent_ratings[["destination_id", "name", "category", "province", "price", "rating"]],
            use_container_width=True,
            height=200,
        )

    # 8. Footer
    st.markdown("<div style='height: 2.5rem;'></div>", unsafe_allow_html=True)
    st.markdown(
        """
        <div style="border-top: 1px solid rgba(255,255,255,0.06); padding: 1.5rem 0; text-align: center; color: #64748B; font-size: 0.8rem;">
            <b>NusaWisata</b> © 2026 — Prototype Sistem Rekomendasi Pariwisata Cerdas Indonesia.<br>
            Implementasi Metode Penelitian Ilmiah: K-Means Clustering + User-Based Collaborative Filtering (UBCF).
        </div>
        """,
        unsafe_allow_html=True,
    )


# ─────────────────────────────────────────────────────────────
# 7. PAGE: Destinasi (Katalog Lengkap)
# ─────────────────────────────────────────────────────────────
elif nav_selection == "🧭 Destinasi":
    render_header("🧭 Eksplorasi Katalog Destinasi", "Cari, saring, dan pelajari destinasi wisata di seluruh pelosok Indonesia.")

    df_all_dest = st.session_state["destinations_df"].copy()

    with st.expander("🔍 Filter Destinasi Wisata", expanded=True):
        fc1, fc2, fc3 = st.columns([2, 1, 1])
        f_keyword = fc1.text_input("Cari Nama Tempat:", placeholder="Masukkan nama destinasi...")
        f_cat = fc2.selectbox("Filter Kategori:", ["Semua Kategori"] + sorted(df_all_dest["category"].dropna().unique().tolist()))
        f_prov = fc3.selectbox("Filter Provinsi:", ["Semua Provinsi"] + sorted(df_all_dest["province"].dropna().unique().tolist()))

        fc4, fc5, fc6 = st.columns([2, 1, 1])
        f_max_price = fc4.slider("Batas Maksimal Harga Tiket (Rp):", 0, int(df_all_dest["price"].max()), int(df_all_dest["price"].max()), step=5000)
        f_min_rate = fc5.slider("Rating Minimal:", 1.0, 5.0, 1.0, 0.1)
        f_sort = fc6.selectbox("Urutkan:", ["Rating Tertinggi", "Harga Termurah", "Harga Termahal", "Nama (A-Z)"])

    filtered = df_all_dest.copy()
    if f_keyword.strip():
        filtered = filtered[filtered["name"].str.contains(f_keyword.strip(), case=False, na=False)]
    if f_cat != "Semua Kategori":
        filtered = filtered[filtered["category"] == f_cat]
    if f_prov != "Semua Provinsi":
        filtered = filtered[filtered["province"] == f_prov]

    filtered = filtered[(filtered["price"] <= f_max_price) & (filtered["google_rating"] >= f_min_rate)]

    if f_sort == "Rating Tertinggi":
        filtered = filtered.sort_values(by="google_rating", ascending=False)
    elif f_sort == "Harga Termurah":
        filtered = filtered.sort_values(by="price", ascending=True)
    elif f_sort == "Harga Termahal":
        filtered = filtered.sort_values(by="price", ascending=False)
    elif f_sort == "Nama (A-Z)":
        filtered = filtered.sort_values(by="name", ascending=True)

    st.markdown(f"Menemukan **{len(filtered)}** destinasi wisata.")

    # Grid Display
    grid_cols = st.columns(3)
    for idx, (_, d_item) in enumerate(filtered.iterrows()):
        with grid_cols[idx % 3]:
            price_str = f"Rp {float(d_item['price']):,.0f}".replace(",", ".")
            st.markdown(
                f"""
                <div class="nw-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span class="nw-badge nw-badge-blue">{d_item['category']}</span>
                        <span style="color: #F59E0B; font-weight: 700; font-size: 0.9rem;">⭐ {d_item['google_rating']:.1f}</span>
                    </div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: #F8FAFC; margin: 4px 0 2px 0;">{d_item['name']}</h3>
                    <div style="font-size: 0.82rem; color: #94A3B8; margin-bottom: 10px;">📍 {d_item['province']}</div>
                    <div style="font-size: 1rem; font-weight: 800; color: #38BDF8; margin-bottom: 12px;">{price_str}</div>
                </div>
                """,
                unsafe_allow_html=True,
            )
            b1, b2 = st.columns(2)
            if b1.button("Detail", key=f"d_dtl_{d_item['destination_id']}", use_container_width=True):
                show_destination_detail_dialog(int(d_item["destination_id"]))
            if b2.button("Beri Rating", key=f"d_rate_{d_item['destination_id']}", use_container_width=True):
                show_rating_dialog(int(d_item["destination_id"]))


# ─────────────────────────────────────────────────────────────
# 8. PAGE: Rekomendasi (Detailed UBCF + K-Means Pipeline)
# ─────────────────────────────────────────────────────────────
elif nav_selection == "⭐ Rekomendasi":
    render_header("⭐ Rekomendasi Personal K-Means + UBCF", "Pipeline rekomendasi dua tahap dengan transparansi perhitungan algoritma.")

    with st.expander("⚙️ Parameter Algoritma Rekomendasi", expanded=True):
        rc1, rc2, rc3, rc4 = st.columns(4)
        use_cluster_peer = rc1.checkbox("Batasi Peer dalam Klaster Pengguna", value=True)
        k_neighbors = rc2.slider("Tetangga Terdekat (k-NN):", 3, 25, 10)
        top_n = rc3.slider("Jumlah Rekomendasi (Top-N):", 3, 15, 6)
        cat_rec_filter = rc4.selectbox(
            "Filter Kategori Rekomendasi:",
            ["Semua Kategori"] + sorted(st.session_state["destinations_df"]["category"].dropna().unique().tolist()),
        )

    # Run recommendation
    recs_detail = recommend_for_user(
        user_id=active_uid,
        ratings_df=st.session_state["ratings_df"],
        destinations_df=st.session_state["destinations_df"],
        users_df=st.session_state["users_df"],
        user_clusters=user_clusters,
        k_neighbors=k_neighbors,
        limit=top_n,
        within_cluster=use_cluster_peer,
        category_filter=cat_rec_filter if cat_rec_filter != "Semua Kategori" else None,
        min_ratings_threshold=3,
    )

    recs_list = recs_detail.get("recommendations", pd.DataFrame())
    neighbors_used = recs_detail.get("neighbors_used", [])

    if recs_detail.get("is_cold_start"):
        st.warning("⚠️ **Mode Cold-Start Aktif:** Rekomendasi disajikan dari destinasi terpopuler karena riwayat rating < 3.")
    else:
        st.success(f"✅ **Rekomendasi Berhasil Dihitung:** {recs_detail.get('explanation')}")

    if not recs_list.empty:
        # Peta Rekomendasi
        st.markdown('<div class="nw-section-title">🗺️ Sebaran Peta Destinasi Rekomendasi</div>', unsafe_allow_html=True)
        map_rec = create_destinations_map(recs_list, zoom_start=5, is_recommendation=True)
        st_folium(map_rec, width=None, height=360, returned_objects=[])

        st.markdown("<div style='height: 1rem;'></div>", unsafe_allow_html=True)
        st.markdown('<div class="nw-section-title">🏆 Peringkat Rekomendasi Teratas</div>', unsafe_allow_html=True)

        r_cols = st.columns(3)
        for idx, (_, r_dest) in enumerate(recs_list.iterrows()):
            with r_cols[idx % 3]:
                rank = r_dest.get("recommendation_rank", idx + 1)
                pred_val = float(r_dest.get("predicted_rating", 4.5))
                p_rp = f"Rp {float(r_dest['price']):,.0f}".replace(",", ".")
                pct = min(99, int((pred_val / 5.0) * 100))

                st.markdown(
                    f"""
                    <div class="nw-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span class="nw-badge nw-badge-blue">Peringkat #{rank}</span>
                            <span class="nw-match-pill">✨ {pct}% Cocok</span>
                        </div>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #F8FAFC; margin: 4px 0 2px 0;">{r_dest['name']}</h3>
                        <div style="font-size: 0.82rem; color: #94A3B8; margin-bottom: 10px;">
                            📍 {r_dest['province']} • <span style="color: #34D399; font-weight: 600;">{r_dest['category']}</span>
                        </div>
                        <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; padding: 8px 12px; margin-bottom: 10px;">
                            <div style="font-size: 0.7rem; color: #94A3B8; font-weight: 600;">PREDIKSI SKOR RATING</div>
                            <div style="font-size: 1.3rem; font-weight: 800; color: #F59E0B;">⭐ {pred_val:.2f} <span style="font-size: 0.75rem; color: #64748B;">/ 5.0</span></div>
                        </div>
                        <div style="font-size: 0.75rem; color: #CBD5E1; margin-bottom: 10px;">
                            {r_dest.get('reason', 'Destinasi rekomendasi personal.')}
                        </div>
                    </div>
                    """,
                    unsafe_allow_html=True,
                )
                rb1, rb2 = st.columns(2)
                if rb1.button("Detail", key=f"r_p_dtl_{r_dest['destination_id']}", use_container_width=True):
                    show_destination_detail_dialog(int(r_dest["destination_id"]))
                if rb2.button("Beri Rating", key=f"r_p_rate_{r_dest['destination_id']}", use_container_width=True):
                    show_rating_dialog(int(r_dest["destination_id"]))

    # Transparency Deep Dive
    st.markdown("<div style='height: 1.5rem;'></div>", unsafe_allow_html=True)
    with st.expander("🧮 Transparansi Perhitungan Algoritma", expanded=False):
        tr_tab1, tr_tab2, tr_tab3 = st.tabs(["1. Profil Klaster", "2. Tetangga Terdekat (k-NN) & Cosine Similarity", "3. Formula Matematis"])
        with tr_tab1:
            st.markdown(f"**Klaster Pengguna Aktif:** `Klaster #{active_cluster_id} - {active_cluster_name}`")
            u_feats = user_features_df[user_features_df["user_id"] == active_uid]
            if not u_feats.empty:
                st.dataframe(u_feats, use_container_width=True)
        with tr_tab2:
            if neighbors_used:
                n_df = pd.DataFrame(neighbors_used)
                n_df.columns = ["User ID", "Nama Wisatawan", "Cosine Similarity", "Klaster", "Rating Bersama"]
                st.dataframe(n_df, use_container_width=True)
            else:
                st.info("Tidak ada tetangga terdekat (Mode Cold-Start).")
        with tr_tab3:
            st.markdown(
                """
                **1. Cosine Similarity:**
                $$sim(u, v) = \\frac{\\sum_{i \\in I_{uv}} r_{u,i} \\cdot r_{v,i}}{\\sqrt{\\sum_{i \\in I_{uv}} r_{u,i}^2} \\cdot \\sqrt{\\sum_{i \\in I_{uv}} r_{v,i}^2}}$$

                **2. Prediksi Rating Destinasi Belum Dikunjungi:**
                $$\\hat{r}(u, i) = \\frac{\\sum_{v \\in \\text{neighbors}} sim(u, v) \\cdot r(v, i)}{\\sum_{v \\in \\text{neighbors}} |sim(u, v)|}$$
                """
            )


# ─────────────────────────────────────────────────────────────
# 9. PAGE: Rating Saya
# ─────────────────────────────────────────────────────────────
elif nav_selection == "📝 Rating Saya":
    render_header("📝 Kelola Riwayat & Preferensi Rating Saya", "Daftar seluruh destinasi wisata yang telah Anda beri penilaian.")

    col_btn1, col_btn2 = st.columns([3, 1])
    col_btn1.markdown(f"Total **{active_ratings_count}** destinasi telah dinilai oleh **{active_user_name}**.")

    # Add quick rating
    with st.expander("➕ Tambahkan Rating untuk Destinasi Baru"):
        all_dests_map = dict(zip(st.session_state["destinations_df"]["destination_id"], st.session_state["destinations_df"]["name"]))
        unrated_opts = {d_id: name for d_id, name in all_dests_map.items() if d_id not in active_user_ratings["destination_id"].values}

        if unrated_opts:
            target_add_id = st.selectbox("Pilih Destinasi:", options=list(unrated_opts.keys()), format_func=lambda x: unrated_opts[x])
            val_add = st.slider("Nilai Rating:", 1, 5, 5, 1)
            if st.button("Simpan Ulasan Baru", type="primary"):
                new_row = pd.DataFrame([{"user_id": int(active_uid), "destination_id": int(target_add_id), "rating": int(val_add)}])
                st.session_state["ratings_df"] = pd.concat([st.session_state["ratings_df"], new_row], ignore_index=True)
                st.success("Rating baru berhasil ditambahkan!")
                st.rerun()
        else:
            st.info("Hebat! Anda telah menilai seluruh destinasi di database.")

    if not active_user_ratings.empty:
        my_history = active_user_ratings.merge(st.session_state["destinations_df"], on="destination_id", how="left")

        # Visual preference breakdown
        h_col1, h_col2 = st.columns(2)
        with h_col1:
            cat_counts = my_history["category"].value_counts().reset_index()
            cat_counts.columns = ["Kategori", "Jumlah"]
            fig_p = px.pie(cat_counts, names="Kategori", values="Jumlah", title="Distribusi Kategori yang Pernah Anda Kunjungi", template="plotly_dark")
            fig_p.update_layout(height=280)
            st.plotly_chart(fig_p, use_container_width=True)

        with h_col2:
            fig_h = px.histogram(my_history, x="rating", nbins=5, title="Distribusi Nilai Rating yang Anda Berikan", color_discrete_sequence=["#38BDF8"], template="plotly_dark")
            fig_h.update_layout(height=280)
            st.plotly_chart(fig_h, use_container_width=True)

        st.markdown("### Daftar Destinasi yang Pernah Dinilai")
        st.dataframe(my_history[["destination_id", "name", "category", "province", "price", "rating"]], use_container_width=True, height=320)


# ─────────────────────────────────────────────────────────────
# 10. PAGE: Peta Interaktif
# ─────────────────────────────────────────────────────────────
elif nav_selection == "🗺️ Peta":
    render_header("🗺️ Peta Interaktif Pariwisata Indonesia", "Eksplorasi persebaran 195 destinasi wisata di seluruh provinsi nusantara.")

    map_filter_col1, map_filter_col2 = st.columns(2)
    with map_filter_col1:
        map_cat = st.selectbox("Saring Berdasarkan Kategori:", ["Semua Kategori"] + sorted(st.session_state["destinations_df"]["category"].dropna().unique().tolist()))
    with map_filter_col2:
        map_prov = st.selectbox("Saring Berdasarkan Provinsi:", ["Semua Provinsi"] + sorted(st.session_state["destinations_df"]["province"].dropna().unique().tolist()))

    map_df = st.session_state["destinations_df"].copy()
    if map_cat != "Semua Kategori":
        map_df = map_df[map_df["category"] == map_cat]
    if map_prov != "Semua Provinsi":
        map_df = map_df[map_df["province"] == map_prov]

    st.info(f"Menampilkan **{len(map_df)}** titik lokasi destinasi pada peta Leaflet / OpenStreetMap.")
    full_map = create_destinations_map(map_df, zoom_start=5, is_recommendation=False)
    st_folium(full_map, width=None, height=520, returned_objects=[])


# ─────────────────────────────────────────────────────────────
# 11. PAGE: Evaluasi Model
# ─────────────────────────────────────────────────────────────
elif nav_selection == "📈 Evaluasi Model":
    render_header("📈 Validasi & Evaluasi Model Ilmiah", "Hasil pengujian empiris segmentasi K-Means dan akurasi Collaborative Filtering.")

    # 1. K-Means
    st.markdown('<div class="nw-section-title">1. Validasi Klaster Segmentasi Pengguna (K-Means)</div>', unsafe_allow_html=True)
    m_km = clustering_result.get("metrics", {})

    km1, km2, km3, km4 = st.columns(4)
    km1.metric("Silhouette Score", f"{m_km.get('silhouette', 0.0):.4f}")
    km2.metric("Davies-Bouldin Index", f"{m_km.get('davies_bouldin', 0.0):.4f}")
    km3.metric("Calinski-Harabasz", f"{m_km.get('calinski_harabasz', 0.0):.1f}")
    km4.metric("Inertia", f"{m_km.get('inertia', 0.0):,.0f}")

    pca_df = clustering_result.get("pca_df", pd.DataFrame())
    if not pca_df.empty:
        fig_pca = px.scatter(
            pca_df,
            x="pca_1",
            y="pca_2",
            color="cluster",
            title="Proyeksi 2D PCA Klaster Pengguna (Rating Behavior Vectors)",
            template="plotly_dark",
        )
        fig_pca.update_layout(height=340)
        st.plotly_chart(fig_pca, use_container_width=True)

    st.markdown("<div style='height: 1.5rem;'></div>", unsafe_allow_html=True)

    # 2. UBCF
    st.markdown('<div class="nw-section-title">2. Evaluasi Akurasi Prediksi & Rekomendasi (UBCF)</div>', unsafe_allow_html=True)
    ev_c1, ev_c2 = st.columns([1, 2.5])
    with ev_c1:
        eval_split = st.slider("Rasio Data Uji (Test Ratio):", 0.1, 0.3, 0.2, 0.05)
        eval_knn = st.slider("Tetangga Terdekat (k-NN):", 5, 20, 10)
        run_ev_btn = st.button("🚀 Uji Evaluasi Model", use_container_width=True, type="primary")

    with ev_c2:
        if run_ev_btn:
            with st.spinner("Menghitung MAE, RMSE, Precision@5, dan Recall@5..."):
                ev_res = evaluate_system(
                    ratings_df=st.session_state["ratings_df"],
                    k_neighbors=eval_knn,
                    test_ratio=eval_split,
                    seed=42,
                    k_threshold=5,
                    rel_threshold=4.0,
                )
                m_c1, m_c2, m_c3, m_c4 = st.columns(4)
                m_c1.metric("MAE", f"{ev_res['mae']:.4f}")
                m_c2.metric("RMSE", f"{ev_res['rmse']:.4f}")
                m_c3.metric("Precision@5", f"{ev_res['precision_k']:.2f}%")
                m_c4.metric("Recall@5", f"{ev_res['recall_k']:.2f}%")

                if ev_res["abs_errors"]:
                    fig_err = px.histogram(
                        x=ev_res["abs_errors"],
                        nbins=25,
                        labels={"x": "Absolute Error (|r - r_pred|)", "y": "Frekuensi"},
                        title="Distribusi Galat Absolut Prediksi Rating",
                        color_discrete_sequence=["#38BDF8"],
                        template="plotly_dark",
                    )
                    fig_err.update_layout(height=280)
                    st.plotly_chart(fig_err, use_container_width=True)

                st.info(
                    f"**Interpretasi Akademis:** MAE {ev_res['mae']:.4f} membuktikan simpangan prediksi rating hanya ~{ev_res['mae']:.2f} poin pada skala 1-5. "
                    f"Precision@5 {ev_res['precision_k']:.2f}% menunjukkan rekomendasi Top-5 memiliki relevansi tinggi."
                )
        else:
            st.info("Klik tombol **Uji Evaluasi Model** di samping untuk menguji metrik performa sistem.")


# ─────────────────────────────────────────────────────────────
# 12. PAGE: Pengaturan / Profil
# ─────────────────────────────────────────────────────────────
elif nav_selection == "👤 Pengaturan / Profil":
    render_header("👤 Pengaturan Profil & Simulasi Pengguna", "Ganti identitas pengguna untuk simulasi atau uji skenario cold-start.")

    ps1, ps2 = st.columns(2)
    with ps1:
        st.subheader("🔄 Ganti Pengguna Aktif")
        all_users = st.session_state["users_df"].copy()
        user_opts = dict(zip(all_users["user_id"], all_users["name"] + " (ID: " + all_users["user_id"].astype(str) + ")"))

        pick_u = st.selectbox("Pilih Pengguna:", options=list(user_opts.keys()), format_func=lambda x: user_opts[x], index=list(user_opts.keys()).index(active_uid) if active_uid in user_opts else 0)
        if st.button("Terapkan Pengguna Ini", type="primary", use_container_width=True):
            st.session_state["active_user_id"] = int(pick_u)
            st.success(f"Beralih ke: {user_opts[pick_u]}")
            st.rerun()

    with ps2:
        st.subheader("🆕 Simulasi Pengguna Baru (Cold-Start)")
        st.caption("Buat akun wisatawan kosong tanpa riwayat rating untuk menguji fallback cold-start.")
        new_u_name = st.text_input("Nama Pengguna Baru:", value="Wisatawan Penguji Cold-Start")
        if st.button("➕ Buat Akun Baru", use_container_width=True):
            new_id = int(st.session_state["users_df"]["user_id"].max()) + 1
            new_u_entry = pd.DataFrame([{"user_id": new_id, "name": new_u_name, "email": f"tester{new_id}@nusawisata.id", "cluster_id": 1}])
            st.session_state["users_df"] = pd.concat([st.session_state["users_df"], new_u_entry], ignore_index=True)
            st.session_state["active_user_id"] = new_id
            st.success(f"Pengguna baru '{new_u_name}' berhasil dibuat!")
            st.rerun()

    st.markdown("<div style='height: 1.5rem;'></div>", unsafe_allow_html=True)
    st.subheader("⚠️ Reset Data Simulasi")
    st.caption("Mengembalikan data rating dan pengguna kembali ke dataset bawaan CSV.")
    if st.button("🔄 Reset ke Dataset Awal"):
        st.session_state["ratings_df"] = raw_ratings.copy()
        st.session_state["users_df"] = raw_users.copy()
        st.session_state["active_user_id"] = 2
        st.success("Data berhasil direset!")
        st.rerun()


# ─────────────────────────────────────────────────────────────
# 13. PAGE: Admin Dataset
# ─────────────────────────────────────────────────────────────
elif nav_selection == "⚙️ Admin Dataset":
    render_header("⚙️ Admin Dataset & Analisis Matriks", "Dashboard analitik dataset produksi dan inspeksi matriks rating.")

    tot_u = len(st.session_state["users_df"])
    tot_d = len(st.session_state["destinations_df"])
    tot_r = len(st.session_state["ratings_df"])
    mat_size = tot_u * tot_d
    mat_sparsity = (1 - (tot_r / mat_size)) * 100 if mat_size > 0 else 0

    ad1, ad2, ad3, ad4 = st.columns(4)
    ad1.metric("Total Pengguna", f"{tot_u:,}")
    ad2.metric("Total Destinasi", f"{tot_d:,}")
    ad3.metric("Total Rating", f"{tot_r:,}")
    ad4.metric("Sparsitas Matriks", f"{mat_sparsity:.2f}%")

    st.markdown("<div style='height: 1rem;'></div>", unsafe_allow_html=True)
    adm_tab1, adm_tab2, adm_tab3 = st.tabs(["Destinasi Wisata", "Data Rating", "Pengguna"])
    with adm_tab1:
        st.dataframe(st.session_state["destinations_df"], use_container_width=True, height=350)
    with adm_tab2:
        st.dataframe(st.session_state["ratings_df"], use_container_width=True, height=350)
    with adm_tab3:
        st.dataframe(st.session_state["users_df"], use_container_width=True, height=350)
