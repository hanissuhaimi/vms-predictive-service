import json
import subprocess
import tempfile
import os

def test_real_model():
    """Test the real ML model prediction"""
    print("=== Testing Real ML Model ===")
    
    # Check if model file exists
    model_path = "model_training_output/maintenance_prediction_model.pkl"
    if not os.path.exists(model_path):
        print(f"❌ Model file not found: {model_path}")
        print("Please train the model first using: python vms_model_training.py")
        return False
    
    print(f"✓ Found model file: {model_path}")
    
    # Test data matching the training format
    test_data = {
        "Description": "brake noise when stopping",
        "Odometer": 200000,
        "Priority": 1,
        "service_count": 150,
        "Building_encoded": 2,
        "Vehicle_encoded": 693,
        "Status_encoded": 3,
        "MrType_encoded": 0,
        "request_date": "2025-06-05 10:30:00",
        "response_days": 1,
        "request_hour": 10,
        "request_day_of_week": 4,
        "request_month": 6
    }
    
    # Create temp file
    with tempfile.NamedTemporaryFile(mode='w', delete=False, suffix='.json') as f:
        json.dump(test_data, f)
        temp_file = f.name
    
    try:
        # Run prediction
        result = subprocess.run([
            'python', 'python/predict.py', 
            temp_file, 
            model_path
        ], capture_output=True, text=True, cwd=os.getcwd())
        
        if result.returncode == 0:
            try:
                prediction = json.loads(result.stdout)
                if 'error' in prediction:
                    print(f"❌ Prediction error: {prediction['error']}")
                    return False
                else:
                    print(f"✅ Real ML prediction successful!")
                    print(f"Category: {prediction.get('prediction')}")
                    print(f"Confidence: {prediction.get('confidence'):.1%}")
                    print(f"Model Type: {prediction.get('model_type')}")
                    return True
            except json.JSONDecodeError:
                print(f"❌ Invalid JSON response: {result.stdout}")
                return False
        else:
            print(f"❌ Prediction failed:")
            print(f"STDOUT: {result.stdout}")
            print(f"STDERR: {result.stderr}")
            return False
            
    finally:
        os.unlink(temp_file)

if __name__ == "__main__":
    test_real_model()