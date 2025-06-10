# VMS Vehicle Maintenance Prediction System

A Laravel-based vehicle maintenance prediction system using machine learning to categorize maintenance requests.

## Features

- 🚗 **Vehicle Maintenance Prediction** - AI-powered categorization of maintenance requests
- 🤖 **Machine Learning Model** - Gradient Boosting Classifier with 91.5% accuracy
- 📱 **Web Interface** - User-friendly Laravel application
- 🔧 **Smart Analysis** - Analyzes vehicle condition, mileage, and problem description
- 💰 **Cost Estimation** - Provides repair cost and time estimates

## Tech Stack

- **Backend:** PHP 8.1+, Laravel 10
- **Machine Learning:** Python 3.11, scikit-learn, pandas, numpy
- **Frontend:** Bootstrap 5, HTML5, CSS3, JavaScript
- **Database:** MySQL (optional)

## Installation

### Prerequisites

- PHP 8.1+
- Composer
- Python 3.8+
- Git

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/vms-prediction.git
   cd vms-prediction
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Install Python dependencies**
   ```bash
   pip install pandas numpy scikit-learn scipy
   ```

5. **Train the model (if needed)**
   ```bash
   python vms_model_training.py
   ```

6. **Start the server**
   ```bash
   php artisan serve
   ```

7. **Visit the application**
   Open http://localhost:8000

## Usage

1. **Access the prediction interface**
2. **Enter vehicle details:**
   - Problem description
   - Current mileage (KM)
   - Vehicle number plate
3. **Submit for analysis**
4. **Get instant prediction:**
   - Maintenance category
   - Cost estimate
   - Time required
   - Next steps

## Model Categories

The system can predict these maintenance categories:

- 🛑 **Brake System** - Brake repairs and maintenance
- 🛞 **Tire Service** - Tire replacement and alignment  
- 🚗 **Engine Repair** - Engine-related issues
- 🧽 **Cleaning Service** - Vehicle washing and cleaning
- ⚙️ **Routine Maintenance** - Regular service and check-ups
- ⚡ **Electrical System** - Battery, lights, wiring
- 🔧 **Mechanical Repair** - General mechanical issues
- 💨 **Air System** - Air conditioning and ventilation
- 💧 **Hydraulic System** - Hydraulic components
- 🚛 **Body Work** - Vehicle body repairs

## Project Structure

```
vms-prediction/
├── app/
│   ├── Http/Controllers/
│   │   └── PredictionController.php
│   └── Services/
│       └── VMSPredictionService.php
├── python/
│   └── predict.py
├── model_training_output/
│   └── maintenance_prediction_model.pkl
├── resources/views/
│   ├── layouts/app.blade.php
│   └── prediction/
│       ├── index.blade.php
│       └── result.blade.php
├── vms_model_training.py
└── README.md
```

## Model Performance

- **Algorithm:** Gradient Boosting Classifier
- **Accuracy:** 91.5%
- **Training Data:** 500K+ maintenance records
- **Features:** 50+ engineered features
- **Categories:** 10+ maintenance types

## API Usage

The system provides a clean interface between Laravel and Python ML model:

### Example Request Data:
```json
{
    "Description": "brake noise when stopping",
    "Odometer": 200000,
    "Priority": 1,
    "service_count": 150,
    "Vehicle_encoded": 693
}
```

### Example Response:
```json
{
    "prediction": "brake_system",
    "confidence": 0.89,
    "model_type": "Gradient Boosting"
}
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request