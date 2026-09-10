"""
User-Based Collaborative Filtering (UBCF) Service for NusaWisata Streamlit.
Replicates the exact cosine similarity, neighbor selection, and rating prediction logic
from Laravel's CollaborativeFilteringService.php.
"""

import math
from typing import Dict, List, Optional, Tuple
import numpy as np
import pandas as pd


def calculate_cosine_similarity(user_ratings: Dict[int, float], peer_ratings: Dict[int, float]) -> Tuple[float, int]:
    """
    Computes Cosine Similarity between two users based on co-rated destinations (I_uv).
    Returns (similarity_score, co_rated_count).
    """
    common_destinations = set(user_ratings.keys()) & set(peer_ratings.keys())
    if not common_destinations:
        return 0.0, 0

    num = 0.0
    sum_sq_u = 0.0
    sum_sq_v = 0.0

    for d_id in common_destinations:
        ru = float(user_ratings[d_id])
        rv = float(peer_ratings[d_id])
        num += ru * rv
        sum_sq_u += ru * ru
        sum_sq_v += rv * rv

    denom = math.sqrt(sum_sq_u) * math.sqrt(sum_sq_v)
    if denom <= 1e-9:
        return 0.0, len(common_destinations)

    similarity = num / denom
    return float(similarity), len(common_destinations)


def recommend_for_user(
    user_id: int,
    ratings_df: pd.DataFrame,
    destinations_df: pd.DataFrame,
    users_df: pd.DataFrame,
    user_clusters: Dict[int, int],
    k_neighbors: int = 10,
    limit: int = 6,
    within_cluster: bool = True,
    category_filter: Optional[str] = None,
    province_filter: Optional[str] = None,
    min_ratings_threshold: int = 3,
) -> dict:
    """
    Generates personal recommendations for the target user using UBCF + K-Means cluster constraint.
    Guarantees that destinations already rated by the target user are excluded.
    Falls back gracefully on cold-start users (< min_ratings_threshold).
    """
    # 1. Target user's rated destinations
    user_sub = ratings_df[ratings_df["user_id"] == user_id]
    user_ratings = dict(zip(user_sub["destination_id"].values, user_sub["rating"].values))
    rated_dest_ids = set(user_ratings.keys())

    # Build filtered candidates (unrated only)
    candidate_df = destinations_df[~destinations_df["destination_id"].isin(rated_dest_ids)].copy()

    if category_filter and category_filter != "Semua Kategori":
        candidate_df = candidate_df[candidate_df["category"] == category_filter]

    if province_filter and province_filter != "Semua Provinsi":
        candidate_df = candidate_df[candidate_df["province"] == province_filter]

    if candidate_df.empty:
        return {
            "recommendations": pd.DataFrame(),
            "target_user_id": user_id,
            "cluster_id": user_clusters.get(user_id, 1),
            "neighbors_used": [],
            "predicted_ratings": {},
            "is_cold_start": False,
            "is_fallback": True,
            "algorithm": "Empty Candidate Filter",
            "explanation": "Tidak ditemukan destinasi yang belum Anda kunjungi sesuai filter yang dipilih.",
        }

    # 2. Cold-start check
    if len(user_ratings) < min_ratings_threshold:
        # Fallback: Popular or Highest rated unrated destinations
        fallback_df = candidate_df.sort_values(
            by=["google_rating", "price"], ascending=[False, True]
        ).head(limit).copy()

        fallback_df["predicted_rating"] = fallback_df["google_rating"]
        fallback_df["recommendation_rank"] = range(1, len(fallback_df) + 1)
        fallback_df["reason"] = "Rekomendasi Populer (Cold-Start Fallback)"

        return {
            "recommendations": fallback_df,
            "target_user_id": user_id,
            "cluster_id": user_clusters.get(user_id, 1),
            "neighbors_used": [],
            "predicted_ratings": dict(zip(fallback_df["destination_id"], fallback_df["google_rating"])),
            "is_cold_start": True,
            "is_fallback": True,
            "algorithm": "Cold-Start Global Popularity",
            "explanation": f"Pengguna memiliki {len(user_ratings)} rating (di bawah ambang batas {min_ratings_threshold}). Sistem menyajikan destinasi berating tertinggi secara fallback.",
        }

    # 3. Select peer candidate users
    target_cluster = user_clusters.get(user_id, 1)
    peer_user_ids = [u for u in ratings_df["user_id"].unique() if u != user_id]

    if within_cluster and target_cluster is not None:
        cluster_peers = [u for u in peer_user_ids if user_clusters.get(u) == target_cluster]
        if len(cluster_peers) >= 3:
            peer_user_ids = cluster_peers

    # Pre-aggregate peer ratings
    peer_ratings_map = {}
    grouped_peers = ratings_df[ratings_df["user_id"].isin(peer_user_ids)].groupby("user_id")
    for p_id, group in grouped_peers:
        peer_ratings_map[p_id] = dict(zip(group["destination_id"].values, group["rating"].values))

    # 4. Calculate Cosine Similarity with each peer
    similarities = {}
    co_rated_counts = {}

    for p_id, p_ratings in peer_ratings_map.items():
        sim, common_cnt = calculate_cosine_similarity(user_ratings, p_ratings)
        if sim > 0.0:
            similarities[p_id] = sim
            co_rated_counts[p_id] = common_cnt

    if not similarities:
        # Fallback if no similarity found
        fallback_df = candidate_df.sort_values(
            by=["google_rating", "price"], ascending=[False, True]
        ).head(limit).copy()
        fallback_df["predicted_rating"] = fallback_df["google_rating"]
        fallback_df["recommendation_rank"] = range(1, len(fallback_df) + 1)
        fallback_df["reason"] = "Destinasi Unggulan (Fallback Kosin Nol)"

        return {
            "recommendations": fallback_df,
            "target_user_id": user_id,
            "cluster_id": target_cluster,
            "neighbors_used": [],
            "predicted_ratings": {},
            "is_cold_start": False,
            "is_fallback": True,
            "algorithm": "UBCF Fallback (No Overlap)",
            "explanation": "Belum ditemukan kesamaan pola penilaian dengan wisatawan lain. Menyajikan destinasi teratas.",
        }

    # 5. Top-N Nearest Neighbors
    sorted_peers = sorted(similarities.items(), key=lambda x: x[1], reverse=True)
    top_neighbors = sorted_peers[:k_neighbors]

    user_names_map = dict(zip(users_df["user_id"], users_df["name"])) if "name" in users_df.columns else {}

    neighbors_used = []
    for p_id, sim in top_neighbors:
        neighbors_used.append({
            "user_id": p_id,
            "name": user_names_map.get(p_id, f"Wisatawan #{p_id}"),
            "similarity": round(sim, 4),
            "cluster_id": user_clusters.get(p_id, 1),
            "common_ratings": co_rated_counts.get(p_id, 0),
        })

    # 6. Predict ratings for unvisited candidate destinations
    # Formula: predicted_rating(u, i) = sum(sim(u, v) * r(v, i)) / sum(abs(sim(u, v)))
    candidate_dest_ids = set(candidate_df["destination_id"].values)
    item_numerator = {}
    item_denominator = {}

    for p_id, sim in top_neighbors:
        p_ratings = peer_ratings_map[p_id]
        for d_id, r_val in p_ratings.items():
            if d_id in candidate_dest_ids:
                item_numerator[d_id] = item_numerator.get(d_id, 0.0) + (sim * float(r_val))
                item_denominator[d_id] = item_denominator.get(d_id, 0.0) + abs(sim)

    predicted_ratings = {}
    for d_id, num in item_numerator.items():
        denom = item_denominator.get(d_id, 0.0)
        if denom > 1e-9:
            pred = num / denom
            predicted_ratings[d_id] = round(min(5.0, max(1.0, pred)), 2)

    if not predicted_ratings:
        fallback_df = candidate_df.sort_values(
            by=["google_rating", "price"], ascending=[False, True]
        ).head(limit).copy()
        fallback_df["predicted_rating"] = fallback_df["google_rating"]
        fallback_df["recommendation_rank"] = range(1, len(fallback_df) + 1)
        fallback_df["reason"] = "Destinasi Unggulan (Fallback Neighbor Unrated)"

        return {
            "recommendations": fallback_df,
            "target_user_id": user_id,
            "cluster_id": target_cluster,
            "neighbors_used": neighbors_used,
            "predicted_ratings": {},
            "is_cold_start": False,
            "is_fallback": True,
            "algorithm": "UBCF Fallback",
            "explanation": "Tetangga terdekat belum menilai destinasi baru yang sesuai kriteria filter Anda.",
        }

    # 7. Sort destinations by predicted rating descending
    sorted_dest_ids = sorted(predicted_ratings.keys(), key=lambda d: predicted_ratings[d], reverse=True)[:limit]

    top_recs_df = candidate_df[candidate_df["destination_id"].isin(sorted_dest_ids)].copy()
    top_recs_df["predicted_rating"] = top_recs_df["destination_id"].map(predicted_ratings)
    top_recs_df = top_recs_df.sort_values(by="predicted_rating", ascending=False).reset_index(drop=True)
    top_recs_df["recommendation_rank"] = range(1, len(top_recs_df) + 1)
    top_recs_df["reason"] = top_recs_df["predicted_rating"].apply(
        lambda p: f"Prediksi Rating {p:.2f} (skor estimasi preferensi wisatawan serupa di Klaster {target_cluster})"
    )

    cluster_info = f"Klaster {target_cluster}" if within_cluster else "Seluruh Pengguna (Global)"
    explanation = (
        f"Rekomendasi dihitung menggunakan {len(top_neighbors)} tetangga terdekat dalam {cluster_info} "
        f"dengan metode Cosine Similarity dan pembobotan rating."
    )

    return {
        "recommendations": top_recs_df,
        "target_user_id": user_id,
        "cluster_id": target_cluster,
        "neighbors_used": neighbors_used,
        "predicted_ratings": predicted_ratings,
        "is_cold_start": False,
        "is_fallback": False,
        "algorithm": f"K-Means + UBCF ({cluster_info})",
        "explanation": explanation,
    }


def evaluate_system(
    ratings_df: pd.DataFrame,
    k_neighbors: int = 10,
    test_ratio: float = 0.2,
    seed: int = 42,
    k_threshold: int = 5,
    rel_threshold: float = 4.0,
) -> dict:
    """
    Evaluates the UBCF recommendation engine on a train/test split.
    Calculates MAE, RMSE, Precision@5, Recall@5, and error distributions.
    """
    rng = np.random.RandomState(seed)
    shuffled_indices = rng.permutation(len(ratings_df))
    test_size = int(len(ratings_df) * test_ratio)

    test_indices = set(shuffled_indices[:test_size])
    train_ratings_df = ratings_df.iloc[~ratings_df.index.isin(test_indices)].copy()
    test_ratings_df = ratings_df.iloc[ratings_df.index.isin(test_indices)].copy()

    # Pre-build train ratings map per user
    train_user_ratings = {}
    for u_id, grp in train_ratings_df.groupby("user_id"):
        train_user_ratings[u_id] = dict(zip(grp["destination_id"].values, grp["rating"].values))

    abs_errors = []
    sq_errors = []
    precisions = []
    recalls = []

    # Evaluate per user who has test samples
    test_grouped = test_ratings_df.groupby("user_id")

    for u_id, test_group in test_grouped:
        if u_id not in train_user_ratings or len(train_user_ratings[u_id]) < 2:
            continue

        target_u_ratings = train_user_ratings[u_id]

        # Similarities with peers
        sims = {}
        for p_id, p_ratings in train_user_ratings.items():
            if p_id == u_id:
                continue
            s, _ = calculate_cosine_similarity(target_u_ratings, p_ratings)
            if s > 0.0:
                sims[p_id] = s

        if not sims:
            continue

        sorted_peers = sorted(sims.items(), key=lambda x: x[1], reverse=True)[:k_neighbors]

        user_test_predictions = {}
        user_test_actuals = dict(zip(test_group["destination_id"].values, test_group["rating"].values))

        for d_id, actual_r in user_test_actuals.items():
            num = 0.0
            denom = 0.0
            for p_id, sim in sorted_peers:
                p_ratings = train_user_ratings[p_id]
                if d_id in p_ratings:
                    num += sim * float(p_ratings[d_id])
                    denom += abs(sim)

            if denom > 1e-9:
                pred_r = min(5.0, max(1.0, num / denom))
                user_test_predictions[d_id] = pred_r
                err = abs(actual_r - pred_r)
                abs_errors.append(err)
                sq_errors.append(err ** 2)

        # Precision@K and Recall@K for this user
        if user_test_predictions:
            sorted_pred_dests = sorted(user_test_predictions.keys(), key=lambda d: user_test_predictions[d], reverse=True)
            top_k_preds = set(sorted_pred_dests[:k_threshold])

            relevant_dests = {d for d, r in user_test_actuals.items() if r >= rel_threshold}

            if relevant_dests:
                hits = len(top_k_preds & relevant_dests)
                prec = hits / min(k_threshold, len(user_test_predictions))
                rec = hits / len(relevant_dests)
                precisions.append(prec)
                recalls.append(rec)

    mae = float(np.mean(abs_errors)) if abs_errors else 0.0
    rmse = float(np.sqrt(np.mean(sq_errors))) if sq_errors else 0.0
    precision_k = float(np.mean(precisions) * 100) if precisions else 0.0
    recall_k = float(np.mean(recalls) * 100) if recalls else 0.0

    return {
        "mae": round(mae, 4),
        "rmse": round(rmse, 4),
        "precision_k": round(precision_k, 2),
        "recall_k": round(recall_k, 2),
        "total_test_samples": len(abs_errors),
        "eval_users_count": len(test_grouped),
        "abs_errors": abs_errors,
        "k_threshold": k_threshold,
        "rel_threshold": rel_threshold,
    }
