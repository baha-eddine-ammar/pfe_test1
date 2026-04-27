from fastapi import FastAPI
from pydantic import BaseModel
import pandas as pd
import joblib

app = FastAPI()

model = joblib.load("model/random_forest_model.pkl")


class TemperatureInput(BaseModel):
    temperature: float


@app.get("/")
def home():
    return {"message": "Temperature prediction API is working"}


@app.post("/predict")
def predict(data: TemperatureInput):
    input_df = pd.DataFrame([[data.temperature]], columns=["temperature"])

    prediction = int(model.predict(input_df)[0])
    probability = float(model.predict_proba(input_df)[0][1])

    if probability < 0.30:
        risk = "low"
    elif probability < 0.70:
        risk = "medium"
    else:
        risk = "high"

    return {
        "temperature": data.temperature,
        "prediction": prediction,
        "failure_probability": round(probability, 4),
        "risk_level": risk
    }
