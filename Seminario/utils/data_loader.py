from pathlib import Path

import pandas as pd


ROOT = Path(__file__).resolve().parent.parent
DATA_FILE = ROOT / "data" / "crops.csv"
DATA_DIR = ROOT / "data"
ARCHIVE_DIR = DATA_DIR / "archive"

REQUIRED_COLUMNS = [
    "crop_name", "description", "days_to_grow", "regrowth", "seed_price",
    "sell_price", "multiple_harvests", "edible", "season",
]


def load_crops():
    """Carrega um CSV único ou combina os arquivos sazonais automaticamente."""
    files = [DATA_FILE] if DATA_FILE.exists() else sorted(DATA_DIR.glob("*_crops_info.csv"))
    if not files:
        return pd.DataFrame(columns=REQUIRED_COLUMNS + ["profit"])

    crops = pd.concat((pd.read_csv(file) for file in files), ignore_index=True)
    missing = [column for column in REQUIRED_COLUMNS if column not in crops.columns]
    if missing:
        raise ValueError(f"Colunas ausentes em crops.csv: {', '.join(missing)}")

    numeric = ["days_to_grow", "regrowth", "seed_price", "sell_price"]
    for column in numeric:
        crops[column] = pd.to_numeric(crops[column], errors="coerce").fillna(0)
    crops["profit"] = crops["sell_price"] - crops["seed_price"]
    return crops


def crop_data_available():
    return DATA_FILE.exists() or any(DATA_DIR.glob("*_crops_info.csv"))


def _gold_to_number(value):
    """Converte valores como '1,500g' em números."""
    return pd.to_numeric(str(value).replace(",", "").replace("g", "").strip(), errors="coerce")


def load_fish():
    """Combina peixes comuns e lendários com seus preços-base."""
    detail_files = [ARCHIVE_DIR / "fish_detail.csv", ARCHIVE_DIR / "legendary_fish_detail.csv"]
    details = [pd.read_csv(file) for file in detail_files if file.exists()]
    if not details:
        return pd.DataFrame(columns=["name", "season", "difficulty", "base_xp", "base_price"])

    fish = pd.concat(details, ignore_index=True)
    fish = fish.rename(columns={"Name": "name", "Season": "season", "Base XP": "base_xp"})
    fish["difficulty"] = pd.to_numeric(
        fish["Difficulty & Behavior"].astype(str).str.extract(r"(\d+)")[0], errors="coerce"
    )
    fish["base_xp"] = pd.to_numeric(fish["base_xp"], errors="coerce")

    price_files = [
        ARCHIVE_DIR / "fish_price_breakdown.csv",
        ARCHIVE_DIR / "legendary_fish_price_breakdown.csv",
    ]
    prices = {}
    for file in price_files:
        if not file.exists():
            continue
        table = pd.read_csv(file)
        base_row = table[table["Name"] == "Base Price"]
        if not base_row.empty:
            prices.update({column: _gold_to_number(base_row.iloc[0][column]) for column in table.columns[1:]})
    fish["base_price"] = fish["name"].map(prices)
    return fish


def load_villagers():
    file = ARCHIVE_DIR / "villagers.csv"
    if not file.exists():
        return pd.DataFrame(columns=["name", "gender", "marriage_candidate", "heart_events"])
    return pd.read_csv(file).rename(columns={
        "Name": "name", "Gender": "gender", "Marriage Candidate": "marriage_candidate",
        "Heart Events": "heart_events",
    })


def filter_crops(crops, query="", season="", order="name"):
    result = crops.copy()
    if query:
        result = result[result["crop_name"].astype(str).str.contains(query, case=False, na=False)]
    if season:
        result = result[result["season"].astype(str).str.casefold() == season.casefold()]

    ordering = {
        "name": (["crop_name"], [True]),
        "profit": (["profit", "crop_name"], [False, True]),
        "growth": (["days_to_grow", "crop_name"], [True, True]),
    }
    columns, ascending = ordering.get(order, ordering["name"])
    return result.sort_values(columns, ascending=ascending)


def find_crop(crops, name):
    if crops.empty:
        return None
    matches = crops[crops["crop_name"].astype(str).str.casefold() == name.casefold()]
    return None if matches.empty else matches.iloc[0].to_dict()
