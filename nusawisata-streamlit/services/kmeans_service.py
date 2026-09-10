"""
K-Means Clustering Service for NusaWisata Streamlit.
Replicates the 12-feature user segmentation methodology used in Laravel NusaWisata.
"""

import numpy as np
import pandas as pd
from sklearn.cluster import KMeans
from sklearn.decomposition import PCA
from sklearn.metrics import silhouette_score, davies_bouldin_score, calinski_harabasz_score
from sklearn.preprocessing import StandardScaler


FEATURE_KEYS = [
    "total_ratings",
    "average_rating",
    "rating_std",
    "min_rating",
    "max_rating",
    "rating_5_ratio",
    "rating_4_ratio",
    "rating_3_ratio",
    "rating_low_ratio",
    "unique_categories",
    "unique_provinces",
    "avg_price",
]


def extract_user_features(ratings_df: pd.DataFrame, destinations_df: pd.DataFrame) -> pd.DataFrame:
    """
    Extracts the 12 behavioral rating features for each user matching Laravel KMeansService.
    """
    if ratings_df.empty:
        return pd.DataFrame(columns=["user_id"] + FEATURE_KEYS)

    # Merge ratings with destination metadata for category, province, price
    merged = ratings_df.merge(
        destinations_df[["destination_id", "category", "province", "price"]],
        on="destination_id",
        how="inner",
    )

    records = []
    grouped = merged.groupby("user_id")

    for user_id, group in grouped:
        scores = group["rating"].values
        total = len(scores)
        if total == 0:
            continue

        avg_r = float(np.mean(scores))
        std_r = float(np.std(scores, ddof=1)) if total > 1 else 0.0
        min_r = float(np.min(scores))
        max_r = float(np.max(scores))

        cnt5 = int(np.sum(scores == 5))
        cnt4 = int(np.sum(scores == 4))
        cnt3 = int(np.sum(scores == 3))
        cnt_low = int(np.sum(scores <= 2))

        unique_cats = group["category"].nunique()
        unique_provs = group["province"].nunique()
        avg_p = float(group["price"].mean()) if "price" in group.columns else 0.0

        records.append({
            "user_id": user_id,
            "total_ratings": float(total),
            "average_rating": round(avg_r, 3),
            "rating_std": round(std_r, 3),
            "min_rating": min_r,
            "max_rating": max_r,
            "rating_5_ratio": round(cnt5 / total, 3),
            "rating_4_ratio": round(cnt4 / total, 3),
            "rating_3_ratio": round(cnt3 / total, 3),
            "rating_low_ratio": round(cnt_low / total, 3),
            "unique_categories": float(unique_cats),
            "unique_provinces": float(unique_provs),
            "avg_price": round(avg_p, 2),
        })

    return pd.DataFrame(records)


def run_user_clustering(
    features_df: pd.DataFrame,
    k: int = 3,
    max_iter: int = 100,
    random_state: int = 42,
    n_init: int = 10,
) -> dict:
    """
    Runs K-Means clustering on user behavioral features using StandardScaler.
    Returns clusters, metrics (Silhouette, DBI, CHI, Inertia), scaled centroids, and 2D PCA.
    """
    if len(features_df) < k or features_df.empty:
        return {
            "k": k,
            "converged": False,
            "user_clusters": {},
            "metrics": {
                "silhouette": 0.0,
                "davies_bouldin": 0.0,
                "calinski_harabasz": 0.0,
                "inertia": 0.0,
            },
            "cluster_distribution": {},
            "centroids": pd.DataFrame(),
            "pca_df": pd.DataFrame(),
            "scaler": None,
            "model": None,
        }

    X_raw = features_df[FEATURE_KEYS].values
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X_raw)

    kmeans = KMeans(
        n_clusters=k,
        init="k-means++",
        max_iter=max_iter,
        random_state=random_state,
        n_init=n_init,
    )
    labels = kmeans.fit_predict(X_scaled)
    cluster_labels = labels + 1  # 1-indexed to match Laravel (Cluster 1, 2, 3...)

    # Metrics
    if len(set(labels)) > 1:
        sil = float(silhouette_score(X_scaled, labels))
        dbi = float(davies_bouldin_score(X_scaled, labels))
        chi = float(calinski_harabasz_score(X_scaled, labels))
    else:
        sil, dbi, chi = 0.0, 0.0, 0.0

    inertia = float(kmeans.inertia_)

    # User cluster map
    user_clusters = dict(zip(features_df["user_id"].values, cluster_labels))

    # Cluster distribution
    cluster_counts = pd.Series(cluster_labels).value_counts().sort_index().to_dict()

    # Original-scale Centroids
    unscaled_centroids = scaler.inverse_transform(kmeans.cluster_centers_)
    centroids_df = pd.DataFrame(
        unscaled_centroids,
        columns=FEATURE_KEYS,
        index=[f"Klaster {i+1}" for i in range(k)],
    )

    # 2D PCA for visualization
    pca = PCA(n_components=2, random_state=random_state)
    X_pca = pca.fit_transform(X_scaled)
    pca_df = pd.DataFrame({
        "user_id": features_df["user_id"].values,
        "pca_1": X_pca[:, 0],
        "pca_2": X_pca[:, 1],
        "cluster": [f"Klaster {c}" for c in cluster_labels],
        "cluster_id": cluster_labels,
    })

    return {
        "k": k,
        "converged": True,
        "user_clusters": user_clusters,
        "metrics": {
            "silhouette": round(sil, 4),
            "davies_bouldin": round(dbi, 4),
            "calinski_harabasz": round(chi, 4),
            "inertia": round(inertia, 2),
        },
        "cluster_distribution": cluster_counts,
        "centroids": centroids_df,
        "pca_df": pca_df,
        "scaler": scaler,
        "model": kmeans,
    }


def predict_user_cluster(
    user_id: int,
    ratings_df: pd.DataFrame,
    destinations_df: pd.DataFrame,
    cluster_result: dict,
) -> int:
    """
    Predicts or retrieves the cluster ID for a specific user.
    If the user has updated ratings, recomputes their feature vector and projects onto centroids.
    """
    if not cluster_result.get("converged") or cluster_result.get("model") is None:
        return 1

    model = cluster_result["model"]
    scaler = cluster_result["scaler"]

    user_ratings = ratings_df[ratings_df["user_id"] == user_id]
    if len(user_ratings) < 3:
        # Fallback to existing cluster if available, or default cluster 1
        return cluster_result.get("user_clusters", {}).get(user_id, 1)

    # Compute feature vector for single user
    user_feat = extract_user_features(user_ratings, destinations_df)
    if user_feat.empty:
        return cluster_result.get("user_clusters", {}).get(user_id, 1)

    x_raw = user_feat[FEATURE_KEYS].values
    x_scaled = scaler.transform(x_raw)
    predicted_cluster = model.predict(x_scaled)[0] + 1
    return int(predicted_cluster)
