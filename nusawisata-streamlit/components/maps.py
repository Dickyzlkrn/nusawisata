"""
Leaflet / OpenStreetMap Component for NusaWisata Streamlit using Folium.
Provides interactive maps for exploring destinations and visualizing recommendations.
"""

from typing import Optional
import folium
from folium.plugins import MarkerCluster
import pandas as pd
from streamlit_folium import st_folium


CATEGORY_COLORS = {
    "Alam": "green",
    "Bahari": "blue",
    "Budaya": "orange",
    "Religi": "purple",
    "Sejarah": "darkred",
    "Rekreasi": "cadetblue",
    "Kuliner": "red",
}


def create_destinations_map(
    destinations_df: pd.DataFrame,
    center_lat: Optional[float] = None,
    center_lon: Optional[float] = None,
    zoom_start: int = 5,
    is_recommendation: bool = False,
    max_markers: int = 150,
) -> folium.Map:
    """
    Creates an interactive Leaflet/OpenStreetMap with clustered or highlighted destination markers.
    """
    valid_df = destinations_df.dropna(subset=["latitude", "longitude"]).copy()

    if center_lat is None or center_lon is None:
        if not valid_df.empty:
            center_lat = float(valid_df["latitude"].mean())
            center_lon = float(valid_df["longitude"].mean())
        else:
            center_lat, center_lon = -2.5, 118.0

    m = folium.Map(
        location=[center_lat, center_lon],
        zoom_start=zoom_start,
        tiles="OpenStreetMap",
        control_scale=True,
    )

    if is_recommendation:
        # For recommendations, show distinct numbered rank pins
        for _, row in valid_df.head(max_markers).iterrows():
            lat = float(row["latitude"])
            lon = float(row["longitude"])
            name = row.get("name", "Destinasi")
            cat = row.get("category", "Wisata")
            prov = row.get("province", "-")
            price = f"Rp {float(row.get('price', 0)):,.0f}".replace(",", ".")
            pred_r = row.get("predicted_rating", row.get("google_rating", 0.0))
            rank = row.get("recommendation_rank", 1)

            popup_html = f"""
            <div style="font-family: sans-serif; font-size: 13px; min-width: 200px; color: #1E293B;">
                <div style="font-weight: bold; font-size: 14px; margin-bottom: 4px;">
                    🏆 #{rank} {name}
                </div>
                <div style="margin-bottom: 4px;">
                    <span style="background-color: #2563EB; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px;">
                        {cat}
                    </span>
                    <span style="color: #64748B; font-size: 12px; margin-left: 4px;">📍 {prov}</span>
                </div>
                <div style="margin-top: 6px; font-size: 12px;">
                    <b>⭐ Prediksi Rating:</b> <span style="color: #D97706; font-weight: bold;">{float(pred_r):.2f}</span> / 5.0<br>
                    <b>💵 Tiket:</b> {price}
                </div>
            </div>
            """

            folium.Marker(
                location=[lat, lon],
                popup=folium.Popup(popup_html, max_width=300),
                tooltip=f"#{rank} {name} (Prediksi: {float(pred_r):.2f} ⭐)",
                icon=folium.Icon(color="red", icon="star", prefix="fa"),
            ).add_to(m)

    else:
        # Standard exploration map with clustering
        marker_cluster = MarkerCluster().add_to(m)

        for _, row in valid_df.head(max_markers).iterrows():
            lat = float(row["latitude"])
            lon = float(row["longitude"])
            name = row.get("name", "Destinasi")
            cat = row.get("category", "Wisata")
            prov = row.get("province", "-")
            price = f"Rp {float(row.get('price', 0)):,.0f}".replace(",", ".")
            rating = row.get("google_rating", 0.0)
            color = CATEGORY_COLORS.get(cat, "blue")

            popup_html = f"""
            <div style="font-family: sans-serif; font-size: 13px; min-width: 180px; color: #1E293B;">
                <div style="font-weight: bold; font-size: 14px; margin-bottom: 4px;">{name}</div>
                <div style="margin-bottom: 4px;">
                    <span style="background-color: #0F766E; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px;">
                        {cat}
                    </span>
                    <span style="color: #64748B; font-size: 12px; margin-left: 4px;">📍 {prov}</span>
                </div>
                <div style="margin-top: 6px; font-size: 12px;">
                    <b>⭐ Rating:</b> {float(rating):.1f} / 5.0<br>
                    <b>💵 Tiket:</b> {price}
                </div>
            </div>
            """

            folium.Marker(
                location=[lat, lon],
                popup=folium.Popup(popup_html, max_width=280),
                tooltip=f"{name} ({cat})",
                icon=folium.Icon(color=color, icon="info-sign"),
            ).add_to(marker_cluster)

    return m


def create_single_destination_map(row: pd.Series, zoom_start: int = 12) -> folium.Map:
    """
    Creates a focused Leaflet map for a single destination detail view.
    """
    lat = float(row["latitude"])
    lon = float(row["longitude"])
    name = row.get("name", "Destinasi")
    cat = row.get("category", "Wisata")
    prov = row.get("province", "-")
    price = f"Rp {float(row.get('price', 0)):,.0f}".replace(",", ".")
    rating = row.get("google_rating", 0.0)

    m = folium.Map(location=[lat, lon], zoom_start=zoom_start, tiles="OpenStreetMap")

    popup_html = f"""
    <div style="font-family: sans-serif; font-size: 13px; color: #1E293B;">
        <b>{name}</b><br>
        <span style="color: #64748B;">{cat} • {prov}</span><br>
        <b>Tiket:</b> {price}<br>
        <b>Rating:</b> {rating} ⭐
    </div>
    """

    folium.Marker(
        location=[lat, lon],
        popup=folium.Popup(popup_html, max_width=250),
        tooltip=name,
        icon=folium.Icon(color="green", icon="map-marker"),
    ).add_to(m)

    return m
