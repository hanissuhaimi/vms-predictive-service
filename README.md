# VMS - Vehicle Maintenance Prediction System

A Laravel-based system that uses machine learning to predict vehicle maintenance needs and analyze fleet health.

## Features

- 🤖 **AI Maintenance Prediction** - 91.5% accuracy using Gradient Boosting
- 🚗 **Vehicle Health Analysis** - Comprehensive condition assessment
- 📊 **Service Scheduling** - Smart maintenance timeline optimization
- 💰 **Cost Estimation** - Predictive cost analysis with ranges
- 📈 **Fleet Dashboard** - Overview of all vehicles

## Tech Stack

- **Backend:** PHP 8.1+, Laravel 10
- **ML:** Python 3.11, scikit-learn, pandas, numpy
- **Database:** SQL Server / MySQL
- **Frontend:** Bootstrap 5, JavaScript

## Quick Start

1. **Clone and install**
   ```bash
   git clone https://github.com/yourusername/vms-prediction.git
   cd vms-prediction
   composer install
   ```

2. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure database** in `.env`
   ```env
   DB_CONNECTION=sqlsrv
   DB_HOST=127.0.0.1
   DB_DATABASE=vms
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

4. **Install Python ML dependencies**
   ```bash
   pip install pandas numpy scikit-learn scipy
   ```

5. **Train model and start server**
   ```bash
   python vms_model_training.py
   php artisan serve
   ```

6. **Visit** http://localhost:8000

## Usage

1. Enter vehicle number and current mileage
2. Get instant AI prediction with:
   - Maintenance category
   - Cost estimate
   - Service timeline
   - Safety assessment

## Maintenance Categories

- 🛑 Brake System
- 🛞 Tire Service  
- 🚗 Engine Repair
- ⚙️ Routine Maintenance
- ⚡ Electrical System
- 🔧 Mechanical Repair
- 💨 Air System
- 💧 Hydraulic System
- 🚛 Body Work
- 🧽 Cleaning Service

## Requirements

- PHP 8.1+
- Python 3.8+
- SQL Server or MySQL
- 2GB RAM minimum

## License

MIT License