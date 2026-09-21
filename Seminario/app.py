from io import BytesIO
from datetime import datetime
from urllib.parse import unquote
from zoneinfo import ZoneInfo

import matplotlib

matplotlib.use("Agg")

import matplotlib.pyplot as plt
from flask import Flask, abort, render_template, request, send_file

from utils.analysis import dashboard_stats, profit_by_crop, season_counts, secondary_stats
from utils.data_loader import (
    crop_data_available, filter_crops, find_crop, load_crops, load_fish, load_villagers,
)


app = Flask(__name__)

SEASONS = {
    "spring": {"name": "Primavera", "icon": "🌼", "image": "images/Flower_Festival.jpg"},
    "summer": {"name": "Verão", "icon": "☀️", "image": "images/Luau.png"},
    "fall": {"name": "Outono", "icon": "🍂", "image": "images/Spirits_Eve.png"},
    "winter": {"name": "Inverno", "icon": "❄️", "image": "images/Feast_of_the_Winterstar.png"},
}


def current_season():
    """Retorna a estação atual no hemisfério sul (fuso de São Paulo)."""
    today = datetime.now(ZoneInfo("America/Sao_Paulo"))
    date = (today.month, today.day)
    if date >= (12, 21) or date < (3, 21):
        return "summer"
    if date < (6, 21):
        return "fall"
    if date < (9, 23):
        return "winter"
    return "spring"


@app.context_processor
def inject_globals():
    return {"data_available": crop_data_available()}


@app.get("/")
def index():
    crops = load_crops()
    # ?season=spring permite pré-visualizar as quatro imagens durante a apresentação.
    selected = request.args.get("season", "").lower()
    season = SEASONS.get(selected, SEASONS[current_season()])
    return render_template("index.html", stats=dashboard_stats(crops), current_season=season)


@app.get("/crops")
def crops():
    query = request.args.get("q", "").strip()
    season = request.args.get("season", "").strip()
    order = request.args.get("order", "name").strip()
    all_crops = load_crops()
    result = filter_crops(all_crops, query=query, season=season, order=order)
    seasons = sorted(all_crops["season"].dropna().astype(str).unique()) if not all_crops.empty else []
    return render_template(
        "crops.html",
        crops=result.to_dict("records"),
        query=query,
        selected_season=season,
        selected_order=order,
        seasons=seasons,
    )


@app.get("/crop/<path:name>")
def crop_detail(name):
    crop = find_crop(load_crops(), unquote(name))
    if crop is None:
        abort(404)
    return render_template("crop.html", crop=crop)


@app.get("/dashboard")
def dashboard():
    crops = load_crops()
    fish = load_fish()
    villagers = load_villagers()
    return render_template(
        "dashboard.html",
        stats=dashboard_stats(crops),
        extra_stats=secondary_stats(fish, villagers),
        has_data=not crops.empty,
        has_archive_data=not fish.empty or not villagers.empty,
    )


@app.get("/chart/<chart_name>.png")
def chart(chart_name):
    crops = load_crops()
    fish = load_fish()
    villagers = load_villagers()
    crop_charts = {"seasons", "profits", "growth"}
    fish_charts = {"fish_value", "fish_seasons"}
    if (chart_name in crop_charts and crops.empty) or (chart_name in fish_charts and fish.empty):
        abort(404)

    plt.style.use("seaborn-v0_8-whitegrid")
    fig, ax = plt.subplots(figsize=(8, 4.5))
    fig.patch.set_facecolor("#fffaf2")
    ax.set_facecolor("#fffaf2")

    if chart_name == "seasons":
        values = season_counts(crops)
        ax.bar(values.index, values.values, color="#77a85b")
        ax.set_title("Culturas por estação")
        ax.set_ylabel("Quantidade")
    elif chart_name == "profits":
        values = profit_by_crop(crops, limit=8).sort_values()
        ax.barh(values.index, values.values, color="#e9a05f")
        ax.set_title("Culturas com maior lucro por unidade")
        ax.set_xlabel("Lucro (g)")
    elif chart_name == "growth":
        ax.scatter(crops["days_to_grow"], crops["sell_price"], color="#7f6bb2", alpha=.8, s=55)
        ax.set_title("Tempo de crescimento × preço de venda")
        ax.set_xlabel("Dias para crescer")
        ax.set_ylabel("Preço de venda (g)")
    elif chart_name == "fish_value":
        values = fish.dropna(subset=["difficulty", "base_price"])
        points = ax.scatter(values["difficulty"], values["base_price"], c=values["base_xp"],
                            cmap="YlGnBu", alpha=.82, s=65, edgecolor="white")
        ax.set_title("Dificuldade × preço dos peixes")
        ax.set_xlabel("Dificuldade de captura")
        ax.set_ylabel("Preço-base (g)")
        fig.colorbar(points, ax=ax, label="XP base")
    elif chart_name == "fish_seasons":
        seasons = ["Spring", "Summer", "Fall", "Winter"]
        counts = {season: fish["season"].astype(str).str.contains(season, case=False, na=False).sum() for season in seasons}
        ax.bar(counts.keys(), counts.values(), color=["#8abf69", "#e5b959", "#d9824f", "#7ba5b7"])
        ax.set_title("Peixes disponíveis por estação")
        ax.set_ylabel("Quantidade")
    elif chart_name == "villagers":
        if villagers.empty:
            abort(404)
        counts = villagers["gender"].fillna("Outro").value_counts()
        ax.pie(counts.values, labels=counts.index, autopct="%1.0f%%", colors=["#6e9bb0", "#df8d82", "#a9bd75"])
        ax.set_title("Distribuição dos moradores")
    else:
        abort(404)

    fig.tight_layout()
    output = BytesIO()
    fig.savefig(output, format="png", dpi=130, bbox_inches="tight")
    plt.close(fig)
    output.seek(0)
    return send_file(output, mimetype="image/png", max_age=300)


@app.errorhandler(404)
def not_found(_error):
    return render_template("404.html"), 404


if __name__ == "__main__":
    app.run(debug=True)
