#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import os
import json
import warnings

# 1. Suppress TensorFlow logs (Must be before importing tensorflow/keras)
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'
os.environ['TF_ENABLE_ONEDNN_OPTS'] = '0'

# 2. Suppress Python Warnings (Fixes the specific UserWarning you are seeing)
warnings.filterwarnings("ignore")

# Add current directory to path to ensure we can import 'predict.py'
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))


def main():
    # 1. Validate Arguments
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No image path provided'}))
        sys.exit(1)

    image_path = sys.argv[1]

    # 2. Validate File Existence
    if not os.path.exists(image_path):
        print(json.dumps({'error': f'Image file not found: {image_path}'}))
        sys.exit(1)

    try:
        # 3. Import Logic (ADDED visualize_prediction)
        from predict import predict_with_confidence, get_disease_info, visualize_prediction

        # 4. Run Prediction
        result = predict_with_confidence(image_path)

        # 5. Get Disease Details
        # Safe get in case 'disease' key is missing
        disease_name = result.get('disease', 'Unknown')
        disease_info = get_disease_info(disease_name)
        result['info'] = disease_info

        # 6. Generate and save visualization (NEW LOGIC)
        # Determine the visualization save path (e.g., /path/to/uploads/image.png -> /path/to/uploads/image_vis.png)
        base, ext = os.path.splitext(image_path)
        vis_save_path = base + '_vis.png'

        visualize_path = visualize_prediction(
            image_path, result, vis_save_path)

        # Add the visualization path relative to the web root/uploads directory
        if visualize_path:
            # This assumes the PHP script is running from a level that can access 'uploads/'
            # We want the path to be 'uploads/filename_vis.png'
            vis_relative_path = 'uploads/' + os.path.basename(vis_save_path)
            result['visualization_path'] = vis_relative_path

        # 7. Output Result
        print(json.dumps(result))

    except ImportError as e:
        print(json.dumps({
            'error': f'Import failed. Ensure predict.py exists and libraries are installed: {str(e)}'
        }))
        sys.exit(1)
    except Exception as e:
        # Catch generic errors (like model loading failures)
        print(json.dumps({'error': f'Prediction error: {str(e)}'}))
        sys.exit(1)


if __name__ == '__main__':
    main()
