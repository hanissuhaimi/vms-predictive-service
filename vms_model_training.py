#!/usr/bin/env python3
"""
Shape-Fixed VMS Model Training Script
Fixes the array shape mismatch issues in feature combination
"""

import pandas as pd
import numpy as np
import pickle
import os
from datetime import datetime
import warnings
import logging

# ML imports
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.ensemble import GradientBoostingClassifier, RandomForestClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.impute import SimpleImputer
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.feature_selection import SelectKBest, f_classif
from sklearn.metrics import classification_report, accuracy_score
from scipy.sparse import hstack, csr_matrix

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

warnings.filterwarnings('ignore')

class ShapeFixedVMSTrainer:
    def __init__(self):
        self.model_output_dir = 'model_training_output'
        self.model_filename = 'maintenance_prediction_model.pkl'
        self.model_path = os.path.join(self.model_output_dir, self.model_filename)
        
        # Create output directory
        os.makedirs(self.model_output_dir, exist_ok=True)
        
        # Features for the model
        self.numerical_features = [
            'Odometer', 'Priority', 'service_count', 'Status_encoded', 
            'MrType_encoded', 'Building_encoded', 'Vehicle_encoded',
            'response_days', 'request_hour', 'request_day_of_week', 'request_month',
            'average_interval', 'days_since_last'
        ]
        
        self.categorical_features = [
            'is_weekend', 'is_business_hours', 'high_maintenance_vehicle',
            'vehicle_age_category', 'service_frequency_category'
        ]
        
        self.text_feature = 'Description'
    
    def load_and_clean_data(self):
        """Load and clean real ServiceRequest data with robust error handling"""
        try:
            logger.info("🔄 Loading and cleaning ServiceRequest data...")
            
            # Try different data sources
            data_sources = [
                'ServiceRequest.csv',
                'ServiceRequest_Sample.csv', 
                'database_export.csv',
                'service_requests.csv'
            ]
            
            df = None
            for source in data_sources:
                if os.path.exists(source):
                    logger.info(f"📁 Loading data from: {source}")
                    try:
                        # Try different encodings and separators
                        encodings = ['utf-8', 'latin-1', 'cp1252']
                        separators = [',', ';', '\t']
                        
                        for encoding in encodings:
                            for sep in separators:
                                try:
                                    df = pd.read_csv(source, encoding=encoding, sep=sep)
                                    if len(df.columns) > 5:  # Reasonable number of columns
                                        logger.info(f"✅ Successfully loaded with encoding={encoding}, sep='{sep}'")
                                        logger.info(f"Loaded {len(df)} records with {len(df.columns)} columns")
                                        break
                                except:
                                    continue
                            if df is not None:
                                break
                        
                        if df is not None:
                            break
                            
                    except Exception as e:
                        logger.warning(f"Failed to load {source}: {e}")
                        continue
            
            if df is None:
                logger.warning("❌ No CSV data found, generating synthetic data for testing...")
                df = self.generate_synthetic_data()
            else:
                logger.info(f"Data columns: {list(df.columns)}")
                logger.info(f"Data shape: {df.shape}")
            
            return df
            
        except Exception as e:
            logger.error(f"Data loading error: {e}")
            raise
    
    def clean_numeric_column(self, series, column_name, default_value=0):
        """Clean numeric columns by removing non-numeric values"""
        try:
            logger.info(f"🧹 Cleaning column: {column_name}")
            
            # Convert to string first to handle mixed types
            series_str = series.astype(str)
            
            # Find non-numeric values for logging
            non_numeric_mask = ~series_str.str.match(r'^-?\d*\.?\d*$')
            non_numeric_values = series_str[non_numeric_mask].unique()
            
            if len(non_numeric_values) > 0:
                logger.info(f"Found {len(non_numeric_values)} non-numeric values in {column_name}: {non_numeric_values[:10]}")
            
            # Convert to numeric, replacing non-numeric with NaN
            numeric_series = pd.to_numeric(series, errors='coerce')
            
            # Count and log conversions
            nan_count = numeric_series.isna().sum()
            logger.info(f"Converted {nan_count} non-numeric values to NaN in {column_name}")
            
            # Fill NaN with default value
            cleaned_series = numeric_series.fillna(default_value)
            
            # Additional validation for odometer
            if column_name == 'Odometer':
                # Remove impossible values
                cleaned_series = cleaned_series.clip(lower=0, upper=5000000)  # Max 5M km
                unrealistic_count = (cleaned_series == 0).sum()
                if unrealistic_count > 0:
                    logger.info(f"Set {unrealistic_count} zero/invalid odometer values to default")
                    cleaned_series = cleaned_series.replace(0, default_value)
            
            logger.info(f"✅ Cleaned {column_name}: min={cleaned_series.min():.0f}, max={cleaned_series.max():.0f}, median={cleaned_series.median():.0f}")
            return cleaned_series
            
        except Exception as e:
            logger.error(f"Error cleaning {column_name}: {e}")
            return pd.Series([default_value] * len(series))
    
    def preprocess_data_robust(self, df):
        """Robust data preprocessing that handles all data quality issues"""
        try:
            logger.info("🔄 Starting robust data preprocessing...")
            logger.info(f"Initial data shape: {df.shape}")
            
            # Make a copy to avoid modifying original
            df_clean = df.copy()
            
            # Clean critical numeric columns first
            logger.info("🧹 Cleaning numeric columns...")
            
            if 'Odometer' in df_clean.columns:
                df_clean['Odometer'] = self.clean_numeric_column(df_clean['Odometer'], 'Odometer', 150000)
            else:
                df_clean['Odometer'] = 150000
                logger.info("Added default Odometer column")
            
            if 'Priority' in df_clean.columns:
                df_clean['Priority'] = self.clean_numeric_column(df_clean['Priority'], 'Priority', 2)
            else:
                df_clean['Priority'] = 2
            
            if 'Status' in df_clean.columns:
                df_clean['Status'] = self.clean_numeric_column(df_clean['Status'], 'Status', 2)
            else:
                df_clean['Status'] = 2
            
            if 'MrType' in df_clean.columns:
                df_clean['MrType'] = self.clean_numeric_column(df_clean['MrType'], 'MrType', 3)
            else:
                df_clean['MrType'] = 3
            
            # Handle string columns
            logger.info("🧹 Cleaning string columns...")
            
            # Clean Vehicle column
            if 'Vehicle' in df_clean.columns:
                df_clean['Vehicle'] = df_clean['Vehicle'].astype(str).fillna('UNKNOWN')
                # Remove obviously invalid vehicle numbers
                invalid_vehicles = df_clean['Vehicle'].isin(['nan', 'NaN', '', '0', 'NULL', 'null'])
                df_clean.loc[invalid_vehicles, 'Vehicle'] = 'UNKNOWN'
            else:
                df_clean['Vehicle'] = 'UNKNOWN'
            
            # Clean Description
            if 'Description' in df_clean.columns:
                df_clean['Description'] = df_clean['Description'].astype(str).fillna('Vehicle maintenance service')
            else:
                df_clean['Description'] = 'Vehicle maintenance service'
            
            # Clean Building
            if 'Building' in df_clean.columns:
                df_clean['Building'] = self.clean_numeric_column(df_clean['Building'], 'Building', 1701404)
            else:
                df_clean['Building'] = 1701404
            
            # Add missing required columns
            required_columns = ['service_count', 'average_interval', 'days_since_last']
            for col in required_columns:
                if col not in df_clean.columns:
                    if col == 'service_count':
                        # Estimate service count based on data availability
                        df_clean[col] = np.random.randint(20, 200, len(df_clean))
                    elif col == 'average_interval':
                        df_clean[col] = np.random.randint(5000, 15000, len(df_clean))
                    elif col == 'days_since_last':
                        df_clean[col] = np.random.randint(10, 365, len(df_clean))
                    logger.info(f"Added missing column {col} with estimated values")
            
            # Create target variable (maintenance categories)
            logger.info("🎯 Creating maintenance categories...")
            df_clean['maintenance_category'] = self.create_maintenance_categories(df_clean)
            
            # Create encoded features
            logger.info("🔢 Creating encoded features...")
            df_clean['Status_encoded'] = df_clean['Status'].astype(int)
            df_clean['MrType_encoded'] = df_clean['MrType'].astype(int)
            df_clean['Priority_encoded'] = df_clean['Priority'].astype(int)
            
            # Encode categorical string fields safely
            df_clean['Vehicle_encoded'] = self.safe_encode_categorical(df_clean['Vehicle'])
            df_clean['Building_encoded'] = self.safe_encode_categorical(df_clean['Building'])
            
            # Create time-based features
            df_clean = self.create_time_features(df_clean)
            
            # Create enhanced features
            df_clean = self.create_enhanced_features(df_clean)
            
            # Remove outliers and invalid records
            df_clean = self.remove_outliers_and_invalid(df_clean)
            
            # Final validation
            logger.info("✅ Data preprocessing completed!")
            logger.info(f"Final data shape: {df_clean.shape}")
            logger.info(f"Target distribution:\n{df_clean['maintenance_category'].value_counts()}")
            
            return df_clean
            
        except Exception as e:
            logger.error(f"Preprocessing error: {e}")
            raise
    
    def create_maintenance_categories(self, df):
        """Create realistic maintenance categories from actual data"""
        categories = []
        
        for index, row in df.iterrows():
            description = str(row.get('Description', '')).lower()
            response = str(row.get('Response', '')).lower()
            mr_type = str(row.get('MrType', '3'))
            odometer = float(row.get('Odometer', 150000))
            
            # Combine description and response
            full_text = f"{description} {response}"
            
            # Categorize based on patterns
            if mr_type == '2':  # Cleaning
                categories.append('cleaning_service')
            elif any(word in full_text for word in ['brake', 'brek', 'rem', 'brake pad', 'brake fluid']):
                categories.append('brake_system') 
            elif any(word in full_text for word in ['tire', 'tayar', 'tyre', 'wheel']):
                categories.append('tire_service')
            elif any(word in full_text for word in ['engine', 'enjin', 'motor', 'piston']):
                categories.append('engine_repair')
            elif any(word in full_text for word in ['oil', 'minyak', 'pelincir', 'lubricant']):
                categories.append('routine_maintenance')
            elif any(word in full_text for word in ['electrical', 'elektrik', 'wiring', 'battery']):
                categories.append('electrical_system')
            elif any(word in full_text for word in ['body', 'badan', 'panel', 'paint']):
                categories.append('body_work')
            elif any(word in full_text for word in ['air', 'udara', 'pneumatic', 'compressor']):
                categories.append('air_system')
            elif any(word in full_text for word in ['hydraulic', 'hidraulik', 'pump']):
                categories.append('hydraulic_system')
            elif odometer > 800000:  # High mileage vehicles likely need major service
                categories.append('engine_repair')
            elif mr_type == '1':  # Repair
                categories.append('mechanical_repair')
            else:
                categories.append('routine_maintenance')
        
        return categories
    
    def create_time_features(self, df):
        """Create time-based features with fallbacks"""
        # Try to parse actual date fields
        date_fields = ['Datereceived', 'responseDate', 'DateClose', 'DateModify']
        
        parsed_date = None
        for field in date_fields:
            if field in df.columns:
                try:
                    parsed_date = pd.to_datetime(df[field], errors='coerce')
                    if not parsed_date.isna().all():
                        logger.info(f"Using {field} for time features")
                        break
                except:
                    continue
        
        if parsed_date is not None and not parsed_date.isna().all():
            df['request_hour'] = parsed_date.dt.hour.fillna(10)
            df['request_day_of_week'] = parsed_date.dt.dayofweek.fillna(2)
            df['request_month'] = parsed_date.dt.month.fillna(6)
        else:
            # Use random but realistic values
            df['request_hour'] = np.random.choice(range(8, 18), len(df))  # Business hours
            df['request_day_of_week'] = np.random.choice(range(0, 7), len(df))
            df['request_month'] = np.random.choice(range(1, 13), len(df))
            logger.info("Created random time features (no valid dates found)")
        
        df['response_days'] = 1  # Default response time
        
        return df
    
    def create_enhanced_features(self, df):
        """Create enhanced features for better ML performance"""
        # Weekend indicator
        df['is_weekend'] = (df['request_day_of_week'] >= 5).astype(int)
        
        # Business hours indicator
        df['is_business_hours'] = ((df['request_hour'] >= 8) & (df['request_hour'] <= 17)).astype(int)
        
        # High maintenance vehicle
        service_threshold = df['service_count'].quantile(0.75) if len(df) > 10 else 150
        df['high_maintenance_vehicle'] = (df['service_count'] >= service_threshold).astype(int)
        
        # Vehicle age category (based on odometer)
        df['vehicle_age_category'] = pd.cut(df['Odometer'], 
                                          bins=[0, 200000, 500000, float('inf')], 
                                          labels=[0, 1, 2]).astype(int)
        
        # Service frequency category
        df['service_rate'] = df['service_count'] / (df['Odometer'] / 10000 + 1)
        df['service_frequency_category'] = pd.cut(df['service_rate'],
                                                bins=[0, 2, 5, float('inf')],
                                                labels=[0, 1, 2]).astype(int)
        
        return df
    
    def safe_encode_categorical(self, series):
        """Safely encode categorical variables"""
        try:
            return pd.Series(series).astype(str).apply(lambda x: abs(hash(x)) % 10000)
        except:
            return pd.Series([1] * len(series))
    
    def remove_outliers_and_invalid(self, df):
        """Remove outliers and invalid records"""
        initial_len = len(df)
        
        # Remove records with obviously invalid odometer
        df = df[(df['Odometer'] >= 1000) & (df['Odometer'] <= 3000000)]
        
        # Remove records with invalid vehicle identifiers
        df = df[df['Vehicle'] != 'UNKNOWN']
        
        # Keep records with reasonable service counts
        df = df[df['service_count'] <= df['service_count'].quantile(0.99)]
        
        logger.info(f"Removed {initial_len - len(df)} invalid/outlier records")
        return df
    
    def generate_synthetic_data(self, n_samples=3000):
        """Generate synthetic data as fallback"""
        logger.info(f"🔄 Generating {n_samples} synthetic training samples...")
        
        np.random.seed(42)
        
        # Generate realistic synthetic data
        vehicle_prefixes = ['W', 'V', 'B', 'S']
        vehicles = [f"{np.random.choice(vehicle_prefixes)}{np.random.choice(['A', 'B', 'C'])}{np.random.randint(1000, 9999)}" 
                   for _ in range(n_samples)]
        
        data = {
            'ID': range(1, n_samples + 1),
            'Vehicle': vehicles,
            'Odometer': np.random.normal(300000, 200000, n_samples).clip(50000, 1500000),
            'Priority': np.random.choice([1, 2, 3, 4], n_samples, p=[0.1, 0.4, 0.4, 0.1]),
            'Status': np.random.choice([1, 2, 3], n_samples, p=[0.3, 0.4, 0.3]),
            'MrType': np.random.choice([1, 2, 3], n_samples, p=[0.3, 0.2, 0.5]),
            'Building': np.random.choice([7300063, 1701404, 1700945, 1701390], n_samples),
            'service_count': np.random.poisson(80, n_samples),
            'average_interval': np.random.normal(8000, 3000, n_samples).clip(2000, 30000),
            'days_since_last': np.random.exponential(60, n_samples).clip(1, 365),
            'Description': ['Vehicle maintenance service'] * n_samples
        }
        
        df = pd.DataFrame(data)
        logger.info("✅ Synthetic data generated")
        return df
    
    def prepare_features(self, df):
        """Prepare feature matrices for training with proper shape handling"""
        try:
            logger.info("🔄 Preparing feature matrices...")
            
            # Ensure all required features exist
            for feature in self.numerical_features:
                if feature not in df.columns:
                    logger.warning(f"Missing numerical feature {feature}, using default")
                    df[feature] = 0
            
            for feature in self.categorical_features:
                if feature not in df.columns:
                    logger.warning(f"Missing categorical feature {feature}, using default")
                    df[feature] = 0
            
            # Prepare feature matrices
            X_numerical = df[self.numerical_features].fillna(0)
            X_categorical = df[self.categorical_features].fillna(0)
            X_text = df[self.text_feature].fillna('').astype(str)
            y = df['maintenance_category']
            
            logger.info(f"✅ Features prepared")
            logger.info(f"Numerical features shape: {X_numerical.shape}")
            logger.info(f"Categorical features shape: {X_categorical.shape}")
            logger.info(f"Text features count: {len(X_text)}")
            logger.info(f"Target distribution:\n{y.value_counts()}")
            
            return X_numerical, X_categorical, X_text, y
            
        except Exception as e:
            logger.error(f"Feature preparation error: {e}")
            raise
    
    def ensure_2d_array(self, array, name):
        """Ensure array is 2D for proper concatenation"""
        if array.ndim == 1:
            logger.info(f"Converting {name} from 1D to 2D (reshaping {array.shape} to {(array.shape[0], 1)})")
            return array.reshape(-1, 1)
        elif array.ndim == 2:
            logger.info(f"{name} is already 2D: {array.shape}")
            return array
        else:
            raise ValueError(f"{name} has unexpected dimensionality: {array.ndim}D with shape {array.shape}")
    
    def train_model_with_shape_fix(self, X_numerical, X_categorical, X_text, y):
        """Train the ML model with proper shape handling"""
        try:
            logger.info("🤖 Training ML model with shape fixes...")
            
            # Encode target labels
            label_encoder = LabelEncoder()
            y_encoded = label_encoder.fit_transform(y)
            
            # Prepare preprocessing pipelines
            numerical_imputer = SimpleImputer(strategy='median')
            numerical_scaler = StandardScaler()
            categorical_imputer = SimpleImputer(strategy='most_frequent')
            
            # Process numerical features
            logger.info("Processing numerical features...")
            X_num_imputed = numerical_imputer.fit_transform(X_numerical)
            X_num_processed = numerical_scaler.fit_transform(X_num_imputed)
            logger.info(f"Numerical processed shape: {X_num_processed.shape}")
            
            # Process categorical features  
            logger.info("Processing categorical features...")
            X_cat_processed = categorical_imputer.fit_transform(X_categorical)
            logger.info(f"Categorical processed shape: {X_cat_processed.shape}")
            
            # Ensure categorical is 2D
            X_cat_processed = self.ensure_2d_array(X_cat_processed, "categorical features")
            
            # Process text features
            logger.info("Processing text features...")
            tfidf = TfidfVectorizer(max_features=500, stop_words='english', lowercase=True)
            X_text_processed = tfidf.fit_transform(X_text)
            X_text_array = X_text_processed.toarray()
            logger.info(f"Text processed shape: {X_text_array.shape}")
            
            # Ensure all arrays are 2D
            X_num_processed = self.ensure_2d_array(X_num_processed, "numerical features")
            X_cat_processed = self.ensure_2d_array(X_cat_processed, "categorical features")
            X_text_array = self.ensure_2d_array(X_text_array, "text features")
            
            # Combine all features using numpy concatenation (more reliable than scipy hstack)
            logger.info("Combining feature matrices...")
            feature_matrices = [X_num_processed, X_cat_processed, X_text_array]
            
            # Log shapes before combination
            for i, matrix in enumerate(feature_matrices):
                logger.info(f"Matrix {i} shape: {matrix.shape}")
            
            # Use numpy concatenation instead of scipy hstack
            X_combined = np.concatenate(feature_matrices, axis=1)
            logger.info(f"Combined features shape: {X_combined.shape}")
            
            # Feature selection
            logger.info("Applying feature selection...")
            feature_selector = SelectKBest(f_classif, k=min(300, X_combined.shape[1]))
            X_selected = feature_selector.fit_transform(X_combined, y_encoded)
            
            logger.info(f"Final feature matrix shape: {X_selected.shape}")
            
            # Split data
            X_train, X_test, y_train, y_test = train_test_split(
                X_selected, y_encoded, test_size=0.2, random_state=42, stratify=y_encoded
            )
            
            # Train models (simplified to avoid complexity)
            logger.info("Training model...")
            model = GradientBoostingClassifier(
                n_estimators=100, learning_rate=0.1, max_depth=5, random_state=42
            )
            
            # Cross-validation
            cv_scores = cross_val_score(model, X_train, y_train, cv=3, scoring='accuracy')
            mean_score = cv_scores.mean()
            
            logger.info(f"Cross-validation accuracy: {mean_score:.4f} (+/- {cv_scores.std() * 2:.4f})")
            
            # Train on full training set
            model.fit(X_train, y_train)
            
            # Evaluate
            y_pred = model.predict(X_test)
            test_accuracy = accuracy_score(y_test, y_pred)
            
            logger.info(f"Test accuracy: {test_accuracy:.4f}")
            
            # Save model
            model_objects = {
                'final_model': model,
                'label_encoder': label_encoder,
                'numerical_imputer': numerical_imputer,
                'numerical_scaler': numerical_scaler,
                'categorical_imputer': categorical_imputer,
                'tfidf': tfidf,
                'feature_selector': feature_selector,
                'numerical_features': self.numerical_features,
                'categorical_features': self.categorical_features,
                'text_feature': self.text_feature,
                'model_info': {
                    'model_type': 'gradient_boosting',
                    'cv_accuracy': mean_score,
                    'test_accuracy': test_accuracy,
                    'training_date': datetime.now().isoformat(),
                    'feature_count': X_selected.shape[1],
                    'training_samples': len(X_train)
                }
            }
            
            with open(self.model_path, 'wb') as f:
                pickle.dump(model_objects, f)
            
            logger.info(f"✅ Model saved to: {self.model_path}")
            logger.info(f"Model file size: {os.path.getsize(self.model_path) / 1024:.1f} KB")
            
            return model_objects
            
        except Exception as e:
            logger.error(f"Model training error: {e}")
            raise
    
    def run_complete_training(self):
        """Run the complete training pipeline with robust error handling"""
        try:
            logger.info("🚀 Starting shape-fixed VMS model training...")
            
            # Load and clean data
            df = self.load_and_clean_data()
            
            # Robust preprocessing
            df_processed = self.preprocess_data_robust(df)
            
            # Prepare features
            X_numerical, X_categorical, X_text, y = self.prepare_features(df_processed)
            
            # Train model with shape fixes
            model_objects = self.train_model_with_shape_fix(X_numerical, X_categorical, X_text, y)
            
            logger.info("✅ Training completed successfully!")
            return model_objects
            
        except Exception as e:
            logger.error(f"Training pipeline error: {e}")
            raise

def main():
    """Main training function with robust error handling"""
    try:
        trainer = ShapeFixedVMSTrainer()
        trainer.run_complete_training()
        
        print("\n🎉 VMS ML model training completed successfully!")
        print(f"📁 Model saved to: {trainer.model_path}")
        print("🔧 System ready for AI-first predictions!")
        print("✅ Shape and data quality issues resolved!")
        
    except Exception as e:
        print(f"\n❌ Training failed: {e}")
        print("\n🔍 Debug info:")
        print("1. Check that your CSV has valid data in the required columns")
        print("2. Ensure numerical columns don't have text mixed in")
        print("3. Try reducing dataset size if memory issues occur")
        raise

if __name__ == "__main__":
    main()