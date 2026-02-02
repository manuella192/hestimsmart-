from flask import Flask, request, jsonify
import requests
from dotenv import load_dotenv
import os
import json
import logging
import re
from pathlib import Path

# Charger .env (depuis le dossier courant)
load_dotenv()

BASE_DIR = Path(__file__).resolve().parent
LOG_DIR = BASE_DIR / "logs"
LOG_DIR.mkdir(exist_ok=True)

logging.basicConfig(
    level=logging.INFO,
    filename=str(LOG_DIR / "chatbot.log"),
    format="%(asctime)s - %(levelname)s - %(message)s"
)

app = Flask(__name__)

DEEPINFRA_API_KEY = os.getenv("DEEPINFRA_API_KEY")
MODEL_NAME = os.getenv("MODEL_NAME", "meta-llama/Meta-Llama-3-70B-Instruct")
DEEPINFRA_API_URL = "https://api.deepinfra.com/v1/openai/chat/completions"

if not DEEPINFRA_API_KEY:
    raise RuntimeError("DEEPINFRA_API_KEY manquant. Mets-le dans chatbot_ai/.env")

def load_knowledge_base():
    kb_path = BASE_DIR / "knowledge_base.json"
    try:
        with open(kb_path, "r", encoding="utf-8") as f:
            data = json.load(f)

        text = []
        for category in data.get("categories", []):
            name = category.get("name", "")
            desc = category.get("description", "")
            if name:
                text.append(f"# {name}\n{desc}".strip())

            for item in category.get("details", []):
                if name == "Tarification":
                    text.append(f"- {item.get('range_fcfa','')}: {item.get('commission','')}".strip())
                elif name in ["Partenariat", "Commande", "Processus d'Achat"]:
                    text.append(f"{item.get('step_number','')}. {item.get('description','')}".strip())
                else:
                    text.append(f"- {item.get('description','')}".strip())

            if category.get("page_target"):
                text.append(f"Plus d'infos disponible sur la page {category['page_target']}")

        return "\n".join([t for t in text if t])

    except FileNotFoundError:
        logging.error("knowledge_base.json introuvable")
        return "Base de connaissances introuvable."
    except Exception as e:
        logging.error(f"Erreur chargement KB: {str(e)}")
        return "Erreur chargement base de connaissances."

KNOWLEDGE_BASE = load_knowledge_base()

SYSTEM_PROMPT = f"""
Tu es un assistant pédagogique pour un portail scolaire (enseignants/étudiants/admin).
Tu aides à comprendre les fonctionnalités: emploi du temps, étudiants inscrits, présence, réservations, comptes.

Base de connaissances:
{KNOWLEDGE_BASE}

Règles:
- Réponds en français.
- Réponses courtes, claires, structurées (points si besoin).
- Pas de liens, pas de markdown, pas de HTML.
- Si tu n’as pas l’info dans la base, dis: "Info non disponible dans la base. Demandez à l’administration."
- Si la question n’est pas liée au portail, dis: "Je réponds uniquement sur le portail scolaire."
""".strip()

def sanitize_text(txt: str) -> str:
    txt = txt or ""
    txt = re.sub(r"<[^>]+>", "", txt)                          # retirer HTML
    txt = re.sub(r"&[a-zA-Z0-9#]+;", "", txt)                  # retirer entities
    txt = re.sub(r"\[([^\]]+)\]\(([^)]+)\)", r"\1", txt)       # retirer markdown links
    return txt.strip()

def get_ai_response(user_message: str) -> str:
    headers = {
        "Authorization": f"Bearer {DEEPINFRA_API_KEY}",
        "Content-Type": "application/json"
    }

    payload = {
        "model": MODEL_NAME,
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": user_message}
        ],
        "temperature": 0.4,
        "max_tokens": 400
    }

    r = requests.post(DEEPINFRA_API_URL, headers=headers, json=payload, timeout=45)
    data = r.json()
    logging.info(f"DeepInfra status={r.status_code} resp={data}")

    # gestion erreurs API
    if r.status_code >= 400:
        msg = data.get("error", {}).get("message") or "Erreur DeepInfra."
        raise RuntimeError(msg)

    content = data["choices"][0]["message"]["content"]
    return sanitize_text(content)

@app.route("/health", methods=["GET"])
def health():
    return jsonify({"ok": True, "model": MODEL_NAME})

@app.route("/chat", methods=["POST"])
def chat():
    data = request.get_json(silent=True) or {}
    message = (data.get("message") or "").strip()

    if not message:
        return jsonify({"response": "Veuillez entrer une question valide.", "error": True}), 400

    try:
        response_text = get_ai_response(message)
        return jsonify({"response": response_text, "error": False})
    except Exception as e:
        logging.error(f"Chat error: {str(e)}")
        # fallback minimal
        return jsonify({
            "response": "Erreur IA momentanée. Réessayez dans quelques instants.",
            "error": True
        }), 502

if __name__ == "__main__":
    # écoute sur localhost seulement (sécurisé)
    app.run(host="127.0.0.1", port=5000, debug=True)
