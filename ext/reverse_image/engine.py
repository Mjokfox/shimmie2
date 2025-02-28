from flask import Flask, request, jsonify
import torch
import clip
from PIL import Image
import numpy as np
import io

# gunicorn -w 1 -b 127.0.0.1:10017 engine:app
# flask pillow numpy gunicorn git+https://github.com/openai/CLIP.git

app = Flask(__name__)

# Load CLIP model
device = "cuda" if torch.cuda.is_available() else "cpu"
model, preprocess = clip.load("ViT-B/32", device)

@app.route('/extract_features', methods=['POST'])
def extract_features():
    try:
        # Receive image file
        file = request.files['image']
        image = preprocess(Image.open(io.BytesIO(file.read()))).unsqueeze(0).to(device)

        # Extract features
        with torch.no_grad():
            features = model.encode_image(image).cpu().numpy().tolist()

        return jsonify({"features": features[0]})
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/search_features', methods=['POST'])
def search_features():
    try:
        search = request.values.get("search")

        # Extract features
        with torch.no_grad():
            text_tokens = clip.tokenize([search]).to(device)
            text_embedding = model.encode_text(text_tokens).cpu().numpy().tolist()

        return jsonify({"features": text_embedding[0]})
    except Exception as e:
        print(e)
        return jsonify({"error": str(e)}), 500


if __name__ == '__main__':
    app.run(host='127.0.0.1', port=10017)
