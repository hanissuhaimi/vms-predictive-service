import pandas as pd
import numpy as np
import os
import time
from datetime import datetime
import pickle
import warnings
warnings.filterwarnings('ignore')

# ML Libraries
from sklearn.model_selection import train_test_split
from sklearn.ensemble import GradientBoostingClassifier
from sklearn.metrics import (classification_report, confusion_matrix, accuracy_score, 
                           precision_recall_fscore_support)
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.impute import SimpleImputer
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.feature_selection import SelectKBest, f_classif
from sklearn.utils.class_weight import compute_class_weight
from scipy.sparse import hstack, csr_matrix

# Visualization
import matplotlib.pyplot as plt
import seaborn as sns

class VMSModelTrainer:
    """Vehicle Maintenance System Model Trainer"""
    
    def __init__(self, data_path='data/Cleaned_ServiceRequest.xlsx', output_dir='model_training_output'):
        self.data_path = data_path
        self.output_dir = output_dir
        self.df = None
        self.model_objects = {}
        
        # Create output directory
        if not os.path.exists(self.output_dir):
            os.makedirs(self.output_dir)
            print(f"Created output directory: {self.output_dir}")
    
    def load_data(self):
        """Load and validate the dataset"""
        print("=" * 80)
        print("VMS MODEL TRAINING")
        print("=" * 80)
        print("\n1. LOADING DATA")
        print("-" * 40)
        
        # Try different possible data paths
        possible_paths = [
            self.data_path,
            'Cleaned_ServiceRequest.xlsx',
            'VMS_ServiceRequest_cleaned.csv',
            'data/VMS_ServiceRequest_cleaned.csv'
        ]
        
        for path in possible_paths:
            if os.path.exists(path):
                print(f"Found data file: {path}")
                try:
                    if path.endswith('.xlsx'):
                        self.df = pd.read_excel(path)
                    else:
                        self.df = pd.read_csv(path)
                    print(f"✓ Loaded data with shape: {self.df.shape}")
                    return True
                except Exception as e:
                    print(f"Error loading {path}: {e}")
                    continue
        
        print("❌ Error: Could not find data file. Please ensure one of these files exists:")
        for path in possible_paths:
            print(f"  - {path}")
        return False
    
    def prepare_features(self):
        """Prepare features for model training"""
        print("\n2. FEATURE ENGINEERING")
        print("-" * 40)
        
        # Check target variable
        target = 'maintenance_category'
        if target not in self.df.columns:
            print(f"❌ Error: Target variable '{target}' not found")
            print(f"Available columns: {list(self.df.columns)}")
            return False
        
        # Clean data
        self.df_clean = self.df.dropna(subset=[target]).copy()
        print(f"Dataset shape after cleaning: {self.df_clean.shape}")
        
        # Define feature sets
        self.core_features = [
            'Priority', 'service_count',
            'Building_encoded', 'Vehicle_encoded', 'Status_encoded', 'MrType_encoded'
        ]
        
        self.time_features = ['request_day_of_week', 'request_month', 'request_hour']
        self.high_impact_features = ['response_days', 'Odometer']
        
        # Build feature list (only include existing columns)
        self.features = [f for f in self.core_features if f in self.df_clean.columns]
        self.features.extend([f for f in self.time_features if f in self.df_clean.columns])
        self.features.extend([f for f in self.high_impact_features if f in self.df_clean.columns])
        
        self.text_feature = 'Description' if 'Description' in self.df_clean.columns else None
        self.target = target
        
        print(f"Using features: {self.features}")
        print(f"Text feature: {self.text_feature}")
        
        return True
    
    def create_enhanced_features(self):
        """Add enhanced features"""
        print("Creating enhanced features...")
        
        # Weekend feature
        if 'request_day_of_week' in self.df_clean.columns:
            self.df_clean['is_weekend'] = (self.df_clean['request_day_of_week'] >= 5).astype(int)
            self.features.append('is_weekend')
        
        # Business hours feature
        if 'request_hour' in self.df_clean.columns:
            self.df_clean['is_business_hours'] = ((self.df_clean['request_hour'] >= 8) &
                                                 (self.df_clean['request_hour'] <= 17)).astype(int)
            self.features.append('is_business_hours')
        
        # High maintenance vehicle feature
        if 'service_count' in self.df_clean.columns:
            service_threshold = self.df_clean['service_count'].quantile(0.75)
            self.df_clean['high_maintenance_vehicle'] = (self.df_clean['service_count'] >= service_threshold).astype(int)
            self.features.append('high_maintenance_vehicle')
        
        print(f"✓ Added enhanced features. Total features: {len(self.features)}")
    
    def prepare_data_splits(self):
        """Prepare train/test splits and encode target"""
        print("\n3. DATA PREPROCESSING")
        print("-" * 40)
        
        # Encode target variable
        self.le_target = LabelEncoder()
        y_encoded = self.le_target.fit_transform(self.df_clean[self.target])
        
        # Split data
        X_train_idx, X_test_idx = train_test_split(
            range(len(self.df_clean)),
            test_size=0.2,
            random_state=42,
            stratify=y_encoded
        )
        
        self.X_train_idx = X_train_idx
        self.X_test_idx = X_test_idx
        self.y_train = y_encoded[X_train_idx]
        self.y_test = y_encoded[X_test_idx]
        
        print(f"Training: {len(X_train_idx):,}, Test: {len(X_test_idx):,}")
    
    def process_features(self):
        """Process all features for training"""
        print("Processing features...")
        
        # Separate feature types
        self.numerical_features = []
        self.categorical_features = []
        
        for f in self.features:
            if self.df_clean[f].dtype in ['int64', 'float64']:
                self.numerical_features.append(f)
            else:
                self.categorical_features.append(f)
        
        print(f"Numerical: {len(self.numerical_features)}, Categorical: {len(self.categorical_features)}")
        
        # Process features
        processed_features = []
        feature_names = []
        
        # Process numerical features
        if self.numerical_features:
            print("Processing numerical features...")
            X_numerical = self.df_clean[self.numerical_features]
            
            self.numerical_imputer = SimpleImputer(strategy='median')
            self.numerical_scaler = StandardScaler()
            
            X_num_train = self.numerical_imputer.fit_transform(X_numerical.iloc[self.X_train_idx])
            X_num_test = self.numerical_imputer.transform(X_numerical.iloc[self.X_test_idx])
            
            X_num_train = self.numerical_scaler.fit_transform(X_num_train)
            X_num_test = self.numerical_scaler.transform(X_num_test)
            
            processed_features.append(('numerical', X_num_train, X_num_test))
            feature_names.extend(self.numerical_features)
        else:
            self.numerical_imputer = None
            self.numerical_scaler = None
        
        # Process categorical features
        if self.categorical_features:
            print("Processing categorical features...")
            X_categorical = self.df_clean[self.categorical_features]
            
            self.categorical_imputer = SimpleImputer(strategy='most_frequent')
            X_cat_train = self.categorical_imputer.fit_transform(X_categorical.iloc[self.X_train_idx])
            X_cat_test = self.categorical_imputer.transform(X_categorical.iloc[self.X_test_idx])
            
            processed_features.append(('categorical', X_cat_train, X_cat_test))
            feature_names.extend(self.categorical_features)
        else:
            self.categorical_imputer = None
        
        # Process text features
        if self.text_feature and self.text_feature in self.df_clean.columns:
            print("Processing text features...")
            X_text = self.df_clean[self.text_feature].fillna('').astype(str)
            
            self.tfidf = TfidfVectorizer(
                max_features=100,
                stop_words='english',
                ngram_range=(1, 2),
                min_df=2,
                max_df=0.95
            )
            
            X_text_train = self.tfidf.fit_transform(X_text.iloc[self.X_train_idx])
            X_text_test = self.tfidf.transform(X_text.iloc[self.X_test_idx])
            
            processed_features.append(('text', X_text_train, X_text_test))
            text_feature_names = [f'text_{f}' for f in self.tfidf.get_feature_names_out()]
            feature_names.extend(text_feature_names)
        else:
            self.tfidf = None
        
        # Combine features
        if processed_features:
            train_matrices = []
            test_matrices = []
            
            for feature_type, train_data, test_data in processed_features:
                train_matrices.append(csr_matrix(train_data))
                test_matrices.append(csr_matrix(test_data))
            
            X_train_combined = hstack(train_matrices)
            X_test_combined = hstack(test_matrices)
        else:
            print("❌ Error: No features to process!")
            return False
        
        # Feature selection
        if X_train_combined.shape[1] > 50:
            print("Applying feature selection...")
            self.selector = SelectKBest(f_classif, k=min(50, X_train_combined.shape[1]))
            self.X_train_final = self.selector.fit_transform(X_train_combined, self.y_train)
            self.X_test_final = self.selector.transform(X_test_combined)
            
            selected_indices = self.selector.get_support()
            self.final_feature_names = [feature_names[i] for i in range(len(feature_names)) 
                                       if i < len(selected_indices) and selected_indices[i]]
            print(f"✓ Selected {self.X_train_final.shape[1]} features")
        else:
            self.X_train_final = X_train_combined
            self.X_test_final = X_test_combined
            self.final_feature_names = feature_names
            self.selector = None
        
        print(f"Final feature matrix: {self.X_train_final.shape}")
        return True
    
    def train_model(self):
        """Train the Gradient Boosting model"""
        print("\n4. MODEL TRAINING")
        print("-" * 40)
        
        print("Training Gradient Boosting model...")
        start_time = time.time()
        
        # Train Gradient Boosting model
        self.gb_model = GradientBoostingClassifier(
            n_estimators=80,
            learning_rate=0.1,
            max_depth=8,
            min_samples_split=5,
            min_samples_leaf=2,
            subsample=0.8,
            random_state=42,
            validation_fraction=0.1,
            n_iter_no_change=10
        )
        
        # Train model
        self.gb_model.fit(self.X_train_final, self.y_train)
        self.training_time = time.time() - start_time
        
        print(f"✓ Training completed in {self.training_time:.2f}s")
    
    def evaluate_model(self):
        """Evaluate model performance"""
        print("\n5. MODEL EVALUATION")
        print("-" * 40)
        
        # Make predictions
        y_pred = self.gb_model.predict(self.X_test_final)
        
        # Calculate metrics
        self.accuracy = accuracy_score(self.y_test, y_pred)
        self.precision, self.recall, self.f1, _ = precision_recall_fscore_support(
            self.y_test, y_pred, average='weighted'
        )
        
        print(f"✓ Test Accuracy: {self.accuracy:.3f}")
        print(f"✓ Precision: {self.precision:.3f}")
        print(f"✓ Recall: {self.recall:.3f}")
        print(f"✓ F1-Score: {self.f1:.3f}")
        
        # Classification report
        target_names = self.le_target.classes_
        class_report = classification_report(self.y_test, y_pred, target_names=target_names)
        print("\nClassification Report:")
        print(class_report)
        
        return y_pred, target_names
    
    def save_model(self):
        """Save the trained model and components"""
        print("\n6. SAVING MODEL")
        print("-" * 40)
        
        # Prepare model objects for saving
        self.model_objects = {
            'final_model': self.gb_model,
            'model_type': 'Gradient Boosting',
            'numerical_features': self.numerical_features,
            'categorical_features': self.categorical_features,
            'text_feature': self.text_feature,
            'feature_names': self.final_feature_names,
            'numerical_imputer': self.numerical_imputer,
            'numerical_scaler': self.numerical_scaler,
            'categorical_imputer': self.categorical_imputer,
            'tfidf': self.tfidf,
            'feature_selector': self.selector,
            'label_encoder': self.le_target,
            'classes': self.le_target.classes_,
            'model_performance': {
                'accuracy': self.accuracy,
                'precision': self.precision,
                'recall': self.recall,
                'f1': self.f1,
                'training_time': self.training_time
            },
            'training_metadata': {
                'training_date': datetime.now().isoformat(),
                'training_samples': len(self.X_train_idx),
                'test_samples': len(self.X_test_idx),
                'n_features': self.X_train_final.shape[1],
                'n_classes': len(self.le_target.classes_)
            }
        }
        
        # Save model file
        model_filename = os.path.join(self.output_dir, 'maintenance_prediction_model.pkl')
        try:
            with open(model_filename, 'wb') as f:
                pickle.dump(self.model_objects, f)
            print(f"✓ Saved model to: {model_filename}")
            return True
        except Exception as e:
            print(f"❌ Error saving model: {e}")
            return False
    
    def create_visualizations(self, y_pred, target_names):
        """Create visualization plots"""
        try:
            # Confusion matrix
            cm = confusion_matrix(self.y_test, y_pred)
            plt.figure(figsize=(10, 8))
            sns.heatmap(cm, annot=True, fmt='d', cmap='Blues',
                       xticklabels=target_names, yticklabels=target_names)
            plt.title('Confusion Matrix - Gradient Boosting')
            plt.xlabel('Predicted')
            plt.ylabel('Actual')
            plt.xticks(rotation=45)
            plt.yticks(rotation=0)
            plt.tight_layout()
            plt.savefig(os.path.join(self.output_dir, 'confusion_matrix.png'), 
                       dpi=300, bbox_inches='tight')
            plt.close()
            print("✓ Saved confusion matrix")
            
            # Feature importance
            if hasattr(self.gb_model, 'feature_importances_'):
                importances = self.gb_model.feature_importances_
                feature_importance_df = pd.DataFrame({
                    'feature': self.final_feature_names[:len(importances)],
                    'importance': importances
                }).sort_values('importance', ascending=False)
                
                feature_importance_df.to_csv(
                    os.path.join(self.output_dir, 'feature_importance.csv'), index=False)
                
                # Plot top 15 features
                plt.figure(figsize=(10, 6))
                top_features = feature_importance_df.head(15)
                plt.barh(range(len(top_features)), top_features['importance'].values)
                plt.yticks(range(len(top_features)), top_features['feature'].values)
                plt.title('Top 15 Feature Importances')
                plt.xlabel('Importance')
                plt.gca().invert_yaxis()
                plt.tight_layout()
                plt.savefig(os.path.join(self.output_dir, 'feature_importance.png'), 
                           dpi=300, bbox_inches='tight')
                plt.close()
                print("✓ Saved feature importance")
                
        except Exception as e:
            print(f"⚠️ Warning: Could not create visualizations: {e}")
    
    def create_summary_report(self):
        """Create a summary report"""
        summary = f"""
VMS MODEL TRAINING SUMMARY
=========================

Training Date: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}
Training Time: {self.training_time:.2f} seconds
Final Accuracy: {self.accuracy:.3f}

Dataset:
- Training samples: {len(self.X_train_idx):,}
- Test samples: {len(self.X_test_idx):,}
- Features used: {self.X_train_final.shape[1]}
- Classes: {len(self.le_target.classes_)}

Performance:
- Accuracy: {self.accuracy:.1%}
- Precision: {self.precision:.1%}
- Recall: {self.recall:.1%}
- F1-Score: {self.f1:.1%}

Model Details:
- Algorithm: Gradient Boosting Classifier
- Estimators: 80
- Learning Rate: 0.1
- Max Depth: 8

Files Generated:
- maintenance_prediction_model.pkl
- confusion_matrix.png
- feature_importance.csv
- feature_importance.png
"""
        
        with open(os.path.join(self.output_dir, 'model_summary.txt'), 'w') as f:
            f.write(summary)
        
        print("✓ Created summary report")
    
    def run_full_training(self):
        """Run the complete training pipeline"""
        try:
            if not self.load_data():
                return False
            
            if not self.prepare_features():
                return False
            
            self.create_enhanced_features()
            self.prepare_data_splits()
            
            if not self.process_features():
                return False
            
            self.train_model()
            y_pred, target_names = self.evaluate_model()
            
            if not self.save_model():
                return False
            
            self.create_visualizations(y_pred, target_names)
            self.create_summary_report()
            
            print("\n" + "=" * 80)
            print("MODEL TRAINING COMPLETED! 🚀")
            print("=" * 80)
            print(f"🎯 Model: Gradient Boosting")
            print(f"🎯 Accuracy: {self.accuracy:.1%}")
            print(f"🎯 Training Time: {self.training_time:.2f} seconds")
            print(f"🎯 Features: {self.X_train_final.shape[1]}")
            print(f"🎯 Samples: {len(self.X_train_idx):,}")
            print(f"\n🚀 Model ready for deployment!")
            print(f"📁 Saved as: {os.path.join(self.output_dir, 'maintenance_prediction_model.pkl')}")
            
            return True
            
        except Exception as e:
            print(f"❌ Training failed: {e}")
            return False

def main():
    """Main function"""
    # You can customize these paths
    data_path = 'data/Cleaned_ServiceRequest.xlsx'  # Adjust path as needed
    output_dir = 'model_training_output'
    
    # Create trainer and run
    trainer = VMSModelTrainer(data_path=data_path, output_dir=output_dir)
    success = trainer.run_full_training()
    
    if success:
        print("\n✅ Training completed successfully!")
        print("Your new model is compatible with current Python/numpy versions.")
    else:
        print("\n❌ Training failed. Please check the error messages above.")

if __name__ == "__main__":
    main()