#!/usr/bin/env python3

import sys
import json
import pickle
import pandas as pd
import numpy as np
import os
import warnings
from datetime import datetime
import logging

# Configure logging to go to stderr (not stdout)
logging.basicConfig(
    level=logging.ERROR,  # Only errors to stderr, info goes to file
    format='%(asctime)s - %(levelname)s - %(message)s',
    stream=sys.stderr
)
logger = logging.getLogger(__name__)

warnings.filterwarnings('ignore')

def convert_and_validate_data(data):
    """Enhanced data conversion with validation"""
    try:
        converted = data.copy()
        
        # Define fields that should be numeric with defaults
        numeric_fields = {
            'Odometer': 100000,
            'Priority': 2,
            'service_count': 50,
            'average_interval': 5000,
            'days_since_last': 30,
            'Building_encoded': 1,
            'Vehicle_encoded': 1,
            'Status_encoded': 2,  # Default to 2 (MO Created) 
            'MrType_encoded': 3,
            'response_days': 1,
            'request_hour': 10,
            'request_day_of_week': 2,
            'request_month': 6
        }
        
        # Convert and validate numeric fields
        for field, default_value in numeric_fields.items():
            if field in converted:
                try:
                    value = converted[field]
                    if isinstance(value, str):
                        if '.' in value:
                            converted[field] = float(value)
                        else:
                            converted[field] = int(value)
                    elif isinstance(value, (int, float)):
                        converted[field] = value
                    else:
                        converted[field] = default_value
                        
                    # Validate ranges
                    if field == 'Odometer' and converted[field] < 0:
                        converted[field] = default_value
                    elif field == 'Priority' and not (1 <= converted[field] <= 4):
                        converted[field] = 2
                    elif field == 'Status_encoded' and not (0 <= converted[field] <= 3):
                        converted[field] = 2  # Default to MO Created
                        
                except (ValueError, TypeError) as e:
                    converted[field] = default_value
            else:
                converted[field] = default_value
        
        # Handle string fields
        converted['Description'] = str(converted.get('Description', 'Vehicle prediction request'))
        converted['Vehicle'] = str(converted.get('Vehicle', 'UNKNOWN'))
        
        # Generate encoded values if missing
        if 'Vehicle_encoded' not in converted or converted['Vehicle_encoded'] == 1:
            converted['Vehicle_encoded'] = abs(hash(converted['Vehicle'])) % 10000
            
        if 'Building_encoded' not in converted or converted['Building_encoded'] == 1:
            building = str(converted.get('Building', 'DEFAULT'))
            converted['Building_encoded'] = abs(hash(building)) % 1000
        
        return converted
        
    except Exception as e:
        raise Exception(f"Data conversion failed: {str(e)}")

def load_model_robust(model_path):
    """Load model with enhanced error handling"""
    try:
        if not os.path.exists(model_path):
            return None, f"Model file does not exist: {model_path}"
        
        file_size = os.path.getsize(model_path)
        if file_size < 10000:  # Less than 10KB
            return None, f"Model file too small ({file_size} bytes), may be corrupted"
        
        with open(model_path, 'rb') as f:
            model_objects = pickle.load(f)
        
        # Validate model structure
        required_keys = ['final_model', 'label_encoder']
        missing_keys = [key for key in required_keys if key not in model_objects]
        if missing_keys:
            return None, f"Model missing required components: {missing_keys}"
        
        return model_objects, "Success"
        
    except Exception as e:
        return None, f"Model loading error: {str(e)}"

def create_robust_features(df):
    """Create features with fallback handling"""
    try:
        df_enhanced = df.copy()
        
        # Ensure numeric types
        numeric_cols = ['request_day_of_week', 'request_hour', 'service_count', 'Odometer']
        for col in numeric_cols:
            if col in df_enhanced.columns:
                df_enhanced[col] = pd.to_numeric(df_enhanced[col], errors='coerce').fillna(0)
        
        # Weekend feature
        if 'request_day_of_week' in df_enhanced.columns:
            day_of_week = df_enhanced['request_day_of_week']
            df_enhanced['is_weekend'] = (day_of_week >= 5).astype(int)
        else:
            df_enhanced['is_weekend'] = 0
        
        # Business hours feature  
        if 'request_hour' in df_enhanced.columns:
            hour = df_enhanced['request_hour']
            df_enhanced['is_business_hours'] = ((hour >= 8) & (hour <= 17)).astype(int)
        else:
            df_enhanced['is_business_hours'] = 1
        
        # High maintenance vehicle feature
        if 'service_count' in df_enhanced.columns:
            service_count = df_enhanced['service_count']
            # Use a fixed threshold instead of quantile for single prediction
            threshold = 200  # Reasonable threshold for high maintenance
            df_enhanced['high_maintenance_vehicle'] = (service_count >= threshold).astype(int)
        else:
            df_enhanced['high_maintenance_vehicle'] = 0
            
        # Vehicle age proxy (based on odometer)
        if 'Odometer' in df_enhanced.columns:
            odometer = df_enhanced['Odometer']
            df_enhanced['vehicle_age_category'] = np.where(odometer > 500000, 2,
                                                  np.where(odometer > 200000, 1, 0))
        else:
            df_enhanced['vehicle_age_category'] = 1
        
        # Service frequency category
        if 'service_count' in df_enhanced.columns and 'Odometer' in df_enhanced.columns:
            service_rate = df_enhanced['service_count'] / (df_enhanced['Odometer'] / 10000 + 1)
            df_enhanced['service_frequency_category'] = np.where(service_rate > 5, 2,
                                                        np.where(service_rate > 2, 1, 0))
        else:
            df_enhanced['service_frequency_category'] = 1
        
        return df_enhanced
        
    except Exception as e:
        raise Exception(f"Feature creation failed: {str(e)}")

def prepare_features_adaptive(data, model_objects):
    """Adaptive feature preparation that works with different model versions"""
    try:
        # Convert and validate data first
        converted_data = convert_and_validate_data(data)
        
        # Convert to DataFrame
        if isinstance(converted_data, dict):
            df = pd.DataFrame([converted_data])
        else:
            df = converted_data.copy()
        
        # Create enhanced features
        df_enhanced = create_robust_features(df)
        
        # Get feature configuration from model (with fallbacks)
        numerical_features = model_objects.get('numerical_features', [])
        categorical_features = model_objects.get('categorical_features', [])
        text_feature = model_objects.get('text_feature', 'Description')
        
        # If no feature configuration, try to infer
        if not numerical_features and not categorical_features:
            numerical_features = ['Odometer', 'Priority', 'service_count', 'Status_encoded', 
                                'MrType_encoded', 'request_hour', 'request_day_of_week', 
                                'is_weekend', 'is_business_hours', 'high_maintenance_vehicle']
        
        # Prepare feature matrices with available columns
        available_numerical = [col for col in numerical_features if col in df_enhanced.columns]
        available_categorical = [col for col in categorical_features if col in df_enhanced.columns]
        
        # Create feature matrices
        X_numerical = df_enhanced[available_numerical] if available_numerical else pd.DataFrame()
        X_categorical = df_enhanced[available_categorical] if available_categorical else pd.DataFrame()
        
        # Handle text features
        if text_feature and text_feature in df_enhanced.columns:
            X_text = df_enhanced[text_feature].fillna('').astype(str)
        else:
            X_text = pd.Series(['Vehicle prediction request'], index=df_enhanced.index)
        
        return process_features_robust(X_numerical, X_categorical, X_text, model_objects)
        
    except Exception as e:
        raise Exception(f"Feature preparation failed: {str(e)}")

def process_features_robust(X_numerical, X_categorical, X_text, model_objects):
    """Process features with comprehensive error handling"""
    try:
        from scipy.sparse import hstack, csr_matrix
        
        processed_features = []
        
        # Process numerical features
        if not X_numerical.empty:
            try:
                if model_objects.get('numerical_imputer') is not None:
                    X_num = model_objects['numerical_imputer'].transform(X_numerical)
                else:
                    X_num = X_numerical.fillna(0).values
                
                if model_objects.get('numerical_scaler') is not None:
                    X_num = model_objects['numerical_scaler'].transform(X_num)
                
                processed_features.append(X_num)
                
            except Exception as e:
                processed_features.append(X_numerical.fillna(0).values)
        
        # Process categorical features
        if not X_categorical.empty:
            try:
                if model_objects.get('categorical_imputer') is not None:
                    X_cat = model_objects['categorical_imputer'].transform(X_categorical)
                    processed_features.append(X_cat)
                else:
                    processed_features.append(X_categorical.fillna(0).values)
            except Exception as e:
                pass
        
        # Process text features
        try:
            if model_objects.get('tfidf') is not None:
                X_text_processed = model_objects['tfidf'].transform(X_text)
                processed_features.append(X_text_processed.toarray())
        except Exception as e:
            pass
        
        if not processed_features:
            raise ValueError("No features could be processed successfully")
        
        # Combine features
        if len(processed_features) == 1:
            X_combined = csr_matrix(processed_features[0])
        else:
            matrices = [csr_matrix(f) for f in processed_features]
            X_combined = hstack(matrices)
        
        # Apply feature selection if available
        if model_objects.get('feature_selector') is not None:
            try:
                X_final = model_objects['feature_selector'].transform(X_combined)
            except Exception as e:
                X_final = X_combined
        else:
            X_final = X_combined
        
        return X_final
        
    except Exception as e:
        raise Exception(f"Feature processing failed: {str(e)}")

def make_prediction_enhanced(data, model_path):
    """Enhanced prediction with clean JSON output"""
    try:
        # Load model
        model_objects, error_msg = load_model_robust(model_path)
        if model_objects is None:
            return {'error': f'Could not load model: {error_msg}'}
        
        # Prepare features
        X_processed = prepare_features_adaptive(data, model_objects)
        
        # Make prediction
        model = model_objects['final_model']
        prediction = model.predict(X_processed)[0]
        
        # Get prediction probabilities and confidence
        confidence = 0.75  # Default confidence
        probabilities = None
        
        if hasattr(model, 'predict_proba'):
            try:
                probabilities = model.predict_proba(X_processed)
                confidence = np.max(probabilities, axis=1)[0]
            except Exception as e:
                pass
        
        # Convert prediction back to category name
        try:
            label_encoder = model_objects['label_encoder']
            predicted_category = label_encoder.inverse_transform([prediction])[0]
        except Exception as e:
            return {'error': f'Label decoding error: {str(e)}'}
        
        # Prepare result
        result = {
            'prediction': predicted_category,
            'confidence': float(confidence),
            'timestamp': datetime.now().isoformat(),
            'model_type': model_objects.get('model_type', 'Enhanced ML Model'),
            'method_used': 'ml_prediction',
            'feature_count': X_processed.shape[1],
            'status_used': data.get('Status', 2)
        }
        
        # Add probability distribution if available
        if probabilities is not None and hasattr(label_encoder, 'classes_'):
            try:
                prob_dict = {}
                for i, class_name in enumerate(label_encoder.classes_):
                    prob_dict[class_name] = float(probabilities[0][i])
                result['probability_distribution'] = prob_dict
            except Exception as e:
                pass
        
        return result
        
    except Exception as e:
        return {'error': f'Prediction failed: {str(e)}'}

def main():
    """Main function with clean JSON output only"""
    try:
        if len(sys.argv) != 3:
            error_msg = 'Usage: python predict.py <data_file> <model_path>'
            print(json.dumps({'error': error_msg}))
            sys.exit(1)
        
        data_file = sys.argv[1]
        model_path = sys.argv[2]
        
        # Validate input file
        if not os.path.exists(data_file):
            error_msg = f'Data file does not exist: {data_file}'
            print(json.dumps({'error': error_msg}))
            sys.exit(1)
        
        # Read input data
        try:
            with open(data_file, 'r') as f:
                data = json.load(f)
        except Exception as e:
            error_msg = f'Could not read data file: {str(e)}'
            print(json.dumps({'error': error_msg}))
            sys.exit(1)
        
        # Make prediction
        result = make_prediction_enhanced(data, model_path)
        
        # Output ONLY clean JSON (no log messages)
        print(json.dumps(result, indent=2))
        
    except Exception as e:
        error_msg = f'Main execution error: {str(e)}'
        print(json.dumps({'error': error_msg}))
        sys.exit(1)

if __name__ == "__main__":
    main()