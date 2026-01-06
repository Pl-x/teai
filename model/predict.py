"""
Complete Tea Leaf Disease Classification System
All-V2 based model for detecting 8 tea leaf conditions

Word: If you ever fall in love with tech fall in love with python if you're soft...php is for enthusiasts...it makes you feel like shit😂😂
"""

import os
import sys
import matplotlib

matplotlib.use('Agg')  # Non-interactive backend for Docker
os.environ['MPLBACKEND'] = 'Agg'
os.environ['QT_LOGGING_RULES'] = '*.debug=false;qt.qpa.*=false'

import numpy as np
from tensorflow.keras.preprocessing.image import load_img, img_to_array
from tensorflow.keras.models import load_model
import matplotlib.pyplot as plt 

# Disease classifications
DISEASE_CLASSES = [
    'Anthracnose',
    'Algal leaf',
    'Bird eye spot',
    'Brown blight',
    'Gray light',
    'Healthy',
    'Red leaf spot',
    'White spot'
]

# Model path - relative to this script's directory
MODEL_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(MODEL_DIR, 'tea-model.hdf5')

# Load model once (for efficiency)
_model = None

def load_disease_model():
    """Load the trained model (cached)"""
    global _model
    if _model is None:
        if not os.path.exists(MODEL_PATH):
            raise FileNotFoundError(f"Model file not found at: {MODEL_PATH}")
        _model = load_model(MODEL_PATH, compile=False)
    return _model

# ============================================
# BASIC PREDICTION
# ============================================
def predict_disease(image_path):
    """
    Predict tea leaf disease from image path
    
    Args:
        image_path: Path to tea leaf image
        
    Returns:
        disease_name: Predicted disease name
    """
    model = load_disease_model()
    
    # Load and preprocess image
    test_image = load_img(image_path, target_size=(180, 180))
    test_image = img_to_array(test_image)
    test_image = np.expand_dims(test_image, axis=0)
    
    # Predict
    result = model.predict(test_image, verbose=0)
    pred_idx = np.argmax(result, axis=1)[0]
    disease = DISEASE_CLASSES[pred_idx]
    
    return disease

# ============================================
# DETAILED PREDICTION WITH CONFIDENCE
# ============================================
def predict_with_confidence(image_path):
    """
    Predict disease with confidence scores
    
    Returns:
        dict with prediction, confidence, and all probabilities
    """
    model = load_disease_model()
    
    # Validate image path exists
    if not os.path.exists(image_path):
        raise FileNotFoundError(f"Image file not found: {image_path}")
    
    # Load and preprocess
    test_image = load_img(image_path, target_size=(180, 180))
    test_image = img_to_array(test_image)
    test_image = np.expand_dims(test_image, axis=0)
    
    # Predict
    result = model.predict(test_image, verbose=0)
    pred_idx = np.argmax(result[0])
    confidence = result[0][pred_idx]
    disease = DISEASE_CLASSES[pred_idx]
    
    # Create probability dictionary
    probabilities = {DISEASE_CLASSES[i]: float(result[0][i]) 
                     for i in range(len(DISEASE_CLASSES))}
    
    return {
        'disease': disease,
        'confidence': float(confidence),
        'probabilities': probabilities,
        'is_healthy': disease == 'Healthy'
    }

# ============================================
# VISUALIZATION
# ============================================
def visualize_prediction(image_path, result, save_path=None):
    """
    Generate and optionally save a visualization of the prediction result.
    
    Args:
        image_path (str): Path to the original image.
        result (dict): The prediction result from predict_with_confidence.
        save_path (str, optional): Path to save the visualization image.
        
    Returns:
        str: The path where the visualization was saved (or None if not saved).
    """
    # Use load_img from Keras to load the image for display
    try:
        img_display = load_img(image_path)
    except Exception:
        # Fallback if image loading fails for display
        print(f"Warning: Could not load image for visualization display: {image_path}", file=sys.stderr)
        img_display = np.zeros((100, 100, 3), dtype=np.uint8) # Create a placeholder

    plt.ioff() # Turn off interactive plotting
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 5))

    # Display image on ax1
    ax1.imshow(img_display)
    ax1.axis('off')
    
    # Add prediction text
    status = "✓ HEALTHY" if result['is_healthy'] else "⚠ DISEASED"
    color = 'green' if result['is_healthy'] else 'red'
    
    ax1.set_title(
        f"{status}\n{result['disease']}\nConfidence: {result['confidence']:.1%}",
        fontsize=14,
        color=color,
        weight='bold'
    )
    
    # Display probability distribution
    sorted_probs = sorted(result['probabilities'].items(), key=lambda item: item[1], reverse=True)
    diseases = [item[0] for item in sorted_probs]
    probs = [item[1] for item in sorted_probs]
    
    colors = ['green' if d == result['disease'] else 'lightgray' for d in diseases]
    
    bars = ax2.barh(diseases, probs, color=colors)
    ax2.set_xlabel('Probability', fontsize=11)
    ax2.set_title('Disease Probabilities', fontsize=12, weight='bold')
    ax2.set_xlim([0, 1])
    ax2.grid(axis='x', alpha=0.3)
    
    # Highlight predicted class
    for i, (bar, prob) in enumerate(zip(bars, probs)):
        if prob > 0.01:
            ax2.text(prob + 0.02, bar.get_y() + bar.get_height()/2, 
                    f'{prob:.1%}', va='center', fontsize=9)
    
    plt.tight_layout()
    
    if save_path:
        os.makedirs(os.path.dirname(save_path), exist_ok=True)
        try:
            plt.savefig(save_path, dpi=150, bbox_inches='tight')
            plt.close(fig) # Close the figure to free up memory
            return save_path
        except Exception as e:
            print(f"Error saving visualization: {e}", file=sys.stderr)
            plt.close(fig)
            return None
    
    plt.show()
    plt.close(fig)
    return None

# ============================================
# FOLDER ANALYSIS
# ============================================
def analyze_folder(folder_path, show_summary=True):
    # NOTE: The original predict_batch was replaced with a loop using predict_with_confidence
    """
    Analyze all tea leaf images in a folder
    ...
    """
    # Get all image files
    extensions = ('.jpg', '.jpeg', '.png', '.bmp')
    image_files = [os.path.join(folder_path, f) for f in os.listdir(folder_path)
                   if f.lower().endswith(extensions)]
    
    if not image_files:
        print(f"No images found in {folder_path}")
        return None
    
    # Predict all images
    results = []
    for f in image_files:
        try:
            results.append(predict_with_confidence(f))
        except Exception as e:
            print(f"Could not predict for {f}: {e}", file=sys.stderr)

    
    # Calculate statistics
    disease_counts = {}
    for result in results:
        disease = result['disease']
        disease_counts[disease] = disease_counts.get(disease, 0) + 1
    
    healthy_count = disease_counts.get('Healthy', 0)
    diseased_count = len(results) - healthy_count
    
    if show_summary:
        print("\n" + "="*60)
        print("ANALYSIS SUMMARY")
        print("="*60)
        print(f"Total images: {len(results)}")
        print(f"Healthy: {healthy_count} ({healthy_count/len(results)*100:.1f}%)")
        print(f"Diseased: {diseased_count} ({diseased_count/len(results)*100:.1f}%)")
        print("\nDisease breakdown:")
        for disease, count in sorted(disease_counts.items(), key=lambda x: x[1], reverse=True):
            print(f"  {disease}: {count} ({count/len(results)*100:.1f}%)")
        print("="*60)
    
    return {
        'total': len(results),
        'healthy': healthy_count,
        'diseased': diseased_count,
        'disease_counts': disease_counts,
        'results': results
    }

# ============================================
# DISEASE INFORMATION
# ============================================
def get_disease_info(disease_name):
    """Get information about a specific tea leaf disease"""
    disease_info = {
        'Anthracnose': {
            'description': 'Fungal disease causing dark lesions on leaves',
            'severity': 'High',
            'treatment': 'Apply fungicide, remove infected leaves, ensure proper ventilation'
        },
        'Algal leaf': {
            'description': 'Algal infection creating greenish-gray patches',
            'severity': 'Medium',
            'treatment': 'Improve air circulation, apply copper-based fungicide, remove infected tissue'
        },
        'Bird eye spot': {
            'description': 'Circular spots with gray centers on leaves',
            'severity': 'Medium',
            'treatment': 'Apply fungicide, prune affected areas, improve air flow'
        },
        'Brown blight': {
            'description': 'Brown lesions spreading across leaf surface',
            'severity': 'High',
            'treatment': 'Apply systemic fungicide, improve drainage, remove heavily infected leaves'
        },
        'Gray light': {
            'description': 'Gray discoloration affecting leaf tissue',
            'severity': 'Medium',
            'treatment': 'Apply appropriate fungicide, improve light exposure'
        },
        'Healthy': {
            'description': 'No disease detected - leaf is healthy',
            'severity': 'None',
            'treatment': 'Continue regular maintenance and monitoring'
        },
        'Red leaf spot': {
            'description': 'Red-brown spots appearing on leaves',
            'severity': 'Medium',
            'treatment': 'Apply fungicide treatment, improve ventilation, remove infected leaves'
        },
        'White spot': {
            'description': 'White or light-colored spots on leaf surface',
            'severity': 'Low to Medium',
            'treatment': 'Monitor closely, apply fungicide if spreading, ensure good air circulation'
        }
    }
    
    return disease_info.get(disease_name, {'description': 'Unknown disease', 'severity': 'Unknown', 'treatment': 'Consult agricultural extension service'})

# ============================================
# EXAMPLE USAGE
# ============================================
if __name__ == "__main__":
    # Example usage
    test_image_path = os.path.join(MODEL_DIR, 'test_leaf.jpg')
    try:
        prediction = predict_with_confidence(test_image_path)
        print("Prediction Result:", prediction)
        
        # Visualize
        vis_path = os.path.join(MODEL_DIR, 'test_leaf_vis.png')
        visualize_prediction(test_image_path, prediction, save_path=vis_path)
        print(f"Visualization saved to: {vis_path}")
        
    except Exception as e:
        print(f"Error during prediction or visualization: {e}")
