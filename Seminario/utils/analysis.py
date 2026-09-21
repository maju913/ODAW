def dashboard_stats(crops):
    if crops.empty:
        return {"total": 0, "seasons": 0, "highest_price": 0, "best_profit": 0, "fastest": 0}
    return {
        "total": int(crops["crop_name"].nunique()),
        "seasons": int(crops["season"].nunique()),
        "highest_price": int(crops["sell_price"].max()),
        "best_profit": int(crops["profit"].max()),
        "fastest": int(crops["days_to_grow"].min()),
    }


def season_counts(crops):
    return crops.groupby("season")["crop_name"].count().sort_values(ascending=False)


def profit_by_crop(crops, limit=8):
    return crops.sort_values("profit", ascending=False).drop_duplicates("crop_name").head(limit).set_index("crop_name")["profit"]


def secondary_stats(fish, villagers):
    return {
        "fish": int(fish["name"].nunique()) if not fish.empty else 0,
        "legendary": int((fish["base_price"] >= 900).sum()) if not fish.empty else 0,
        "villagers": int(villagers["name"].nunique()) if not villagers.empty else 0,
        "candidates": int(villagers["marriage_candidate"].astype(str).str.casefold().eq("yes").sum()) if not villagers.empty else 0,
    }
