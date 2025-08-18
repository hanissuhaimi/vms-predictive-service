# VMS - Vehicle Maintenance Prediction System

A comprehensive Laravel-based fleet management system that uses machine learning to predict vehicle maintenance needs and provides powerful analytics for fleet optimization.

## 🚀 Features

### 🤖 **AI Maintenance Prediction**
- Machine learning-powered maintenance category prediction
- Intelligent cost estimation with confidence scoring
- Service timeline optimization based on vehicle history

### 📊 **Fleet Analytics Dashboard**
- **Complete Fleet Analysis** - Process all historical maintenance data
- **Performance Trends** - Monthly breakdown of maintenance patterns
- **Service Type Analysis** - Comprehensive breakdown by maintenance categories
- **Fleet Health Scoring** - Overall fleet condition assessment
- **Real-time Statistics** - Live fleet metrics and KPIs

### 🚗 **Vehicle Management**
- Individual vehicle maintenance prediction
- Historical service record analysis
- Vehicle health assessment
- Maintenance scheduling recommendations

### 💰 **Cost & Performance Analysis**
- Predictive maintenance cost estimation
- Fleet efficiency metrics
- Service type distribution analysis
- Trend analysis for budget planning

## 🛠️ Tech Stack

- **Backend:** PHP 8.1+, Laravel 10
- **Database:** SQL Server / MySQL with 88K+ maintenance records
- **ML Engine:** Python 3.11, scikit-learn, pandas, numpy
- **Frontend:** Bootstrap 5, JavaScript, Font Awesome
- **Analytics:** Direct database queries for optimal performance

## 📋 System Requirements

- **PHP:** 8.1 or higher
- **Python:** 3.8+ (for ML predictions)
- **Database:** SQL Server or MySQL
- **Memory:** 1GB RAM minimum, 2GB recommended for large datasets
- **Storage:** 500MB+ for application and ML models

## ⚡ Quick Start

### 1. **Clone and Install**
```bash
git clone https://github.com/yourusername/vms-prediction.git
cd vms-prediction
composer install
```

### 2. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

### 3. **Database Configuration**
Update `.env` with your database credentials:
```env
DB_CONNECTION=sqlsrv  # or mysql
DB_HOST=127.0.0.1
DB_DATABASE=vms
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. **Python ML Dependencies**
```bash
pip install pandas numpy scikit-learn scipy
```

### 5. **Optional: Train ML Model**
```bash
python vms_model_training.py  # If you have training data
```

### 6. **Start Application**
```bash
php artisan serve
```

### 7. **Access System**
Visit: [http://localhost:8000](http://localhost:8000)

## 🎯 How to Use

### **Individual Vehicle Prediction**
1. Navigate to the main dashboard
2. Enter vehicle registration number
3. Input current mileage
4. Get instant AI-powered maintenance prediction

### **Fleet Analysis**
1. Click "Fleet Analysis" button on main dashboard
2. Wait 2-3 minutes for complete analysis of all records
3. View comprehensive fleet insights including:
   - Total fleet statistics
   - Monthly maintenance trends
   - Service type breakdown
   - Fleet health metrics

## 📈 Analytics Features

### **Fleet Overview**
- Total vehicles in fleet
- Complete service history analysis
- Fleet health score calculation
- Service efficiency metrics

### **Maintenance Trends**
- Monthly service patterns
- Trend direction analysis (increasing/decreasing/stable)
- Average monthly service counts
- Historical data spanning multiple years

### **Service Categories**
- **Maintenance** - General repairs and upkeep
- **Cleaning/Washing** - Vehicle cleaning services
- **Tires** - Tire-related services and repairs
- **Rental** - Vehicle rental operations
- **Operation** - Operational services

## 📊 Performance

- **Dataset Size:** 88,000+ maintenance records
- **Processing Time:** 2-3 minutes for complete fleet analysis
- **Memory Usage:** Optimized for 1GB+ RAM systems
- **Database:** Efficient queries with proper indexing

---

**Built for comprehensive fleet management and intelligent maintenance prediction.**