import sys
import json
import pickle
import pandas as pd
import numpy as np
import os
import warnings
from datetime import datetime
warnings.filterwarnings('ignore')

def convert_data_types(data):
    """Convert string data from Laravel to proper Python types"""
    converted = data.copy()
    
    # Define fields that should be numeric
    numeric_fields = [
        'Odometer', 'Priority', 'service_count', 'Building_encoded', 
        'Vehicle_encoded', 'Status_encoded', 'MrType_encoded', 
        'response_days', 'request_hour', 'request_day_of_week', 'request_month'
    ]
    
    # Convert string numbers to actual numbers
    for field in numeric_fields:
        if field in converted:
            try:
                # Convert string to appropriate numeric type
                value = converted[field]
                if isinstance(value, str):
                    # Try integer first
                    if '.' not in value:
                        converted[field] = int(value)
                    else:
                        converted[field] = float(value)
                elif isinstance(value, (int, float)):
                    # Already numeric, keep as is
                    converted[field] = value
                else:
                    # Default value for invalid types
                    converted[field] = 0
            except (ValueError, TypeError):
                # If conversion fails, use default value
                converted[field] = 0
    
    # Ensure Description is string
    if 'Description' in converted:
        converted['Description'] = str(converted['Description'])
    
    # Ensure request_date is string
    if 'request_date' in converted:
        converted['request_date'] = str(converted['request_date'])
    
    return converted

def load_model(model_path):
    """Load the trained model and components"""
    try:
        if not os.path.exists(model_path):
            return None, f"Model file does not exist: {model_path}"
        
        with open(model_path, 'rb') as f:
            model_objects = pickle.load(f)
        
        # Validate model structure
        required_keys = ['final_model', 'label_encoder', 'numerical_features']
        missing_keys = [key for key in required_keys if key not in model_objects]
        if missing_keys:
            return None, f"Model missing required keys: {missing_keys}"
        
        return model_objects, "Success"
        
    except Exception as e:
        return None, f"Model loading error: {str(e)}"

def create_enhanced_features(df):
    """Create enhanced features with proper data type handling"""
    df_enhanced = df.copy()
    
    # Weekend feature
    if 'request_day_of_week' in df.columns:
        # Ensure numeric comparison
        day_of_week = pd.to_numeric(df['request_day_of_week'], errors='coerce').fillna(0)
        df_enhanced['is_weekend'] = (day_of_week >= 5).astype(int)
    
    # Business hours feature  
    if 'request_hour' in df.columns:
        # Ensure numeric comparison
        hour = pd.to_numeric(df['request_hour'], errors='coerce').fillna(12)
        df_enhanced['is_business_hours'] = ((hour >= 8) & (hour <= 17)).astype(int)
    
    # High maintenance vehicle feature
    if 'service_count' in df.columns:
        # Ensure numeric comparison
        service_count = pd.to_numeric(df['service_count'], errors='coerce').fillna(200)
        service_threshold = service_count.quantile(0.75) if len(df) > 1 else 200
        df_enhanced['high_maintenance_vehicle'] = (service_count >= service_threshold).astype(int)
    
    return df_enhanced

def prepare_features(data, model_objects):
    """Prepare features from raw data with data type conversion"""
    try:
        # Step 1: Convert data types first
        converted_data = convert_data_types(data)
        
        # Step 2: Convert to DataFrame
        if isinstance(converted_data, dict):
            df = pd.DataFrame([converted_data])
        else:
            df = converted_data.copy()
        
        # Step 3: Parse request_date and create time features
        if 'request_date' in df.columns:
            try:
                df['request_date'] = pd.to_datetime(df['request_date'])
                df['request_day_of_week'] = df['request_date'].dt.dayofweek
                df['request_month'] = df['request_date'].dt.month
                df['request_hour'] = df['request_date'].dt.hour
            except Exception as e:
                # If date parsing fails, use provided values or defaults
                if 'request_day_of_week' not in df.columns:
                    df['request_day_of_week'] = 2  # Default Tuesday
                if 'request_month' not in df.columns:
                    df['request_month'] = 6  # Default June
                if 'request_hour' not in df.columns:
                    df['request_hour'] = 10  # Default 10 AM
        
        # Step 4: Ensure all numeric columns are actually numeric
        numeric_columns = ['Odometer', 'Priority', 'service_count', 'Building_encoded', 
                          'Vehicle_encoded', 'Status_encoded', 'MrType_encoded', 
                          'response_days', 'request_hour', 'request_day_of_week', 'request_month']
        
        for col in numeric_columns:
            if col in df.columns:
                df[col] = pd.to_numeric(df[col], errors='coerce').fillna(0)
        
        # Step 5: Create enhanced features
        df_enhanced = create_enhanced_features(df)
        
        # Step 6: Get feature lists from model
        numerical_features = model_objects.get('numerical_features', [])
        categorical_features = model_objects.get('categorical_features', [])
        text_feature = model_objects.get('text_feature')
        
        # Step 7: Prepare feature matrices
        X_numerical = df_enhanced[numerical_features] if numerical_features else pd.DataFrame()
        X_categorical = df_enhanced[categorical_features] if categorical_features else pd.DataFrame()
        
        if text_feature and text_feature in df_enhanced.columns:
            X_text = df_enhanced[text_feature]
        else:
            X_text = pd.Series('', index=df_enhanced.index)
        
        return process_features(X_numerical, X_categorical, X_text, model_objects)
        
    except Exception as e:
        raise Exception(f"Feature preparation error: {str(e)}")

def process_features(X_numerical, X_categorical, X_text, model_objects):
    """Process features using trained preprocessors"""
    try:
        from scipy.sparse import hstack, csr_matrix
        
        processed_features = []
        
        # Process numerical features
        if not X_numerical.empty and model_objects.get('numerical_imputer') is not None:
            X_num = model_objects['numerical_imputer'].transform(X_numerical)
            X_num = model_objects['numerical_scaler'].transform(X_num)
            processed_features.append(X_num)
        
        # Process categorical features
        if not X_categorical.empty and model_objects.get('categorical_imputer') is not None:
            X_cat = model_objects['categorical_imputer'].transform(X_categorical)
            processed_features.append(X_cat)
        
        # Process text features
        if model_objects.get('tfidf') is not None:
            X_text_clean = X_text.fillna('').astype(str)
            X_text_processed = model_objects['tfidf'].transform(X_text_clean)
            processed_features.append(X_text_processed.toarray())
        
        if processed_features:
            # Combine features
            matrices = [csr_matrix(f) for f in processed_features]
            X_combined = hstack(matrices)
            
            # Apply feature selection if available
            if model_objects.get('feature_selector') is not None:
                X_final = model_objects['feature_selector'].transform(X_combined)
            else:
                X_final = X_combined
            
            return X_final
        else:
            raise ValueError("No features could be processed")
            
    except Exception as e:
        raise Exception(f"Feature processing error: {str(e)}")

def make_prediction(data, model_path):
    """Make prediction using the trained model"""
    try:
        # Load model
        model_objects, error_msg = load_model(model_path)
        if model_objects is None:
            return {'error': f'Could not load model: {error_msg}'}
        
        # Prepare features
        X_processed = prepare_features(data, model_objects)
        
        # Make prediction
        model = model_objects['final_model']
        prediction = model.predict(X_processed)[0]
        
        # Get prediction probabilities
        if hasattr(model, 'predict_proba'):
            probabilities = model.predict_proba(X_processed)
            confidence = np.max(probabilities, axis=1)[0]
        else:
            confidence = 0.85
        
        # Convert prediction back to category name
        label_encoder = model_objects['label_encoder']
        predicted_category = label_encoder.inverse_transform([prediction])[0]
        
        return {
            'prediction': predicted_category,
            'confidence': float(confidence),
            'timestamp': datetime.now().isoformat(),
            'model_type': model_objects.get('model_type', 'ML Model')
        }
        
    except Exception as e:
        return {'error': f'Prediction error: {str(e)}'}

def main():
    """Main function called from command line"""
    if len(sys.argv) != 3:
        print(json.dumps({'error': 'Usage: python predict.py <data_file> <model_path>'}))
        sys.exit(1)
    
    data_file = sys.argv[1]
    model_path = sys.argv[2]
    
    try:
        # Read input data
        with open(data_file, 'r') as f:
            data = json.load(f)
        
        # Make prediction
        result = make_prediction(data, model_path)
        
        # Output result as JSON
        print(json.dumps(result))
        
    except Exception as e:
        print(json.dumps({'error': f'Main execution error: {str(e)}'}))

if __name__ == "__main__":
    main()